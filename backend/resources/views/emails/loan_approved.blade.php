{{-- resources/views/emails/loan_approved.blade.php --}}
<!DOCTYPE html><html><head><meta charset="utf-8">
<style>body{font-family:Arial,sans-serif;font-size:14px;color:#1C1F26;margin:0;padding:0;background:#f5f5f0}
.wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:8px;overflow:hidden;border:0.5px solid #ddd}
.header{background:#1C3557;padding:24px 28px;text-align:center}
.header h1{color:#fff;font-size:18px;margin:0}
.header p{color:#9BB8CC;font-size:12px;margin:6px 0 0}
.body{padding:24px 28px}
.field-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:0.5px solid #eee}
.field-row:last-child{border-bottom:none}
.fl{color:#666;font-size:13px}
.fv{font-weight:500;font-size:13px}
.cta{display:block;background:#1D9E75;color:#fff;text-decoration:none;text-align:center;padding:12px 24px;border-radius:6px;font-weight:500;margin:20px 0 0}
.footer{background:#f5f5f0;padding:12px 28px;font-size:11px;color:#888;text-align:center}
</style></head><body>
<div class="wrap">
  <div class="header"><h1>Loan Application Approved</h1><p>CRS Holdings Corporations Employees Credit Cooperative</p></div>
  <div class="body">
    <p>Dear {{ $recipientName }},</p>
    <p>We are pleased to inform you that your loan application has been <strong>approved</strong>. Below are your loan details:</p>
    <div class="field-row"><span class="fl">Loan reference</span><span class="fv">{{ $loan_no ?? '—' }}</span></div>
    <div class="field-row"><span class="fl">Approved amount</span><span class="fv">₱{{ $amount ?? '—' }}</span></div>
    <div class="field-row"><span class="fl">Term</span><span class="fv">{{ $term ?? '—' }} months</span></div>
    <div class="field-row"><span class="fl">First due date</span><span class="fv">{{ $first_due ?? '—' }}</span></div>
    <p style="margin-top:16px;font-size:13px;color:#555">Please coordinate with your loan officer for the release and to sign the required documents.</p>
  </div>
  <div class="footer">CRS Holdings Corporations ECCO &nbsp;·&nbsp; Mandaue City, Cebu &nbsp;·&nbsp; This is an automated message.</div>
</div></body></html>
