<?php
// app/Http/Controllers/Api/BillController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bill\CreateBillRequest;
use App\Http\Requests\Bill\UploadRemittanceRequest;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use App\Models\BillRemittance;
use App\Services\BillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BillController extends Controller
{
    public function __construct(private readonly BillService $service) {}

    // GET /api/v1/bills
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Bill::class);
        $bills = $this->service->list($request->only(['company_id','status','date_from','date_to','per_page']));

        return response()->json([
            'data' => BillResource::collection($bills),
            'meta' => [
                'current_page' => $bills->currentPage(),
                'last_page'    => $bills->lastPage(),
                'total'        => $bills->total(),
            ],
        ]);
    }

    // POST /api/v1/bills
    public function store(CreateBillRequest $request): JsonResponse
    {
        try {
            $bill = $this->service->create($request->validated());
            return response()->json([
                'data'    => new BillResource($bill),
                'message' => "Bill {$bill->bill_no} created with {$bill->item_count} line items.",
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // GET /api/v1/bills/{bill}
    public function show(Bill $bill): JsonResponse
    {
        $this->authorize('viewAny', Bill::class);
        $bill->load(['company','preparedBy','items.member','items.loan','items.schedule','remittances.postedBy']);
        return response()->json(['data' => new BillResource($bill)]);
    }

    // POST /api/v1/bills/{bill}/issue
    public function issue(Bill $bill): JsonResponse
    {
        $this->authorize('issue', $bill);
        try {
            $bill = $this->service->issue($bill);
            return response()->json([
                'data'    => new BillResource($bill),
                'message' => "Bill {$bill->bill_no} issued. {$bill->item_count} periods marked as BILLED.",
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // POST /api/v1/bills/{bill}/remittance
    public function uploadRemittance(UploadRemittanceRequest $request, Bill $bill): JsonResponse
    {
        $bill = $this->service->uploadRemittance(
            $bill,
            $request->validated(),
            $request->file('file')
        );

        $msg = $bill->status === 'SETTLED'
            ? "Bill {$bill->bill_no} fully settled."
            : "Remittance recorded. Balance remaining: ₱" . number_format($bill->balance, 2);

        return response()->json([
            'data'    => new BillResource($bill),
            'message' => $msg,
        ]);
    }

    // POST /api/v1/bills/{bill}/settle
    public function settle(Bill $bill): JsonResponse
    {
        $this->authorize('settle', $bill);
        $bill = $this->service->settle($bill);
        return response()->json([
            'data'    => new BillResource($bill),
            'message' => "Bill {$bill->bill_no} marked as settled.",
        ]);
    }

    // POST /api/v1/bills/{bill}/cancel
    public function cancel(Bill $bill): JsonResponse
    {
        $this->authorize('cancel', $bill);
        $bill = $this->service->cancel($bill);
        return response()->json([
            'data'    => new BillResource($bill),
            'message' => "Bill {$bill->bill_no} cancelled. Affected periods reverted to PENDING.",
        ]);
    }

    // GET /api/v1/bills/{bill}/pdf
    public function pdf(Bill $bill)
    {
        $this->authorize('viewAny', Bill::class);
        $path = $this->service->generatePdf($bill);
        return response()->download(
            $path,
            "Bill_{$bill->bill_no}.pdf",
            ['Content-Type' => 'application/pdf']
        )->deleteFileAfterSend(true);
    }

    // GET /api/v1/bills/remittance/{remittance}/file
    public function remittanceFile(BillRemittance $remittance)
    {
        $this->authorize('viewAny', Bill::class);
        if (!$remittance->file_path || !Storage::exists($remittance->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }
        return Storage::download($remittance->file_path);
    }
}
