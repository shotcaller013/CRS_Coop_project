{{-- resources/views/loan_packet/pages/page2-authority.blade.php --}}
<div class="page">

  <div class="letterhead">
    <div class="lh-name">{{ strtoupper($profile->name) }}</div>
    <div class="lh-addr">{{ $profile->address }} &nbsp;·&nbsp; CDA Reg. No. {{ $profile->cda_reg_no ?? '___________' }}</div>
    <div class="lh-doc">Authority to Deduct</div>
    <div class="lh-ref">Loan No.: <strong>{{ $loan->loan_no }}</strong> &nbsp;·&nbsp; Date: <strong>{{ $today }}</strong></div>
  </div>

  <br>

  {{-- Addressee block --}}
  <p style="font-size:9.5px; line-height:1.6">
    <strong>{{ $member->company ?? 'CRS Holdings Corporations' }}</strong><br>
    Human Resources Department<br>
    {{ $profile->address }}
  </p>

  <p style="margin-top: 14px; font-size: 9.5px"><strong>RE: Authority to Deduct — {{ $loan->loan_no }}</strong></p>

  {{-- Body --}}
  <p class="body-text" style="margin-top:12px">
    I, <strong>{{ strtoupper($member->last_name) }}, {{ $member->first_name }} {{ $member->middle_name ?? '' }}</strong>,
    Employee No. {{ $member->member_no }}, assigned to the <strong>{{ $member->department ?? '_______________' }}</strong> department,
    hereby authorize the <strong>{{ $member->company ?? 'Company' }}</strong> Payroll Section through the
    Human Resources Department to deduct from my salary the amount corresponding to my loan obligation
    with the <strong>{{ $profile->name }}</strong>, the details of which are as follows:
  </p>

  {{-- Loan summary box --}}
  <div class="highlight-box" style="margin: 12px 0;">
    <table style="width:100%; border:none">
      <tr>
        <td style="border:none; padding:3px 0; width:50%"><span class="muted">Loan reference:</span> <strong>{{ $loan->loan_no }}</strong></td>
        <td style="border:none; padding:3px 0"><span class="muted">Loan type:</span> <strong>{{ $loanType?->label ?? '—' }}</strong></td>
      </tr>
      <tr>
        <td style="border:none; padding:3px 0"><span class="muted">Principal amount:</span> <strong>₱{{ number_format($loan->amount, 2) }}</strong></td>
        <td style="border:none; padding:3px 0"><span class="muted">Term:</span> <strong>{{ $loan->term_months }} months</strong></td>
      </tr>
      <tr>
        <td style="border:none; padding:3px 0"><span class="muted">Payment frequency:</span> <strong>{{ $loan->frequency }}</strong></td>
        <td style="border:none; padding:3px 0"><span class="muted">Interest rate:</span> <strong>{{ $loan->annual_rate }}% p.a.</strong></td>
      </tr>
      <tr>
        <td style="border:none; padding:3px 0"><span class="muted">Amount per deduction:</span> <strong style="font-size:11px">₱{{ number_format($first_period?->amount_due ?? 0, 2) }}</strong></td>
        <td style="border:none; padding:3px 0"><span class="muted">Number of deductions:</span> <strong>{{ $n_periods }}</strong></td>
      </tr>
      <tr>
        <td style="border:none; padding:3px 0"><span class="muted">First deduction date:</span> <strong>{{ $first_period?->due_date?->format('F d, Y') ?? '—' }}</strong></td>
        <td style="border:none; padding:3px 0"><span class="muted">Last deduction date:</span> <strong>{{ $last_period?->due_date?->format('F d, Y') ?? '—' }}</strong></td>
      </tr>
    </table>
  </div>

  <p class="body-text">
    This authority shall remain in full force and effect until the total loan obligation has been fully paid.
    I understand and agree that the deduction shall be made from every {{ strtolower($loan->frequency) }} pay period
    commencing on the first deduction date stated above.
  </p>

  <p class="body-text">
    In the event of my separation from the service prior to the full payment of my loan obligation,
    I authorize the Company to deduct the outstanding balance from my final pay, separation pay,
    retirement benefits, or any other amounts due to me.
  </p>

  <p class="body-text">
    I further authorize the {{ $profile->name }} to apply my Share Capital as partial or full payment
    of my outstanding loan obligation should I fail to complete payment within the agreed term.
  </p>

  {{-- Signature blocks --}}
  <div class="sig-row" style="margin-top: 28px">
    <div class="sig-box">
      <div class="sig-line">{{ strtoupper($member->last_name) }}, {{ strtoupper($member->first_name) }}</div>
      <div class="sig-label">Borrower — Signature over Printed Name</div>
      @if($coMaker1)
      <div style="margin-top:16px">
        <div class="sig-line">{{ strtoupper($coMaker1->last_name) }}, {{ strtoupper($coMaker1->first_name) }}</div>
        <div class="sig-label">Co-Maker — Signature over Printed Name</div>
      </div>
      @endif
    </div>
    <div class="sig-box" style="max-width: 180px">
      <div class="sig-line">&nbsp;</div>
      <div class="sig-label">Date</div>
    </div>
  </div>

  <div style="margin-top: 24px; border-top: 0.5px solid #ccc; padding-top: 10px">
    <p style="font-size: 8.5px; font-weight: bold; margin-bottom: 6px;">FOR COMPANY USE</p>
    <div class="sig-row">
      <div class="sig-box">
        <div class="sig-line">{{ $profile->hr_signatory ?? '___________________________' }}</div>
        <div class="sig-label">HR Manager — Received and Noted</div>
      </div>
      <div class="sig-box">
        <div class="sig-line">{{ $profile->coop_signatory ?? '___________________________' }}</div>
        <div class="sig-label">COOP Manager — Approved</div>
      </div>
      <div class="sig-box" style="max-width: 130px">
        <div class="sig-line">&nbsp;</div>
        <div class="sig-label">Date Approved</div>
      </div>
    </div>
  </div>

  <div class="footer">
    <span>{{ $profile->name }} — Authority to Deduct</span>
    <span>Loan No.: {{ $loan->loan_no }} &nbsp;·&nbsp; Page 2 of 5</span>
    <span>Printed by: {{ $printed_by }}</span>
  </div>

</div>
