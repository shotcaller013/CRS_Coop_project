{{-- resources/views/loan_packet/pages/page3-promissory.blade.php --}}
<div class="page">

  <div class="letterhead">
    <div class="lh-name">{{ strtoupper($profile->name) }}</div>
    <div class="lh-addr">{{ $profile->address }} &nbsp;·&nbsp; CDA Reg. No. {{ $profile->cda_reg_no ?? '___________' }}</div>
    <div class="lh-doc">Promissory Note</div>
    <div class="lh-ref">No.: <strong>{{ $loan->loan_no }}</strong> &nbsp;·&nbsp; Date: <strong>{{ $today }}</strong></div>
  </div>

  <p style="font-size: 9.5px; line-height: 1.5; margin: 10px 0 6px;">
    FOR VALUE RECEIVED, I/We, the undersigned, jointly and severally promise to pay to
    the <strong>{{ strtoupper($profile->name) }}</strong>, or its order, the principal sum of:
  </p>

  {{-- Principal amount prominent --}}
  <div style="text-align:center; border: 1px solid #1C3557; border-radius: 4px; padding: 10px; margin: 8px 0;">
    <div style="font-size: 18px; font-weight: bold; color: #1C3557;">₱{{ number_format($loan->amount, 2) }}</div>
    <div style="font-size: 8.5px; color: #555; margin-top: 3px;">
      {{ strtoupper('' /* TODO: number_to_words($loan->amount) */) }}PHILIPPINE PESOS
    </div>
  </div>

  <p class="body-text">
    payable in <strong>{{ $n_periods }} {{ strtolower($loan->frequency) }}</strong> installments
    of <strong>₱{{ number_format($first_period?->amount_due ?? 0, 2) }}</strong> per period
    (except the last installment of <strong>₱{{ number_format($last_period?->amount_due ?? 0, 2) }}</strong>),
    with interest at the rate of <strong>{{ $loan->annual_rate }}%</strong> per annum computed on the
    diminishing balance, the first installment to be due and payable on
    <strong>{{ $first_period?->due_date?->format('F d, Y') ?? '_______________' }}</strong>,
    and each subsequent installment on the same day of each succeeding
    {{ $loan->frequency === 'MONTHLY' ? 'month' : ($loan->frequency === 'WEEKLY' ? 'week' : '15-day period') }}
    thereafter, until fully paid.
  </p>

  <p class="body-text">
    <strong>Penalty Clause:</strong>
    In the event of default in the payment of any installment when due, a penalty charge of
    <strong>{{ $loanType?->penalty_rate ?? 2 }}%</strong> per month shall be imposed on the
    unpaid outstanding balance for each month or fraction thereof of delay.
  </p>

  <p class="body-text">
    <strong>Acceleration Clause:</strong>
    Should I/we fail to pay any installment when due, the entire unpaid balance of this note,
    together with accrued interest and penalties, shall immediately become due and payable at
    the option of the {{ $profile->name }}, without notice or demand.
  </p>

  <p class="body-text">
    <strong>Attorney's Fees:</strong>
    In case of judicial or extra-judicial action to enforce payment of this note, I/we agree to
    pay attorney's fees equivalent to twenty-five percent (25%) of the total amount due,
    but not less than Five Thousand Pesos (₱5,000.00), plus costs of suit.
  </p>

  <p class="body-text">
    <strong>Share Capital:</strong>
    I/we further authorize the cooperative to apply my/our share capital contribution as partial
    or full payment of the outstanding obligation should the loan remain unpaid at maturity.
  </p>

  <p class="body-text">
    This note is executed at {{ $profile->address ?? 'Mandaue City, Cebu' }},
    Philippines, this <strong>{{ $today }}</strong>.
  </p>

  {{-- Signatures --}}
  <div class="sig-row" style="margin-top: 20px">
    <div class="sig-box">
      <div class="sig-line">{{ strtoupper($member->last_name) }}, {{ strtoupper($member->first_name) }} {{ strtoupper($member->middle_name ?? '') }}</div>
      <div class="sig-label">Principal Borrower — Signature over Printed Name</div>
      <div style="font-size: 8px; color: #555; text-align: center; margin-top: 3px;">Member No. {{ $member->member_no }}</div>
    </div>
    @if($coMaker1)
    <div class="sig-box">
      <div class="sig-line">{{ strtoupper($coMaker1->last_name) }}, {{ strtoupper($coMaker1->first_name) }}</div>
      <div class="sig-label">Co-Maker — Signature over Printed Name</div>
      <div style="font-size: 8px; color: #555; text-align: center; margin-top: 3px;">Member No. {{ $coMaker1->member_no }}</div>
    </div>
    @endif
    @if($coMaker2)
    <div class="sig-box">
      <div class="sig-line">{{ strtoupper($coMaker2->last_name) }}, {{ strtoupper($coMaker2->first_name) }}</div>
      <div class="sig-label">Co-Maker 2 — Signature over Printed Name</div>
    </div>
    @endif
  </div>

  {{-- Witness / Notarial block --}}
  <div style="margin-top: 20px; border-top: 0.5px solid #ccc; padding-top: 8px;">
    <p style="font-size: 8.5px; font-weight: bold; margin-bottom: 8px;">SIGNED IN THE PRESENCE OF:</p>
    <div class="sig-row">
      <div class="sig-box">
        <div class="sig-line">&nbsp;</div>
        <div class="sig-label">Witness</div>
      </div>
      <div class="sig-box">
        <div class="sig-line">{{ $profile->coop_signatory ?? '___________________________' }}</div>
        <div class="sig-label">{{ $profile->name }} — COOP Manager</div>
      </div>
    </div>
  </div>

  {{-- Notarial acknowledgment --}}
  <div style="margin-top: 14px; border: 0.5px solid #ccc; padding: 8px 10px; font-size: 8.5px; line-height: 1.6;">
    <strong>ACKNOWLEDGMENT</strong><br><br>
    REPUBLIC OF THE PHILIPPINES )<br>
    CITY OF MANDAUE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ) S.S.<br>
    PROVINCE OF CEBU &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )<br><br>
    BEFORE ME, a Notary Public for and in the City of Mandaue, this _______ day of _______________, 20____,
    personally appeared the above-named person(s) known to me to be the same person(s) who executed the
    foregoing instrument and acknowledged to me that the same is their free and voluntary act and deed.<br><br>
    <div style="margin-top: 12px; text-align: right;">
      _____________________________________<br>
      <span style="font-size: 8px;">Notary Public · Doc. No. ___ · Page No. ___ · Book No. ___ · Series of 20____</span>
    </div>
  </div>

  <div class="footer">
    <span>{{ $profile->name }} — Promissory Note</span>
    <span>Loan No.: {{ $loan->loan_no }} &nbsp;·&nbsp; Page 3 of 5</span>
    <span>Printed by: {{ $printed_by }}</span>
  </div>

</div>
