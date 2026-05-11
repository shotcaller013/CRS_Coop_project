<?php
// app/Http/Controllers/Api/ShareCapitalController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareCapital\PostTransactionRequest;
use App\Http\Resources\ShareCapitalResource;
use App\Models\Member;
use App\Models\ShareCapitalTransaction;
use App\Services\ShareCapitalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareCapitalController extends Controller
{
    public function __construct(
        private readonly ShareCapitalService $service
    ) {}

    // GET /api/v1/members/{member}/share-capital
    // Returns full ledger + summary for one member
    public function index(Request $request, Member $member): JsonResponse
    {
        $this->authorize('viewAny', ShareCapitalTransaction::class);

        $filters = $request->only(['date_from', 'date_to', 'type']);
        $ledger  = $this->service->getLedger($member, $filters);
        $summary = $this->service->getSummary($member);

        return response()->json([
            'data' => [
                'ledger'  => ShareCapitalResource::collection($ledger),
                'summary' => $summary,
            ],
        ]);
    }

    // POST /api/v1/members/{member}/share-capital
    // Post a new transaction
    public function store(PostTransactionRequest $request, Member $member): JsonResponse
    {
        try {
            $tx = $this->service->post(
                $member,
                $request->validated(),
                $request->resolvedDirection()
            );

            return response()->json([
                'data'    => new ShareCapitalResource($tx),
                'message' => 'Transaction posted.',
                'new_balance' => (float) $member->fresh()->share_capital,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => ['amount' => [$e->getMessage()]],
            ], 422);
        }
    }

    // DELETE /api/v1/share-capital/{transaction}
    // Void a transaction (soft-delete + recompute balance)
    public function destroy(ShareCapitalTransaction $shareCapital): JsonResponse
    {
        $this->authorize('delete', $shareCapital);

        if ($shareCapital->deleted_at) {
            return response()->json(['message' => 'Transaction is already voided.'], 409);
        }

        $this->service->void($shareCapital);

        return response()->json([
            'message'     => 'Transaction voided.',
            'new_balance' => (float) $shareCapital->member->fresh()->share_capital,
        ]);
    }

    // GET /api/v1/members/{member}/share-capital/summary
    public function summary(Member $member): JsonResponse
    {
        $this->authorize('viewAny', ShareCapitalTransaction::class);
        return response()->json([
            'data' => $this->service->getSummary($member),
        ]);
    }

    // GET /api/v1/members/{member}/share-capital/ledger.pdf
    public function pdf(Request $request, Member $member)
    {
        $this->authorize('viewAny', ShareCapitalTransaction::class);
        $filters = $request->only(['date_from', 'date_to']);
        $path    = $this->service->generateLedgerPdf($member, $filters);

        return response()->download(
            $path,
            "ShareCapital_Ledger_{$member->member_no}.pdf",
            ['Content-Type' => 'application/pdf']
        )->deleteFileAfterSend(true);
    }

    // GET /api/v1/share-capital/report
    // Aggregate report across all members
    public function report(Request $request): JsonResponse
    {
        $this->authorize('viewReport', ShareCapitalTransaction::class);

        $filters = $request->only(['company', 'department', 'date_from', 'date_to']);
        $data    = $this->service->getAggregate($filters);

        return response()->json(['data' => $data]);
    }
}
