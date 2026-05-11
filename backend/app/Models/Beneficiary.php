<?php
// app/Models/Beneficiary.php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use SoftDeletes;

    protected $table = 'member_beneficiaries';

    protected $fillable = [
        'member_id', 'type', 'first_name', 'last_name', 'middle_name',
        'relationship', 'birthdate', 'share_percentage',
        'contact_number', 'address',
        'guardian_name', 'guardian_contact', 'guardian_relationship',
        'sort_order',
    ];

    protected $casts = [
        'birthdate'        => 'date',
        'share_percentage' => 'decimal:2',
        'sort_order'       => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    // ── Computed attributes ──────────────────────────────────

    /** Full name */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);
        return implode(' ', $parts);
    }

    /** Age in years; null if no birthdate */
    public function getAgeAttribute(): ?int
    {
        return $this->birthdate ? $this->birthdate->age : null;
    }

    /** True when beneficiary is under 18 */
    public function getIsMinorAttribute(): bool
    {
        return $this->age !== null && $this->age < 18;
    }

    /** True when this is a primary beneficiary */
    public function getIsPrimaryAttribute(): bool
    {
        return $this->type === 'primary';
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopePrimary($query)
    {
        return $query->where('type', 'primary')->orderBy('sort_order');
    }

    public function scopeSecondary($query)
    {
        return $query->where('type', 'secondary')->orderBy('sort_order');
    }
}
