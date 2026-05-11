<?php
// app/Http/Resources/AuditLogResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,

            // Who
            'user_id'          => $this->user_id,
            'user_name'        => $this->user_name,
            'user_role'        => $this->user_role,

            // What was touched
            'auditable_type'   => $this->auditable_type,
            'auditable_short'  => $this->auditable_short_type,
            'auditable_id'     => $this->auditable_id,
            'auditable_label'  => $this->auditable_label,

            // What happened
            'event'            => $this->event,
            'event_badge'      => $this->eventBadge(),
            'old_values'       => $this->old_values,
            'new_values'       => $this->new_values,
            'dirty_keys'       => $this->dirty_keys,
            'diff'             => $this->getDiff(),  // only changed key pairs

            // Context
            'ip_address'       => $this->ip_address,
            'url'              => $this->url,
            'tags'             => $this->tags,
            'created_at'       => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }

    private function eventBadge(): array
    {
        return match ($this->event) {
            'created'  => ['label' => 'Created',  'severity' => 'success'],
            'updated'  => ['label' => 'Updated',  'severity' => 'info'],
            'deleted'  => ['label' => 'Deleted',  'severity' => 'danger'],
            'restored' => ['label' => 'Restored', 'severity' => 'warning'],
            default    => ['label' => ucfirst($this->event), 'severity' => 'secondary'],
        };
    }
}
