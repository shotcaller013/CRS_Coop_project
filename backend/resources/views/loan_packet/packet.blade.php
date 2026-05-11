{{-- resources/views/loan_packet/packet.blade.php --}}
{{-- Master template: includes all 5 pages with DomPDF page-break directives --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  /* ── Global ──────────────────────────────────────────────── */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9.5px; color: #1A1A2E; }

  /* ── Page breaks ─────────────────────────────────────────── */
  .page { page-break-after: always; padding: 24px 28px; min-height: 277mm; }
  .page:last-child { page-break-after: auto; }

  /* ── Letterhead ──────────────────────────────────────────── */
  .letterhead { text-align: center; border-bottom: 2px solid #1C3557; padding-bottom: 8px; margin-bottom: 12px; }
  .lh-name    { font-size: 13px; font-weight: bold; color: #1C3557; letter-spacing: .3px; }
  .lh-addr    { font-size: 8px; color: #555; margin-top: 2px; }
  .lh-doc     { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; margin-top: 7px; }
  .lh-ref     { font-size: 8.5px; color: #555; margin-top: 3px; }

  /* ── Tables ──────────────────────────────────────────────── */
  table { width: 100%; border-collapse: collapse; }
  th    { background: #1C3557; color: #fff; font-size: 8px; text-transform: uppercase; letter-spacing: .3px; padding: 4px 7px; text-align: left; }
  td    { padding: 3.5px 7px; border-bottom: 0.5px solid #eee; font-size: 9px; }
  tr:last-child td { border-bottom: none; }
  .total-row td { font-weight: bold; background: #F0EFE8; border-top: 1px solid #aaa; }

  /* ── Info grid ───────────────────────────────────────────── */
  .info-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 0.5px solid #ccc; }
  .info-cell  { padding: 5px 8px; border-bottom: 0.5px solid #ddd; border-right: 0.5px solid #ddd; }
  .info-cell:nth-child(even) { border-right: none; }
  .info-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: .3px; color: #666; display: block; }
  .info-value { font-size: 9.5px; font-weight: bold; margin-top: 1px; }
  .info-cell.full { grid-column: 1 / -1; }

  /* ── Section headings ────────────────────────────────────── */
  .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .4px;
    background: #1C3557; color: #fff; padding: 3px 8px; margin: 10px 0 4px; }
  .section-title.light { background: #2E5FA3; }
  .section-title.teal  { background: #1D9E75; }

  /* ── Signature block ─────────────────────────────────────── */
  .sig-row    { display: flex; gap: 20px; margin-top: 20px; }
  .sig-box    { flex: 1; }
  .sig-line   { border-top: 1px solid #333; margin-top: 28px; padding-top: 4px; font-size: 8px; color: #333; text-align: center; }
  .sig-label  { font-size: 7.5px; color: #555; text-align: center; margin-top: 2px; }

  /* ── Misc ────────────────────────────────────────────────── */
  .right  { text-align: right; }
  .center { text-align: center; }
  .bold   { font-weight: bold; }
  .muted  { color: #666; }
  .body-text { font-size: 9px; line-height: 1.6; margin: 8px 0; }
  .checkbox  { display: inline-block; width: 10px; height: 10px; border: 1px solid #333; margin-right: 4px; vertical-align: middle; }
  .footer    { margin-top: 14px; border-top: 0.5px solid #ccc; padding-top: 4px; font-size: 7.5px; color: #888; display: flex; justify-content: space-between; }
  .highlight-box { border: 0.5px solid #1D9E75; background: #E8F7F1; border-radius: 3px; padding: 8px 10px; margin: 8px 0; }
</style>
</head>
<body>

{{-- PAGE 1 — LOAN APPLICATION FORM --}}
@include('loan_packet.pages.page1-application')

{{-- PAGE 2 — AUTHORITY TO DEDUCT --}}
@include('loan_packet.pages.page2-authority')

{{-- PAGE 3 — PROMISSORY NOTE --}}
@include('loan_packet.pages.page3-promissory')

{{-- PAGE 4 (& 5 if long) — AMORTIZATION SCHEDULE --}}
@include('loan_packet.pages.page4-schedule')

{{-- LAST PAGE — DISCLOSURE STATEMENT --}}
@include('loan_packet.pages.page5-disclosure')

</body>
</html>
