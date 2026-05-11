{{-- resources/views/loan_packet/pages/page5-disclosure.blade.php --}}
<div class="page" style="page-break-before: always">

  <div class="letterhead">
    <div class="lh-name">{{ strtoupper($profile->name) }}</div>
    <div class="lh-addr">{{ $profile->address }} &nbsp;·&nbsp; CDA Reg. No. {{ $profile->cda_reg_no ?? '___________' }}</div>
    <div class="lh-doc">Disclosure Statement on Loan/Credit Transaction</div>
    <div class="lh-ref">(In compliance with Republic Act No. 3765 — Truth in Lending Act) &nbsp;·&nbsp; Date: <strong>{{ $today }}</strong></div>
  </div>

  <div class="section-title">I. Loan Information</div>
  <div class="info-grid">
    <div class="info-cell"><span class="info-label">Borrower</span><span class="info-value">{{ $member->last_name }}, {{ $member->first_name }} {{ $member->middle_name ?? '' }}</span></div>
    <div class="info-cell"><span class="info-label">Member no.</span><span class="info-value">{{ $member->member_no }}</span></div>
    <div class="info-cell"><span class="info-label">Loan reference no.</span><span class="info-value">{{ $loan->loan_no }}</span></div>
    <div class="info-cell"><span class="info-label">Loan type</span><span class="info-value">{{ $loanType?->label ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Date of application</span><span class="info-value">{{ $loan->application_date ?? $today }}</span></div>
    <div class="info-cell"><span class="info-label">Loan term</span><span class="info-value">{{ $loan->term_months }} months ({{ $n_periods }} {{ strtolower($loan->frequency) }} payments)</span></div>
  </div>

  <div class="section-title light">II. Financial Disclosure</div>

  {{-- The key disclosure table --}}
  <table style="margin-bottom: 8px;">
    <thead><tr><th>Item</th><th class="right">Amount (₱)</th><th>Notes</th></tr></thead>
    <tbody>
      <tr>
        <td><strong>A. Principal Amount of the Loan</strong></td>
        <td class="right bold">{{ number_format($loan->amount, 2) }}</td>
        <td style="font-size:8.5px">Amount actually received by the borrower</td>
      </tr>
      <tr>
        <td><strong>B. Finance Charges (Total Interest)</strong></td>
        <td class="right bold">{{ number_format($total_interest, 2) }}</td>
        <td style="font-size:8.5px">At {{ $loan->annual_rate }}% p.a., diminishing balance</td>
      </tr>
      <tr>
        <td><strong>C. Other Charges</strong></td>
        <td class="right">0.00</td>
        <td style="font-size:8.5px">No processing fee / service charge</td>
      </tr>
      <tr class="total-row">
        <td><strong>D. Total Amount to Be Paid (A + B + C)</strong></td>
        <td class="right bold" style="font-size: 11px;">{{ number_format($total_payment, 2) }}</td>
        <td style="font-size:8.5px">Over the full term of the loan</td>
      </tr>
    </tbody>
  </table>

  <div class="section-title teal">III. Interest Rate Disclosure</div>

  <div class="info-grid" style="margin-bottom: 8px;">
    <div class="info-cell"><span class="info-label">Nominal interest rate</span><span class="info-value">{{ $loan->annual_rate }}% per annum</span></div>
    <div class="info-cell"><span class="info-label">Computation method</span><span class="info-value">Diminishing / Reducing balance</span></div>
    <div class="info-cell"><span class="info-label">Effective interest rate (approx.)</span><span class="info-value">{{ round($loan->annual_rate * 1.1, 2) }}% per annum</span></div>
    <div class="info-cell"><span class="info-label">Interest per period (first)</span><span class="info-value">₱{{ number_format($first_period?->interest ?? 0, 2) }}</span></div>
  </div>

  <div class="section-title">IV. Penalty and Default Charges</div>

  <table style="margin-bottom: 8px;">
    <thead><tr><th>Charge type</th><th>Rate</th><th>Basis</th></tr></thead>
    <tbody>
      <tr>
        <td>Late payment penalty</td>
        <td><strong>{{ $loanType?->penalty_rate ?? 2 }}% per month</strong></td>
        <td>On the unpaid outstanding balance per month or fraction thereof of delay</td>
      </tr>
      <tr>
        <td>Acceleration (full balance due)</td>
        <td>—</td>
        <td>Upon default of any installment, the entire balance becomes immediately due and demandable</td>
      </tr>
      <tr>
        <td>Attorney's fees (if litigated)</td>
        <td>25% of amount due</td>
        <td>Minimum ₱5,000.00, plus costs of suit</td>
      </tr>
    </tbody>
  </table>

  <div class="section-title">V. Payment Schedule Summary</div>
  <div class="info-grid" style="margin-bottom: 8px;">
    <div class="info-cell"><span class="info-label">No. of payment periods</span><span class="info-value">{{ $n_periods }}</span></div>
    <div class="info-cell"><span class="info-label">Payment frequency</span><span class="info-value">{{ $loan->frequency }}</span></div>
    <div class="info-cell"><span class="info-label">First payment date</span><span class="info-value">{{ $first_period?->due_date?->format('F d, Y') ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Last payment date</span><span class="info-value">{{ $last_period?->due_date?->format('F d, Y') ?? '—' }}</span></div>
    <div class="info-cell"><span class="info-label">Regular installment amount</span><span class="info-value bold">₱{{ number_format($first_period?->amount_due ?? 0, 2) }}</span></div>
    <div class="info-cell"><span class="info-label">Last installment amount</span><span class="info-value">₱{{ number_format($last_period?->amount_due ?? 0, 2) }}</span></div>
  </div>

  <div class="section-title">VI. Borrower's Acknowledgment</div>

  <p class="body-text">
    I hereby certify that I have read and fully understood all the terms and conditions stated in this
    Disclosure Statement and in the Loan Application Form, Promissory Note, and Authority to Deduct
    forming part of this loan transaction. I confirm that:
  </p>

  <div style="margin: 8px 0 8px 12px; font-size: 9px; line-height: 1.8;">
    <div><span class="checkbox"></span>I have received a complete copy of this Disclosure Statement.</div>
    <div><span class="checkbox"></span>I understand the total cost of the loan, including finance charges.</div>
    <div><span class="checkbox"></span>I understand the penalty charges for late or missed payments.</div>
    <div><span class="checkbox"></span>I agree to all terms and conditions of this loan transaction.</div>
    <div><span class="checkbox"></span>I authorize payroll deduction as specified in the Authority to Deduct.</div>
  </div>

  <div class="sig-row" style="margin-top: 16px">
    <div class="sig-box">
      <div class="sig-line">{{ strtoupper($member->last_name) }}, {{ strtoupper($member->first_name) }} {{ strtoupper($member->middle_name ?? '') }}</div>
      <div class="sig-label">Borrower — Signature over Printed Name</div>
      <div style="font-size: 8px; color: #555; text-align: center; margin-top: 3px;">Member No. {{ $member->member_no }}</div>
    </div>
    <div class="sig-box" style="max-width: 130px">
      <div class="sig-line">&nbsp;</div>
      <div class="sig-label">Date Signed</div>
    </div>
    <div class="sig-box">
      <div class="sig-line">{{ $profile->coop_signatory ?? '___________________________' }}</div>
      <div class="sig-label">{{ $profile->name }} Representative</div>
    </div>
  </div>

  <div style="margin-top: 12px; padding: 8px 10px; border: 0.5px solid #ccc; border-radius: 3px; font-size: 7.5px; color: #555; line-height: 1.5;">
    <strong>Prepared by:</strong> {{ $printed_by }} &nbsp;&nbsp;|&nbsp;&nbsp;
    <strong>Date prepared:</strong> {{ $today }} &nbsp;&nbsp;|&nbsp;&nbsp;
    This disclosure statement is prepared pursuant to Republic Act No. 3765 (Truth in Lending Act) and Bangko Sentral ng Pilipinas Circular No. 730, Series of 2011.
    The effective interest rate shown is an approximation. For exact computation, refer to the amortization schedule on Page 4.
  </div>

  <div class="footer">
    <span>{{ $profile->name }} — Disclosure Statement (R.A. 3765)</span>
    <span>Loan No.: {{ $loan->loan_no }} &nbsp;·&nbsp; Page 5 of 5</span>
    <span>End of loan packet</span>
  </div>

</div>
