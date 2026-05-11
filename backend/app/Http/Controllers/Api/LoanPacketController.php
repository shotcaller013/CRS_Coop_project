<?php
// app/Http/Controllers/Api/LoanPacketController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\LoanPacketService;
use Illuminate\Http\Request;

class LoanPacketController extends Controller
{
    public function __construct(
        private readonly LoanPacketService $service
    ) {}

    // GET /api/v1/loans/{loan}/packet.pdf
    public function download(Loan $loan)
    {
        $this->authorize('view', $loan);

        try {
            $path = $this->service->generate($loan);

            return response()->download(
                $path,
                "LoanPacket_{$loan->loan_no}.pdf",
                ['Content-Type' => 'application/pdf']
            )->deleteFileAfterSend(true);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
