{{-- resources/views/emails/payment_due.blade.php --}}
<!DOCTYPE html><html><head><meta charset="utf-8">
<style>body{font-family:Arial,sans-serif;font-size:14px;color:#1C1F26;margin:0;background:#f5f5f0}
.wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:8px;overflow:hidden;border:0.5px solid #ddd}
.header{background:#2E5FA3;padding:20px 28px}.header h1{color:#fff;font-size:16px;margin:0}
.body{padding:24px 28px}
.alert-box{background:#FEF9EC;border:0.5px solid #E8A020;border-radius:6px;padding:14px 16px;margin:16px 0}
.amount{font-size:28px;font-weight:700;color:#1C3557}
.field-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:0.5px solid #eee}
.field-row:last-child{border-bottom:none}
.footer{background:#f5f5f0;padding:12px 28px;font-size:11px;color:#888;text-align:center}
</style></head><body>
<div class="wrap">
  <div class="header"><h1>Loan Payment Reminder</h1></div>
  <div class="body">
    <p>Dear {{ $recipientName }},</p>
    <div class="alert-box">
      Your loan payment is due in <strong>{{ $days_away ?? 3 }} day(s)</strong>.
      <div class="amount">₱{{ $amount_due ?? '—' }}</div>
    </div>
    <div class="field-row"><span style="color:#666">Loan</span><span style="font-weight:500">{{ $loan_no ?? '—' }}</span></div>
    <div class="field-row"><span style="color:#666">Period</span><span style="font-weight:500">{{ $period_no ?? '—' }}</span></div>
    <div class="field-row"><span style="color:#666">Due date</span><span style="font-weight:500">{{ $due_date ?? '—' }}</span></div>
    <p style="margin-top:16px;font-size:13px;color:#555">Please ensure timely payment to avoid penalties. Contact your loan officer if you have any concerns.</p>
  </div>
  <div class="footer">CRS Holdings Corporations ECCO &nbsp;·&nbsp; Automated reminder</div>
</div></body></html>
