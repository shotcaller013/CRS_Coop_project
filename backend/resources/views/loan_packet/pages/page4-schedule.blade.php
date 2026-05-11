{{-- resources/views/loan_packet/pages/page4-schedule.blade.php --}}
{{-- DomPDF will split this across multiple pages automatically if it overflows --}}
<div class="page">

  <div class="letterhead">
    <div class="lh-name">{{ strtoupper($profile->name) }}</div>
    <div class="lh-addr">{{ $profile->address }} &nbsp;·&nbsp; CDA Reg. No. {{ $profile->cda_reg_no ?? '___________' }}</div>
    <div class="lh-doc">Loan Amortization Schedule</div>
    <div class="lh-ref">Loan No.: <strong>{{ $loan->loan_no }}</strong> &nbsp;·&nbsp; Date: <strong>{{ $today }}</strong></div>
  </div>

  {{-- Loan summary --}}
  <div class="highlight-box" style="margin-bottom: 8px;">
    <table style="width:100%; border:none">
      <tr>
        <td style="border:none; padding:2px 0; width:25%"><span class="muted">Borrower:</span> <strong>{{ $member->last_name }}, {{ $member->first_name }}</strong></td>
        <td style="border:none; padding:2px 0; width:25%"><span class="muted">Loan type:</span> <strong>{{ $loanType?->label ?? '—' }}</strong></td>
        <td style="border:none; padding:2px 0; width:25%"><span class="muted">Principal:</span> <strong>₱{{ number_format($loan->amount, 2) }}</strong></td>
        <td style="border:none; padding:2px 0; width:25%"><span class="muted">Total payable:</span> <strong>₱{{ number_format($total_payment, 2) }}</strong></td>
      </tr>
      <tr>
        <td style="border:none; padding:2px 0"><span class="muted">Frequency:</span> <strong>{{ $loan->frequency }}</strong></td>
        <td style="border:none; padding:2px 0"><span class="muted">Term:</span> <strong>{{ $loan->term_months }} months</strong></td>
        <td style="border:none; padding:2px 0"><span class="muted">Rate:</span> <strong>{{ $loan->annual_rate }}% p.a.</strong></td>
        <td style="border:none; padding:2px 0"><span class="muted">Total interest:</span> <strong>₱{{ number_format($total_interest, 2) }}</strong></td>
      </tr>
    </table>
  </div>

  {{-- Full schedule table --}}
  <table>
    <thead>
      <tr>
        <th style="width:36px">#</th>
        <th style="width:90px">Due date</th>
        <th class="right" style="width:90px">Principal (₱)</th>
        <th class="right" style="width:84px">Interest (₱)</th>
        <th class="right" style="width:90px">Amount due (₱)</th>
        <th class="right" style="width:90px">Balance (₱)</th>
        <th style="width:50px">O.R. No.</th>
        <th style="width:55px">Date paid</th>
        <th style="width:42px">Initial</th>
      </tr>
    </thead>
    <tbody>
      @foreach($schedule as $period)
      <tr @if($period->status === 'PAID') style="color:#0F6E56" @elseif($period->status === 'OVERDUE') style="color:#A32D2D" @endif>
        <td class="center">{{ $period->period_no }}</td>
        <td>{{ $period->due_date?->format('d M Y') }}</td>
        <td class="right">{{ number_format($period->principal, 2) }}</td>
        <td class="right" style="color:#555">{{ number_format($period->interest, 2) }}</td>
        <td class="right bold">{{ number_format($period->amount_due, 2) }}</td>
        <td class="right">{{ number_format($period->balance, 2) }}</td>
        <td style="font-size:8px">{{ $period->or_number ?? '' }}</td>
        <td style="font-size:8px">{{ $period->paid_date?->format('d M Y') ?? '' }}</td>
        <td>&nbsp;</td>
      </tr>
      @endforeach

      {{-- Totals --}}
      <tr class="total-row">
        <td colspan="2" class="right">TOTALS</td>
        <td class="right">{{ number_format($total_principal, 2) }}</td>
        <td class="right">{{ number_format($total_interest, 2) }}</td>
        <td class="right bold">{{ number_format($total_payment, 2) }}</td>
        <td class="right">&nbsp;</td>
        <td colspan="3">&nbsp;</td>
      </tr>
    </tbody>
  </table>

  {{-- Status legend --}}
  <div style="margin-top: 8px; font-size: 8px; color: #555;">
    <span style="color:#0F6E56">■</span> Paid &nbsp;&nbsp;
    <span style="color:#A32D2D">■</span> Overdue &nbsp;&nbsp;
    <span style="color:#333">■</span> Pending
    &nbsp;&nbsp;·&nbsp;&nbsp;
    Note: Interest computed on diminishing balance. Payment allocated to interest first, then principal.
  </div>

  {{-- Signature --}}
  <div class="sig-row" style="margin-top: 16px">
    <div class="sig-box">
      <div class="sig-line">{{ strtoupper($member->last_name) }}, {{ strtoupper($member->first_name) }}</div>
      <div class="sig-label">Borrower — Received and Confirmed</div>
    </div>
    <div class="sig-box" style="max-width:180px">
      <div class="sig-line">&nbsp;</div>
      <div class="sig-label">Date</div>
    </div>
    <div class="sig-box">
      <div class="sig-line">{{ $profile->coop_signatory ?? '___________________________' }}</div>
      <div class="sig-label">COOP Manager — Prepared by</div>
    </div>
  </div>

  <div class="footer">
    <span>{{ $profile->name }} — Amortization Schedule</span>
    <span>Loan No.: {{ $loan->loan_no }} &nbsp;·&nbsp; Page 4 of 5</span>
    <span>Printed by: {{ $printed_by }} on {{ $today }}</span>
  </div>

</div>
