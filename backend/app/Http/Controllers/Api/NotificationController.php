<?php
// app/Http/Controllers/Api/NotificationController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationLogResource;
use App\Models\NotificationLog;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $service
    ) {}

    // GET /api/v1/notification-logs
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NotificationLog::class);

        $query = NotificationLog::with('member')->orderByDesc('created_at');

        if ($s = $request->input('status'))  $query->where('status', $s);
        if ($c = $request->input('channel')) $query->where('channel', $c);
        if ($e = $request->input('event'))   $query->where('event', $e);
        if ($m = $request->input('member_id')) $query->where('member_id', $m);

        if ($from = $request->input('date_from')) $query->whereDate('created_at', '>=', $from);
        if ($to   = $request->input('date_to'))   $query->whereDate('created_at', '<=', $to);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhereHas('member', fn($mq) =>
                        $mq->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name',  'like', "%{$search}%")
                  );
            });
        }

        $perPage = $request->integer('per_page', 30);
        $logs    = $query->paginate($perPage);

        return response()->json([
            'data' => NotificationLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
            'stats' => [
                'total'  => NotificationLog::count(),
                'sent'   => NotificationLog::sent()->count(),
                'failed' => NotificationLog::failed()->count(),
                'sms'    => NotificationLog::sms()->count(),
                'email'  => NotificationLog::email()->count(),
            ],
        ]);
    }

    // GET /api/v1/notification-logs/settings
    // Returns all notification preferences and default templates
    public function settings(): JsonResponse
    {
        $this->authorize('viewAny', NotificationLog::class);

        $events   = array_keys($this->service->defaultTemplates());
        $channels = ['sms', 'email'];
        $prefs    = [];

        foreach ($events as $event) {
            foreach ($channels as $channel) {
                $prefs["{$event}_{$channel}"] = $this->service->isEnabled($event, $channel);
            }
        }

        return response()->json([
            'preferences'      => $prefs,
            'reminder_days'    => $this->service->reminderDaysBefore(),
            'default_templates'=> $this->service->defaultTemplates(),
            'events'           => $events,
        ]);
    }

    // PUT /api/v1/notification-logs/settings
    // Toggle notification preferences (saved to system_preferences)
    public function updateSettings(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NotificationLog::class);

        $validated = $request->validate([
            'preferences'   => 'required|array',
            'reminder_days' => 'sometimes|integer|min:1|max:30',
        ]);

        foreach ($validated['preferences'] as $key => $value) {
            \App\Models\SystemPreference::set("notify_{$key}", $value ? 'true' : 'false');
        }

        if (isset($validated['reminder_days'])) {
            \App\Models\SystemPreference::set('notify_reminder_days_before', $validated['reminder_days']);
        }

        return response()->json(['message' => 'Notification settings updated.']);
    }

    // POST /api/v1/notification-logs/test-sms
    // Send a test SMS to verify Semaphore is configured
    public function testSms(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NotificationLog::class);
        $request->validate(['number' => 'required|string']);

        $semaphore = app(\App\Services\SemaphoreService::class);
        $result    = $semaphore->send(
            $request->input('number'),
            'This is a test SMS from ' . config('semaphore.coop_name', 'CRS ECCO') . '. If you receive this, Semaphore is configured correctly.'
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Test SMS sent.' : 'Failed: ' . ($result['raw']['error'] ?? 'Unknown error'),
            'raw'     => $result['raw'],
        ]);
    }
}
