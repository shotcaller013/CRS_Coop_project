{{-- resources/views/billing/bill.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9.5px; color: #1A1A2E; margin: 24px; }
  .letterhead { text-align: center; border-bottom: 2px solid #1C3557; padding-bottom: 8px; margin-bottom: 12px; }
  .lh-name { font-size: 13px; font-weight: bold; color: #1C3557; }
  .lh-addr { font-size: 8px; color: #555; margin-top: 2px; }
  .lh-doc  { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; margin-top: 7px; }
  .lh-ref  { font-size: 8.5px; color: #555; margin-top: 3px; }

  .meta-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
  .meta-box { border: 0.5px solid #ccc; border-radius: 3px; padding: 8px 12px; flex: 1; margin-right: 8px; }
  .meta-box:last-child { margin-right: 0; }
  .meta-label { font-size: 7.5px; text-transform: uppercase; color: #666; display: block; }
  .meta-value { font-size: 11px; font-weight: bold; margin-top: 2px; }

  .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .4px;
    background: #1C3557; color: #fff; padding: 3px 8px; margin: 10px 0 4px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #2E5FA3; color: #fff; font-size: 8px; text-transform: uppercase; padding: 4px 7px; text-align: left; }
  td { padding: 4px 7px; border-bottom: 0.5px solid #eee; font-size: 9px; }
  tr:last-child td { border-bottom: none; }
  .total-row td { font-weight: bold; background: #F0EFE8; border-top: 1px solid #aaa; }
  .right { text-align: right; }
  .bold  { font-weight: bold; }
  .muted { color: #666; }

  .status-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
  .status-DRAFT     { background: #f0f0f0; color: #555; }
  .status-ISSUED    { background: #E6F1FB; color: #0C447C; }
  .status-PARTIAL   { background: #FAEEDA; color: #633806; }
  .status-SETTLED   { background: #E8F7F1; color: #085041; }
  .status-CANCELLED { background: #FDEAEA; color: #791F1F; }

  .summary-box { border: 1px solid #1D9E75; background: #E8F7F1; border-radius: 3px; padding: 10px 14px; margin: 10px 0; }
  .summary-row { display: flex; justify-content: space-between; font-size: 9.5px; padding: 3px 0; }
  .summary-row.total { font-weight: bold; font-size: 11px; border-top: 1px solid #1D9E75; margin-top: 5px; padding-top: 5px; }

  .sig-row { display: flex; gap: 20px; margin-top: 20px; }
  .sig-box { flex: 1; }
  .sig-line { border-top: 1px solid #333; margin-top: 26px; padding-top: 4px; font-size: 8px; color: #333; text-align: center; }

  .footer { margin-top: 14px; border-top: 0.5px solid #ccc; padding-top: 4px; font-size: 7.5px; color: #888; display: flex; justify-content: space-between; }

  .remit-section { margin-top: 12px; }
</style>
</head>
<body>

<div class="letterhead">
  <div class="lh-name">{{ strtoupper($profile->name) }}</div>
  <div class="lh-addr">{{ $profile->address }} &nbsp;·&nbsp; CDA Reg. No. {{ $profile->cda_reg_no ?? '___________' }}</div>
  <div class="lh-doc">Payroll Deduction Billing Statement</div>
  <div class="lh-ref">
    Bill No.: <strong>{{ $bill->bill_no }}</strong> &nbsp;·&nbsp;
    Status: <span class="status-badge status-{{ $bill->status }}">{{ $bill->status_label }}</span> &nbsp;·&nbsp;
    Printed: <strong>{{ $printed_at }}</strong>
  </div>
</div>

{{-- Meta boxes --}}
<div class="meta-row">
  <div class="meta-box">
    <span class="meta-label">Company / Employer</span>
    <span class="meta-value">{{ $bill->company->name }}</span>
  </div>
  <div class="meta-box">
    <span class="meta-label">Billing period</span>
    <span class="meta-value">{{ $bill->billing_period_start->format('M d, Y') }} — {{ $bill->billing_period_end->format('M d, Y') }}</span>
  </div>
  <div class="meta-box">
    <span class="meta-label">Prepared by</span>
    <span class="meta-value">{{ $bill->preparedBy?->name ?? $printed_by }}</span>
  </div>
  <div class="meta-box">
    <span class="meta-label">Issued</span>
    <span class="meta-value">{{ $bill->issued_at?->format('M d, Y') ?? '—' }}</span>
  </div>
</div>

{{-- Line items table --}}
<div class="section-title">Loan deductions — {{ $items->count() }} employee{{ $items->count() !== 1 ? 's' : '' }}</div>
<table>
  <thead><tr>
    <th>#</th>
    <th>Employee name</th>
    <th>Member no.</th>
    <th>Loan no.</th>
    <th>Period</th>
    <th>Due date</th>
    <th class="right">Amount (₱)</th>
    <th>Status</th>
  </tr></thead>
  <tbody>
    @foreach($items as $i => $item)
    <tr @if($item->status === 'PAID') style="color:#0F6E56" @endif>
      <td>{{ $i + 1 }}</td>
      <td class="bold">{{ $item->member?->last_name }}, {{ $item->member?->first_name }}</td>
      <td>{{ $item->member?->member_no }}</td>
      <td>{{ $item->loan?->loan_no }}</td>
      <td class="right">{{ $item->schedule?->period_no }}</td>
      <td>{{ $item->schedule?->due_date?->format('d M Y') }}</td>
      <td class="right bold">{{ number_format($item->amount_due, 2) }}</td>
      <td>{{ $item->status }}</td>
    </tr>
    @endforeach
    <tr class="total-row">
      <td colspan="6" class="right">TOTAL PAYROLL DEDUCTION</td>
      <td class="right">{{ number_format($items->sum('amount_due'), 2) }}</td>
      <td></td>
    </tr>
  </tbody>
</table>

{{-- Financial summary --}}
<div class="summary-box">
  <div class="summary-row"><span>Total billed amount</span><span class="bold">₱{{ number_format($bill->total_amount, 2) }}</span></div>
  <div class="summary-row"><span>Amount remitted</span><span>₱{{ number_format($bill->amount_remitted, 2) }}</span></div>
  <div class="summary-row total"><span>Balance outstanding</span><span>₱{{ number_format($bill->balance, 2) }}</span></div>
</div>

{{-- Remittance history (if any) --}}
@if($remittances->isNotEmpty())
<div class="remit-section">
  <div class="section-title" style="background:#2E5FA3">Remittance history</div>
  <table>
    <thead><tr><th>Date</th><th>O.R. Number</th><th class="right">Amount (₱)</th><th>Posted by</th><th>Notes</th></tr></thead>
    <tbody>
      @foreach($remittances as $r)
      <tr>
        <td>{{ $r->remittance_date?->format('d M Y') }}</td>
        <td>{{ $r->or_number ?? '—' }}</td>
        <td class="right bold">{{ number_format($r->amount, 2) }}</td>
        <td>{{ $r->postedBy?->name }}</td>
        <td>{{ $r->notes ?? '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

{{-- Signature block --}}
<div class="sig-row">
  <div class="sig-box">
    <div class="sig-line">{{ $profile->coop_signatory ?? 'COOP Manager' }}<br>Prepared by / {{ $profile->name }}</div>
  </div>
  <div class="sig-box">
    <div class="sig-line">HR Manager / {{ $bill->company->name }}<br>Received and noted</div>
  </div>
  <div class="sig-box">
    <div class="sig-line">Payroll Officer<br>For deduction from payroll</div>
  </div>
</div>

<div class="footer">
  <span>{{ $profile->name }} — Billing Statement {{ $bill->bill_no }}</span>
  <span>Printed by: {{ $printed_by }}</span>
</div>

</body>
</html>
