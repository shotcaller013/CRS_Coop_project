<?php
// app/Observers/CoopProfileObserver.php
namespace App\Observers;

use App\Models\CoopProfile;
use App\Services\AuditLogService;

class CoopProfileObserver
{
    public function __construct(private readonly AuditLogService $audit) {}

    // CoopProfile is a single-row config — only updated events matter in practice
    public function updated(CoopProfile $profile): void
    {
        $this->audit->recordUpdated($profile);
    }

    // Log creation in case of fresh installs
    public function created(CoopProfile $profile): void
    {
        $this->audit->recordCreated($profile);
    }
}
