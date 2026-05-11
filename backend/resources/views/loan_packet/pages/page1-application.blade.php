{{-- resources/views/loan_packet/pages/page1-application.blade.php --}}
<div class="page">

  {{-- Letterhead --}}
  <div class="letterhead">
    <div class="lh-name">{{ strtoupper($profile->name) }}</div>
    <div class="lh-addr">{{ $profile->address }}  &nbsp;·&nbsp;  CDA Reg. No. {{ $profile->cda_reg_no ?? '___________' }}</div>
    <div class="lh-doc">Loan Application Form</div>
    <div class="lh-ref">Loan No.: <strong>{{ $loan->loan_no }}</strong> &nbsp;·&nbsp; Date: <strong>{{ $today }}</strong> &nbsp;·&nbsp; Loan Type: <strong>{{ $loanType?->label ?? '—' }}</strong></div>
  </div>

  {{-- SECTION A — BORROWER INFORMATION --}}
  <div class="section-title">A. Borrower Information</div>
  <div class="info-grid">
    <div class="info-cell"><span class="info-label">Last name</span><span class="info-value">{{ $member->last_name }}</span></div>
    <div class="info-cell"><span class="info-label">First name</span><span class="info-value">{{ $member->first_name }}</span></div>
    <div class="info-cell"><span class="info-label">Middle name</span><span class="info-value">{{ $member->middle_name ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Member no.</span><span class="info-value">{{ $member->member_no }}</span></div>
    <div class="info-cell"><span class="info-label">Date of birth</span><span class="info-value">{{ $member->birthdate ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Civil status</span><span class="info-value">{{ $member->civil_status ?? '—' }}</span></div>
    <div class="info-cell full"><span class="info-label">Permanent address</span><span class="info-value">{{ $member->address ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Mobile number</span><span class="info-value">{{ $member->contact_number ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Email address</span><span class="info-value">{{ $member->email ?? '—' }}</span></div>
  </div>

  {{-- SECTION B — EMPLOYMENT DETAILS --}}
  <div class="section-title light">B. Employment Details</div>
  <div class="info-grid">
    <div class="info-cell"><span class="info-label">Company / Employer</span><span class="info-value">{{ $member->company ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Department</span><span class="info-value">{{ $member->department ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Employment status</span><span class="info-value">{{ $member->status ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Date hired</span><span class="info-value">{{ $member->date_hired ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Monthly salary</span><span class="info-value">₱{{ number_format($member->monthly_salary ?? 0, 2) }}</span></div>
    <div class="info-cell"><span class="info-label">Share capital</span><span class="info-value">₱{{ number_format($member->share_capital ?? 0, 2) }}</span></div>
  </div>

  {{-- SECTION C — LOAN DETAILS --}}
  <div class="section-title teal">C. Loan Details</div>
  <div class="info-grid">
    <div class="info-cell"><span class="info-label">Amount applied for</span><span class="info-value bold" style="font-size:12px">₱{{ number_format($loan->amount, 2) }}</span></div>
    <div class="info-cell"><span class="info-label">Loan type</span><span class="info-value">{{ $loanType?->label ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Term</span><span class="info-value">{{ $loan->term_months }} months</span></div>
    <div class="info-cell"><span class="info-label">Payment frequency</span><span class="info-value">{{ $loan->frequency }}</span></div>
    <div class="info-cell"><span class="info-label">Annual interest rate</span><span class="info-value">{{ $loan->annual_rate }}% per annum</span></div>
    <div class="info-cell"><span class="info-label">No. of payment periods</span><span class="info-value">{{ $n_periods }}</span></div>
    <div class="info-cell"><span class="info-label">First due date</span><span class="info-value">{{ $first_period?->due_date?->format('F d, Y') ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Last due date</span><span class="info-value">{{ $last_period?->due_date?->format('F d, Y') ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">First payment amount</span><span class="info-value">₱{{ number_format($first_period?->amount_due ?? 0, 2) }}</span></div>
    <div class="info-cell"><span class="info-label">Total amount to be paid</span><span class="info-value bold">₱{{ number_format($total_payment, 2) }}</span></div>
    <div class="info-cell full"><span class="info-label">Purpose of loan</span><span class="info-value">{{ $loan->purpose ?? '—' }}</span></div>
  </div>

  {{-- SECTION D — CO-MAKER --}}
  @if($coMaker1)
  <div class="section-title">D. Co-Maker</div>
  <div class="info-grid">
    <div class="info-cell"><span class="info-label">Co-maker name</span><span class="info-value">{{ $coMaker1->last_name }}, {{ $coMaker1->first_name }}</span></div>
    <div class="info-cell"><span class="info-label">Member no.</span><span class="info-value">{{ $coMaker1->member_no }}</span></div>
    <div class="info-cell"><span class="info-label">Contact number</span><span class="info-value">{{ $coMaker1->contact_number ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Department</span><span class="info-value">{{ $coMaker1->department ?? '—' }}</span></div>
  </div>
  @endif

  {{-- SECTION E — DECLARATION --}}
  <div class="section-title">E. Member Declaration</div>
  <p class="body-text">
    I hereby certify that all information provided in this application is true and correct to the best of my knowledge.
    I authorize the {{ $profile->name }} to verify any information herein and to check my credit standing.
    I agree to abide by all the terms and conditions governing this loan and the rules and regulations of the cooperative.
  </p>

  <div class="sig-row">
    <div class="sig-box">
      <div class="sig-line">{{ strtoupper($member->last_name) }}, {{ strtoupper($member->first_name) }}</div>
      <div class="sig-label">Borrower — Signature over Printed Name</div>
    </div>
    <div class="sig-box">
      <div class="sig-line">&nbsp;</div>
      <div class="sig-label">Date signed</div>
    </div>
    <div class="sig-box">
      <div class="sig-line">{{ $profile->hr_signatory ?? '___________________' }}</div>
      <div class="sig-label">HR Manager — Noted</div>
    </div>
    <div class="sig-box">
      <div class="sig-line">{{ $profile->coop_signatory ?? '___________________' }}</div>
      <div class="sig-label">COOP Manager — Approved</div>
    </div>
  </div>

  <div class="footer">
    <span>{{ $profile->name }} — Loan Application Form</span>
    <span>Loan No.: {{ $loan->loan_no }} &nbsp;·&nbsp; Page 1 of 5</span>
    <span>Printed by: {{ $printed_by }}</span>
  </div>

</div>
