<?php
// app/Http/Resources/NotificationLogResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'member_id'        => $this->member_id,
            'member_name'      => $this->member?->first_name . ' ' . $this->member?->last_name,
            'event'            => $this->event,
            'event_label'      => $this->event_label,
            'channel'          => $this->channel,
            'recipient'        => $this->recipient,
            'recipient_name'   => $this->recipient_name,
            'message'          => $this->message,
            'status'           => $this->status,
            'status_badge'     => $this->statusBadge(),
            'error_message'    => $this->error_message,
            'sent_at'          => $this->sent_at?->toIso8601String(),
            'failed_at'        => $this->failed_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'reference_type'   => $this->reference_type
                ? class_basename($this->reference_type)
                : null,
            'reference_id'     => $this->reference_id,
        ];
    }

    private function statusBadge(): array
    {
        return match ($this->status) {
            'sent'   => ['label' => 'Sent',   'severity' => 'success'],
            'failed' => ['label' => 'Failed', 'severity' => 'danger'],
            default  => ['label' => 'Queued', 'severity' => 'info'],
        };
    }
}
