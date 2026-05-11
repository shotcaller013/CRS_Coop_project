{{-- resources/views/emails/approval_needed.blade.php --}}
<!DOCTYPE html><html><head><meta charset="utf-8">
<style>body{font-family:Arial,sans-serif;font-size:14px;color:#1C1F26;margin:0;background:#f5f5f0}
.wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:8px;overflow:hidden;border:0.5px solid #ddd}
.header{background:#1C3557;padding:20px 28px}.header h1{color:#fff;font-size:16px;margin:0}
.body{padding:24px 28px}
.cta{display:inline-block;background:#1D9E75;color:#fff;text-decoration:none;padding:10px 20px;border-radius:6px;font-weight:500;margin-top:16px}
.field-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:0.5px solid #eee}
.field-row:last-child{border-bottom:none}
.footer{background:#f5f5f0;padding:12px 28px;font-size:11px;color:#888;text-align:center}
</style></head><body>
<div class="wrap">
  <div class="header"><h1>Loan Application Awaiting Your Approval</h1></div>
  <div class="body">
    <p>Hi {{ $manager_name ?? 'Manager' }},</p>
    <p>A loan application has been submitted and requires your approval.</p>
    <div class="field-row"><span style="color:#666">Loan reference</span><span style="font-weight:500">{{ $loan_no ?? '—' }}</span></div>
    <div class="field-row"><span style="color:#666">Member</span><span style="font-weight:500">{{ $member_name ?? '—' }}</span></div>
    <div class="field-row"><span style="color:#666">Amount</span><span style="font-weight:500">₱{{ $amount ?? '—' }}</span></div>
    <div class="field-row"><span style="color:#666">Loan type</span><span style="font-weight:500">{{ $loan_type ?? '—' }}</span></div>
    <p style="font-size:13px;color:#555;margin-top:12px">Please log in to the Loan Management System to review and approve or reject this application.</p>
  </div>
  <div class="footer">CRS Holdings Corporations ECCO &nbsp;·&nbsp; Internal notification</div>
</div></body></html>
