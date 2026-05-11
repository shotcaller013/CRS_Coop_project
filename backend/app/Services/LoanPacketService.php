<?php
// app/Services/LoanPacketService.php
namespace App\Services;

use App\Models\CoopProfile;
use App\Models\Loan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class LoanPacketService
{
    /**
     * Generate the full 5-page loan packet PDF for a given loan.
     * Returns the path to the saved PDF file.
     */
    public function generate(Loan $loan): string
    {
        // Guard — only APPROVED or ACTIVE loans get a packet
        if (!in_array($loan->status, ['APPROVED', 'ACTIVE', 'PENDING'])) {
            throw new \InvalidArgumentException(
                "Loan packet can only be generated for APPROVED, ACTIVE, or PENDING loans. Status: {$loan->status}"
            );
        }

        // Eager-load everything the templates need
        $loan->load([
            'member',
            'loanType',
            'amortizationSchedules' => fn($q) => $q->orderBy('period_no'),
            'coMaker1',
            'coMaker2',
        ]);

        $profile   = CoopProfile::current();
        $schedule  = $loan->amortizationSchedules;
        $today     = now()->format('F d, Y');
        $printedBy = Auth::user()?->name;

        $data = [
            'loan'       => $loan,
            'member'     => $loan->member,
            'loanType'   => $loan->loanType,
            'coMaker1'   => $loan->coMaker1,
            'coMaker2'   => $loan->coMaker2,
            'schedule'   => $schedule,
            'profile'    => $profile,
            'today'      => $today,
            'printed_by' => $printedBy,

            // Pre-computed totals for templates
            'total_payment'  => $schedule->sum('amount_due'),
            'total_principal'=> $schedule->sum('principal'),
            'total_interest' => $schedule->sum('interest'),
            'first_period'   => $schedule->first(),
            'last_period'    => $schedule->last(),
            'n_periods'      => $schedule->count(),
        ];

        $html = view('loan_packet.packet', $data)->render();

        $pdf = Pdf::loadHtml($html)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $filename = "LoanPacket_{$loan->loan_no}_" . now()->format('Ymd_His') . '.pdf';
        $path     = storage_path("app/exports/{$filename}");
        @mkdir(dirname($path), 0755, true);
        $pdf->save($path);

        return $path;
    }
}
