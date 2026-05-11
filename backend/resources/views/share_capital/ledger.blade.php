{{-- resources/views/share_capital/ledger.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1C1F26; margin: 24px; }
  .letterhead { text-align: center; border-bottom: 2px solid #1C3557; padding-bottom: 8px; margin-bottom: 12px; }
  .letterhead h1 { font-size: 12px; font-weight: bold; margin: 0 0 2px; color: #1C3557; }
  .letterhead p  { font-size: 8px; color: #555; margin: 0; }
  .letterhead h2 { font-size: 13px; font-weight: bold; margin: 8px 0 2px; text-transform: uppercase; }
  .member-box { border: 0.5px solid #ccc; border-radius: 4px; padding: 10px 14px; margin-bottom: 12px; display: flex; gap: 24px; flex-wrap: wrap; }
  .mf-label { font-size: 7.5px; text-transform: uppercase; color: #888; display: block; }
  .mf-value { font-size: 10px; font-weight: bold; color: #1C1F26; }
  .summary-row { display: flex; gap: 10px; margin-bottom: 12px; }
  .stat { background: #f5f5f0; border: 0.5px solid #ddd; border-radius: 4px; padding: 8px 12px; flex: 1; }
  .stat-label { font-size: 7px; text-transform: uppercase; color: #666; }
  .stat-val   { font-size: 14px; font-weight: bold; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #1C3557; color: #fff; font-size: 7.5px; text-transform: uppercase; padding: 4px 8px; text-align: left; }
  td { padding: 4px 8px; border-bottom: 0.5px solid #eee; font-size: 8.5px; }
  tr:last-child td { border-bottom: none; }
  .credit { color: #0F6E56; }
  .debit  { color: #A32D2D; }
  .voided { opacity: .5; text-decoration: line-through; }
  .balance-col { font-weight: bold; text-align: right; }
  .right { text-align: right; }
  .footer { margin-top: 16px; font-size: 7px; color: #888; border-top: 0.5px solid #ccc; padding-top: 5px; display:flex; justify-content:space-between; }
  .sig-block { display:flex; justify-content:space-between; margin-top:28px; }
  .sig-line { text-align:center; width:180px; }
  .sig-line .line { border-top:1px solid #333; padding-top:4px; font-size:8px; color:#555; margin-top:24px; }
</style>
</head>
<body>

<div class="letterhead">
  <h1>{{ strtoupper($profile->name) }}</h1>
  <p>{{ $profile->address }} &nbsp;·&nbsp; CDA Reg. No. {{ $profile->cda_reg_no ?? '___' }}</p>
  <h2>Share Capital Ledger Statement</h2>
</div>

<div class="member-box">
  <div><span class="mf-label">Member no.</span><span class="mf-value">{{ $member->member_no }}</span></div>
  <div><span class="mf-label">Member name</span><span class="mf-value">{{ $member->last_name }}, {{ $member->first_name }}</span></div>
  <div><span class="mf-label">Department</span><span class="mf-value">{{ $member->department ?? '—' }}</span></div>
  <div><span class="mf-label">Company</span><span class="mf-value">{{ $member->company ?? '—' }}</span></div>
  @if(!empty($filters['date_from']) || !empty($filters['date_to']))
  <div><span class="mf-label">Period</span><span class="mf-value">{{ $filters['date_from'] ?? 'All' }} to {{ $filters['date_to'] ?? 'Present' }}</span></div>
  @endif
  <div><span class="mf-label">Printed</span><span class="mf-value">{{ $printed_at }}</span></div>
</div>

<div class="summary-row">
  <div class="stat"><div class="stat-label">Current balance</div><div class="stat-val">₱{{ number_format($summary['current_balance'],2) }}</div></div>
  <div class="stat"><div class="stat-label">Total credits</div><div class="stat-val credit">₱{{ number_format($summary['total_credits'],2) }}</div></div>
  <div class="stat"><div class="stat-label">Total debits</div><div class="stat-val debit">₱{{ number_format($summary['total_debits'],2) }}</div></div>
  <div class="stat"><div class="stat-label">Transactions</div><div class="stat-val">{{ $summary['transaction_count'] }}</div></div>
</div>

<table>
  <thead><tr>
    <th>Date</th><th>Type</th><th>O.R. #</th><th>Remarks</th>
    <th class="right">Debit (₱)</th>
    <th class="right">Credit (₱)</th>
    <th class="right">Balance (₱)</th>
  </tr></thead>
  <tbody>
    @foreach($ledger as $tx)
    <tr class="{{ $tx->deleted_at ? 'voided' : '' }}">
      <td>{{ $tx->transaction_date?->format('d M Y') }}</td>
      <td>{{ $tx->type_label }}{{ $tx->deleted_at ? ' [VOIDED]' : '' }}</td>
      <td>{{ $tx->or_number ?? '—' }}</td>
      <td>{{ $tx->remarks ?? '—' }}</td>
      <td class="right debit">{{ $tx->direction === 'debit' ? number_format($tx->amount,2) : '—' }}</td>
      <td class="right credit">{{ $tx->direction === 'credit' ? number_format($tx->amount,2) : '—' }}</td>
      <td class="balance-col">{{ number_format($tx->balance_after,2) }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<div class="sig-block">
  <div class="sig-line">
    <div class="line">{{ $printed_by }}<br>Prepared by</div>
  </div>
  <div class="sig-line">
    <div class="line">{{ $profile->coop_signatory ?? 'COOP Manager' }}<br>Verified by</div>
  </div>
  <div class="sig-line">
    <div class="line">{{ strtoupper($member->last_name) }}, {{ strtoupper($member->first_name) }}<br>Member</div>
  </div>
</div>

<div class="footer">
  <span>Printed by: {{ $printed_by }}</span>
  <span>{{ $profile->name }} — Share Capital Ledger</span>
</div>

</body>
</html>
