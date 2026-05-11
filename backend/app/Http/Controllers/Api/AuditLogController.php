<?php
// app/Http/Controllers/Api/AuditLogController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLog\AuditLogIndexRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    // GET /api/v1/audit-logs
    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        $query = AuditLog::query()->orderByDesc('created_at');

        if ($type = $request->input('auditable_type')) {
            // Accept short name ("Member") or full ("App\Models\Member")
            $fullType = str_contains($type, '\\') ? $type : "App\\Models\\{$type}";
            $query->where('auditable_type', $fullType);
        }

        if ($id = $request->input('auditable_id')) {
            $query->where('auditable_id', $id);
        }

        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('auditable_label', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 25);
        $logs    = $query->paginate($perPage);

        return response()->json([
            'data' => AuditLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }

    // GET /api/v1/audit-logs/{id}
    public function show(AuditLog $auditLog): JsonResponse
    {
        $this->authorize('view', $auditLog);
        return response()->json(['data' => new AuditLogResource($auditLog)]);
    }

    // GET /api/v1/audit-logs/for/{type}/{id}
    // Convenience endpoint: fetch audit history for a specific record
    public function forRecord(string $type, int $id): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);
        $fullType = str_contains($type, '\\') ? $type : "App\\Models\\{$type}";

        $logs = AuditLog::where('auditable_type', $fullType)
            ->where('auditable_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => AuditLogResource::collection($logs)]);
    }
}
