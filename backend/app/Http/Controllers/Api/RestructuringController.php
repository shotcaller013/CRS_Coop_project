<?php
// app/Http/Controllers/Api/RestructuringController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Restructuring\PreviewRestructuringRequest;
use App\Http\Requests\Restructuring\RestructureRequest;
use App\Http\Resources\LoanRestructuringResource;
use App\Models\Loan;
use App\Models\LoanRestructuring;
use App\Services\RestructuringService;
use Illuminate\Http\JsonResponse;

class RestructuringController extends Controller
{
    public function __construct(
        private readonly RestructuringService $service
    ) {}

    // GET /api/v1/loans/{loan}/restructurings
    // List all restructuring records for a loan
    public function index(Loan $loan): JsonResponse
    {
        $this->authorize('viewAny', [LoanRestructuring::class, $loan]);

        $records = $loan->restructurings()->get();

        return response()->json([
            'data' => LoanRestructuringResource::collection($records),
            'meta' => [
                'restructuring_count' => $records->count(),
                'is_restructured'     => $records->isNotEmpty(),
                'remaining_balance'   => round($loan->remaining_balance, 2),
                'total_penalty'       => round($loan->total_penalty_outstanding, 2),
                'can_restructure'     => $loan->status === 'ACTIVE' && $loan->remaining_balance > 0,
            ],
        ]);
    }

    // POST /api/v1/loans/{loan}/restructurings/preview
    // Dry-run — returns new schedule without saving
    public function preview(PreviewRestructuringRequest $request, Loan $loan): JsonResponse
    {
        try {
            $result = $this->service->preview($loan, $request->validated());
            return response()->json(['data' => $result]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'can_restructure' => false,
            ], 422);
        }
    }

    // POST /api/v1/loans/{loan}/restructurings
    // Execute the restructuring
    public function store(RestructureRequest $request, Loan $loan): JsonResponse
    {
        try {
            $record = $this->service->execute($loan, $request->validated());

            return response()->json([
                'data'    => new LoanRestructuringResource($record),
                'message' => "Loan restructured successfully. Reference: {$record->restructuring_no}",
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // GET /api/v1/loans/{loan}/restructurings/{restructuring}
    public function show(Loan $loan, LoanRestructuring $restructuring): JsonResponse
    {
        $this->authorize('viewAny', [LoanRestructuring::class, $loan]);
        return response()->json(['data' => new LoanRestructuringResource($restructuring)]);
    }
}
