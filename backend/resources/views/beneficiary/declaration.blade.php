{{-- resources/views/beneficiary/declaration.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1C1F26; margin: 24px; }
  .letterhead { text-align: center; border-bottom: 2px solid #1C3557; padding-bottom: 8px; margin-bottom: 12px; }
  .letterhead h1 { font-size: 12px; font-weight: bold; margin: 0 0 2px; color: #1C3557; }
  .letterhead p  { font-size: 8px; color: #555; margin: 0; }
  .letterhead h2 { font-size: 13px; font-weight: bold; margin: 8px 0 2px; text-transform: uppercase; letter-spacing: .5px; }
  .member-box { border: 0.5px solid #ccc; border-radius: 4px; padding: 10px 14px; margin-bottom: 14px; display: flex; justify-content: space-between; }
  .member-field { display: inline-block; }
  .field-label { font-size: 7.5px; text-transform: uppercase; color: #888; display: block; }
  .field-value { font-size: 10px; font-weight: bold; color: #1C1F26; }
  .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; color: #fff; background: #1C3557; padding: 4px 8px; margin: 10px 0 0; }
  .section-title.secondary { background: #2E5FA3; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #E8EEF5; font-size: 7.5px; text-transform: uppercase; letter-spacing: .3px; padding: 4px 8px; text-align: left; color: #333; }
  td { padding: 5px 8px; border-bottom: 0.5px solid #eee; font-size: 8.5px; }
  tr:last-child td { border-bottom: none; }
  .share { font-weight: bold; color: #1C3557; }
  .minor-note { font-size: 7.5px; color: #854F0B; font-style: italic; }
  .total-row td { font-weight: bold; background: #F0F0EA; border-top: 1px solid #aaa; }
  .status-bar { margin: 14px 0 10px; padding: 8px 12px; border-radius: 4px; font-size: 8.5px; }
  .status-ok  { background: #E8F7F1; border: 0.5px solid #1D9E75; color: #0F6E56; }
  .status-err { background: #FDEAEA; border: 0.5px solid #C0392B; color: #8B1F1F; }
  .sig-block { display: flex; justify-content: space-between; margin-top: 28px; }
  .sig-line { text-align: center; width: 210px; }
  .sig-line .line { border-top: 1px solid #333; padding-top: 4px; font-size: 8px; color: #555; margin-top: 24px; }
  .footer { margin-top: 16px; font-size: 7px; color: #888; border-top: 0.5px solid #ccc; padding-top: 5px; display:flex; justify-content:space-between; }
</style>
</head>
<body>

<div class="letterhead">
  <h1>{{ strtoupper($profile->name) }}</h1>
  <p>{{ $profile->address }} &nbsp;·&nbsp; CDA Reg. No. {{ $profile->cda_reg_no ?? '___' }}</p>
  <h2>Beneficiary Declaration Form</h2>
</div>

<div class="member-box">
  <div class="member-field">
    <span class="field-label">Member no.</span>
    <span class="field-value">{{ $member->member_no }}</span>
  </div>
  <div class="member-field">
    <span class="field-label">Member name</span>
    <span class="field-value">{{ $member->last_name }}, {{ $member->first_name }} {{ $member->middle_name }}</span>
  </div>
  <div class="member-field">
    <span class="field-label">Department</span>
    <span class="field-value">{{ $member->department ?? '—' }}</span>
  </div>
  <div class="member-field">
    <span class="field-label">Company</span>
    <span class="field-value">{{ $member->company ?? '—' }}</span>
  </div>
  <div class="member-field">
    <span class="field-label">Date printed</span>
    <span class="field-value">{{ $printed_at }}</span>
  </div>
</div>

{{-- Compliance status --}}
@if($status['is_complete'])
<div class="status-bar status-ok">Beneficiary declaration is complete and CDA-compliant. Total primary share: {{ $status['total_share'] }}%</div>
@else
<div class="status-bar status-err">
  Declaration is incomplete: {{ implode(' · ', $status['issues']) }}
</div>
@endif

{{-- PRIMARY BENEFICIARIES --}}
<div class="section-title">Primary beneficiaries</div>
@if($primary->isEmpty())
  <p style="font-size:8.5px;color:#A32D2D;padding:6px 8px;">No primary beneficiary declared.</p>
@else
<table>
  <thead><tr>
    <th>#</th><th>Full name</th><th>Relationship</th>
    <th>Birthdate</th><th>Age</th>
    <th>Contact</th><th>Share %</th>
  </tr></thead>
  <tbody>
    @foreach($primary as $i => $b)
    <tr>
      <td>{{ $i + 1 }}</td>
      <td>
        {{ $b->full_name }}
        @if($b->is_minor)
          <div class="minor-note">Minor — Guardian: {{ $b->guardian_name ?? 'NOT DECLARED' }} ({{ $b->guardian_relationship }}) {{ $b->guardian_contact }}</div>
        @endif
      </td>
      <td>{{ $b->relationship }}</td>
      <td>{{ $b->birthdate?->format('d M Y') ?? '—' }}</td>
      <td>{{ $b->age ?? '—' }}</td>
      <td>{{ $b->contact_number ?? '—' }}</td>
      <td class="share">{{ number_format($b->share_percentage, 2) }}%</td>
    </tr>
    @endforeach
    <tr class="total-row">
      <td colspan="6" style="text-align:right">Total share</td>
      <td class="share">{{ number_format($primary->sum('share_percentage'), 2) }}%</td>
    </tr>
  </tbody>
</table>
@endif

{{-- SECONDARY BENEFICIARIES --}}
@if($secondary->isNotEmpty())
<div class="section-title secondary" style="margin-top:14px">Secondary (contingent) beneficiaries</div>
<table>
  <thead><tr>
    <th>#</th><th>Full name</th><th>Relationship</th>
    <th>Birthdate</th><th>Age</th><th>Contact</th>
  </tr></thead>
  <tbody>
    @foreach($secondary as $i => $b)
    <tr>
      <td>{{ $i + 1 }}</td>
      <td>
        {{ $b->full_name }}
        @if($b->is_minor)
          <div class="minor-note">Minor — Guardian: {{ $b->guardian_name ?? 'NOT DECLARED' }}</div>
        @endif
      </td>
      <td>{{ $b->relationship }}</td>
      <td>{{ $b->birthdate?->format('d M Y') ?? '—' }}</td>
      <td>{{ $b->age ?? '—' }}</td>
      <td>{{ $b->contact_number ?? '—' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- Signature block --}}
<div class="sig-block">
  <div class="sig-line">
    <div class="line">{{ strtoupper($member->last_name) }}, {{ strtoupper($member->first_name) }}<br>Member signature over printed name</div>
  </div>
  <div class="sig-line">
    <div class="line">Date signed</div>
  </div>
  <div class="sig-line">
    <div class="line">{{ $profile->coop_signatory ?? 'COOP Manager' }}<br>Received by</div>
  </div>
</div>

<div class="footer">
  <span>Printed by: {{ $printed_by }}</span>
  <span>CDA compliance form — {{ $profile->name }}</span>
</div>

</body>
</html>
