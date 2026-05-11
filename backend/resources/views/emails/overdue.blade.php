{{-- resources/views/emails/overdue.blade.php --}}
<!DOCTYPE html><html><head><meta charset="utf-8">
<style>body{font-family:Arial,sans-serif;font-size:14px;color:#1C1F26;margin:0;background:#f5f5f0}
.wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:8px;overflow:hidden;border:0.5px solid #ddd}
.header{background:#A32D2D;padding:20px 28px}.header h1{color:#fff;font-size:16px;margin:0}
.body{padding:24px 28px}
.alert-box{background:#FDEAEA;border:0.5px solid #E24B4A;border-radius:6px;padding:14px 16px;margin:16px 0}
.days{font-size:26px;font-weight:700;color:#A32D2D}
.field-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:0.5px solid #eee}
.field-row:last-child{border-bottom:none}
.footer{background:#f5f5f0;padding:12px 28px;font-size:11px;color:#888;text-align:center}
</style></head><body>
<div class="wrap">
  <div class="header"><h1>Overdue Payment Notice</h1></div>
  <div class="body">
    <p>Dear {{ $recipientName }},</p>
    <div class="alert-box">
      Your payment is <strong>overdue</strong>.
      <div class="days">{{ $days_overdue ?? 0 }} day(s) past due</div>
    </div>
    <div class="field-row"><span style="color:#666">Loan</span><span style="font-weight:500">{{ $loan_no ?? '—' }}</span></div>
    <div class="field-row"><span style="color:#666">Period</span><span style="font-weight:500">{{ $period_no ?? '—' }}</span></div>
    <div class="field-row"><span style="color:#666">Due date</span><span style="font-weight:500">{{ $due_date ?? '—' }}</span></div>
    <div class="field-row"><span style="color:#666">Amount due</span><span style="font-weight:500;color:#A32D2D">₱{{ $amount_due ?? '—' }}</span></div>
    <div class="field-row"><span style="color:#666">Penalty accrued</span><span style="font-weight:500;color:#A32D2D">₱{{ $penalty ?? '0.00' }}</span></div>
    <p style="margin-top:16px;font-size:13px;color:#555">Please settle your overdue payment immediately to prevent further penalties. Contact us if you need assistance.</p>
  </div>
  <div class="footer">CRS Holdings Corporations ECCO &nbsp;·&nbsp; Automated notice</div>
</div></body></html>
