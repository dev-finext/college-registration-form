<!doctype html>
<html lang="he" dir="rtl">
<head><meta charset="utf-8"></head>
<body style="margin:0; padding:24px; background:#f3f5f9; font-family:Arial, sans-serif; color:#1d2433;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="rtl" style="max-width:600px; margin:0 auto; background:#ffffff; border-radius:8px; border-top:4px solid #14284b;">
  <tr><td style="padding:24px 28px; text-align:right;">
    <h1 style="margin:0 0 4px; font-size:20px; color:#14284b;">טופס רישום הנדסאים חדש</h1>
    <p style="margin:0 0 18px; color:#6b7486; font-size:14px;">שלוחת {{ $branch['name'] }} · {{ now()->format('d/m/Y H:i') }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="font-size:15px; line-height:1.7;">
      <tr><td style="color:#6b7486; padding-left:16px;">שם:</td><td><strong>{{ $d['firstNameHe'] }} {{ $d['lastNameHe'] }}</strong> ({{ $d['firstNameEn'] }} {{ $d['lastNameEn'] }})</td></tr>
      <tr><td style="color:#6b7486; padding-left:16px;">ת"ז:</td><td>{{ $d['idNumber'] }}</td></tr>
      <tr><td style="color:#6b7486; padding-left:16px;">מגמה:</td><td>{{ $d['major'] }}</td></tr>
      <tr><td style="color:#6b7486; padding-left:16px;">מועד פתיחה:</td><td>{{ $d['startTerm'] }} · {{ $d['studyTrack'] }}</td></tr>
      <tr><td style="color:#6b7486; padding-left:16px;">נייד:</td><td dir="ltr" style="text-align:right;">{{ $d['mobile'] }}</td></tr>
      <tr><td style="color:#6b7486; padding-left:16px;">דוא"ל:</td><td>{{ $d['email'] }}</td></tr>
    </table>

    <p style="margin:18px 0 0; font-size:14px;">טופס הרישום המלא והחתום מצורף כקובץ PDF. אפשר להשיב למייל הזה כדי לפנות ישירות למועמד/ת.</p>
    <p style="margin:10px 0 0; font-size:12px; color:#6b7486;">המסמך לא נשמר בשרת. המייל הזה הוא העותק היחיד של הרישום.</p>
  </td></tr>
</table>
</body>
</html>
