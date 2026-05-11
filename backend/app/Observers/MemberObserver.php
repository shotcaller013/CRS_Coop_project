<?php
// app/Observers/MemberObserver.php
namespace App\Observers;

use App\Models\Member;
use App\Services\AuditLogService;

class MemberObserver
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function created(Member $member): void
    {
        $this->audit->recordCreated($member);
    }

    public function updated(Member $member): void
    {
        $this->audit->recordUpdated($member);
    }

    public function deleted(Member $member): void
    {
        $this->audit->recordDeleted($member);
    }

    public function restored(Member $member): void
    {
        $this->audit->recordRestored($member);
    }
}
