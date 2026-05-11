<?php
// app/Services/BeneficiaryService.php
namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Member;
use App\Models\CoopProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BeneficiaryService
{
    // ── CRUD ─────────────────────────────────────────────────

    public function store(array $data): Beneficiary
    {
        // Auto-assign sort_order if not provided
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = Beneficiary::where('member_id', $data['member_id'])
                ->where('type', $data['type'])
                ->withTrashed()
                ->max('sort_order') + 1;
        }

        return Beneficiary::create($data);
    }

    public function update(Beneficiary $beneficiary, array $data): Beneficiary
    {
        $beneficiary->update($data);
        return $beneficiary->fresh();
    }

    public function delete(Beneficiary $beneficiary): void
    {
        $beneficiary->delete();
    }

    // ── Reorder ───────────────────────────────────────────────

    /**
     * Accepts: [{ id: 1, sort_order: 0 }, { id: 2, sort_order: 1 }, ...]
     */
    public function reorder(int $memberId, array $items): void
    {
        DB::transaction(function () use ($memberId, $items) {
            foreach ($items as $item) {
                Beneficiary::where('id', $item['id'])
                    ->where('member_id', $memberId)
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });
    }

    // ── Validation helpers ────────────────────────────────────

    /**
     * Returns a structured completion status for the member's beneficiaries.
     * Used by BeneficiaryStatus.vue and the compliance check endpoint.
     */
    public function getCompletionStatus(Member $member): array
    {
        $primary   = $member->primaryBeneficiaries()->withoutTrashed()->get();
        $secondary = $member->secondaryBeneficiaries()->withoutTrashed()->get();

        $issues = [];

        if ($primary->isEmpty()) {
            $issues[] = 'No primary beneficiary declared. At least one is required.';
        } else {
            $totalShare = $primary->sum('share_percentage');
            if (round((float) $totalShare, 2) !== 100.00) {
                $issues[] = sprintf(
                    'Primary beneficiary shares total %.2f%% — must equal 100%%.',
                    $totalShare
                );
            }

            foreach ($primary as $b) {
                if ($b->is_minor && empty($b->guardian_name)) {
                    $issues[] = "{$b->full_name} is a minor — guardian information is required.";
                }
            }
        }

        return [
            'is_complete'      => empty($issues),
            'issues'           => $issues,
            'primary_count'    => $primary->count(),
            'secondary_count'  => $secondary->count(),
            'total_share'      => round((float) $primary->sum('share_percentage'), 2),
        ];
    }

    /**
     * Validate that primary shares still sum to ≤ 100 after adding/updating.
     * Returns the remaining available share.
     */
    public function getRemainingShare(int $memberId, ?int $excludeId = null): float
    {
        $query = Beneficiary::where('member_id', $memberId)
            ->where('type', 'primary')
            ->withoutTrashed();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $used = (float) $query->sum('share_percentage');
        return round(100.0 - $used, 2);
    }

    // ── PDF Declaration ───────────────────────────────────────

    public function generateDeclarationPdf(Member $member): string
    {
        $member->load([
            'primaryBeneficiaries'   => fn($q) => $q->withoutTrashed(),
            'secondaryBeneficiaries' => fn($q) => $q->withoutTrashed(),
        ]);

        $profile = CoopProfile::current();
        $status  = $this->getCompletionStatus($member);

        $pdf = Pdf::loadView('beneficiary.declaration', [
            'member'    => $member,
            'profile'   => $profile,
            'primary'   => $member->primaryBeneficiaries,
            'secondary' => $member->secondaryBeneficiaries,
            'status'    => $status,
            'printed_by'=> Auth::user()?->name,
            'printed_at'=> now()->format('F d, Y  H:i'),
        ])->setPaper('a4', 'portrait');

        $path = storage_path('app/exports/beneficiary_' . $member->member_no . '_' . now()->format('Ymd_His') . '.pdf');
        @mkdir(dirname($path), 0755, true);
        $pdf->save($path);
        return $path;
    }
}
