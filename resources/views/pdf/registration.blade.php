@php
    $v = fn ($value) => filled($value) ? $value : '—';
    $date = fn ($iso) => filled($iso) ? \Carbon\Carbon::parse($iso)->format('d/m/Y') : '—';
    $box = fn ($checked) => $checked ? '<span class="box">X</span>' : '<span class="box">&nbsp;&nbsp;&nbsp;</span>';
    $fullName = $d['firstNameHe'].' '.$d['lastNameHe'];
    $served = filled($d['serviceType']) && $d['serviceType'] !== 'לא';
@endphp
<html dir="rtl" lang="he">
<head>
<style>
    body { font-family: dejavusans; font-size: 9.5pt; color: #1d1d1d; }
    h1 { font-size: 18pt; color: #1f8dbb; margin: 0; }
    .sub { color: #6b7486; font-size: 9pt; margin-bottom: 8pt; }
    h2 { font-size: 12.5pt; color: #1f8dbb; border-bottom: 1.5pt solid #1f8dbb; padding-bottom: 2pt; margin: 12pt 0 5pt; }
    h3 { font-size: 10pt; color: #1d1d1d; margin: 7pt 0 2pt; border-right: 2.5pt solid #4bbce5; padding-right: 4pt; }
    table.kv { width: 100%; border-collapse: collapse; }
    table.kv td { width: 50%; padding: 1.5pt 0; vertical-align: top; }
    .k { font-weight: bold; color: #4a5263; }
    ol { margin: 3pt 0; padding-right: 14pt; }
    li { margin-bottom: 1.5pt; font-size: 8.5pt; }
    table.charge { width: 100%; border-collapse: collapse; margin-top: 5pt; font-size: 8.5pt; }
    table.charge th { background: #1f8dbb; color: #fff; padding: 3pt 5pt; text-align: right; }
    table.charge td { border: 0.5pt solid #c5ccd8; padding: 3pt 5pt; }
    .box { border: 0.8pt solid #1f8dbb; color: #1f8dbb; font-weight: bold; font-size: 8pt; padding: 0 2pt; }
    .check { margin: 3pt 0; }
    .tuition { font-weight: bold; color: #1d1d1d; font-size: 10.5pt; margin: 3pt 0 5pt; padding: 4pt 6pt; background: #e0e4e6; border-right: 3pt solid #ff8a00; }
    .sig { margin-top: 4pt; }
</style>
</head>
<body>

<h1>{{ $cfg['form_title'] }}</h1>
<div class="sub">{{ $cfg['college_name'] }}</div>

<h2>פרטי הסטודנט/ית</h2>
<table class="kv">
    <tr><td><span class="k">שם פרטי (עברית):</span> {{ $d['firstNameHe'] }}</td><td><span class="k">שם משפחה (עברית):</span> {{ $d['lastNameHe'] }}</td></tr>
    <tr><td><span class="k">First name:</span> {{ $d['firstNameEn'] }}</td><td><span class="k">Last name:</span> {{ $d['lastNameEn'] }}</td></tr>
    <tr><td><span class="k">מס' ת"ז:</span> {{ $d['idNumber'] }}</td><td></td></tr>
    <tr><td><span class="k">שלוחה:</span> {{ $branch['name'] }}</td><td><span class="k">מגמה:</span> {{ $d['major'] }}</td></tr>
    <tr><td><span class="k">מועד פתיחת לימודים:</span> {{ $d['startTerm'] }}</td><td><span class="k">מסלול לימודים:</span> {{ $d['studyTrack'] }}</td></tr>
</table>

<h2>טופס בקשת הרשמה</h2>
<h3>המסלול</h3>
<table class="kv">
    <tr><td><span class="k">תאריך הרשמה:</span> {{ $today }}</td><td><span class="k">סוג תעודה:</span> {{ $d['certificateType'] }}</td></tr>
</table>

<h3>פרטים אישיים</h3>
<table class="kv">
    <tr><td><span class="k">תאריך לידה:</span> {{ $date($d['birthDate']) }}</td><td><span class="k">ארץ לידה:</span> {{ $d['birthCountry'] }}</td></tr>
    <tr>
        <td>@if (filled($d['aliyahDate'] ?? null))<span class="k">תאריך עלייה:</span> {{ $date($d['aliyahDate']) }}@endif</td>
        <td><span class="k">מגדר:</span> {{ $d['gender'] }}</td>
    </tr>
</table>

<h3>כתובת מגורים</h3>
<table class="kv">
    <tr><td><span class="k">ישוב:</span> {{ $d['city'] }}</td><td><span class="k">רחוב / ת.ד.:</span> {{ $d['street'] }}</td></tr>
    <tr><td><span class="k">מספר:</span> {{ $v($d['houseNumber'] ?? null) }}</td><td><span class="k">מיקוד:</span> {{ $v($d['zip'] ?? null) }}</td></tr>
</table>

<h3>דרכי התקשרות</h3>
<table class="kv">
    <tr><td><span class="k">טלפון נייד:</span> {{ $d['mobile'] }}</td><td><span class="k">טלפון נוסף:</span> {{ $v($d['phone2'] ?? null) }}</td></tr>
    <tr><td colspan="2"><span class="k">דוא"ל:</span> {{ $d['email'] }}</td></tr>
</table>

<h3>משפחה</h3>
<table class="kv">
    <tr><td><span class="k">שם האב:</span> {{ $v($d['fatherName'] ?? null) }}</td><td><span class="k">שם האם:</span> {{ $v($d['motherName'] ?? null) }}</td></tr>
</table>

<h3>שירות צבאי / לאומי</h3>
<table class="kv">
    <tr><td><span class="k">שירות בצה"ל / שירות לאומי:</span> {{ $d['serviceType'] }}</td><td>@if ($d['serviceType'] === 'צה"ל')<span class="k">מס' אישי:</span> {{ $v($d['personalNumber'] ?? null) }}@endif</td></tr>
    @if ($served)
        <tr><td><span class="k">תאריך גיוס:</span> {{ $date($d['enlistDate'] ?? null) }}</td><td><span class="k">תאריך שחרור:</span> {{ $date($d['releaseDate'] ?? null) }}</td></tr>
    @endif
</table>

<h3>מקור מידע על המכללה</h3>
<table class="kv"><tr><td><span class="k">סימון:</span> {{ $v($d['infoSource'] ?? null) }}</td><td></td></tr></table>

<h2>כתב התחייבות והצהרת הסטודנט</h2>
<div class="tuition">{{ $cfg['tuition_text'] }}</div>
<p>{{ $cfg['declaration'] }}</p>
<h3>עיקרי ההתחייבות (סעיפים 1–{{ count($cfg['clauses']) }})</h3>
<ol>
    @foreach ($cfg['clauses'] as $clause)
        <li>{{ $clause }}</li>
    @endforeach
</ol>
<table class="charge">
    <tr><th>מועד ההודעה</th><th>היקף החיוב</th></tr>
    @foreach ($cfg['charge_table'] as [$when, $charge])
        <tr><td>{{ $when }}</td><td>{{ $charge }}</td></tr>
    @endforeach
</table>
<div class="check">{!! $box($d['commitmentAccepted']) !!} {{ $cfg['commitment_confirm'] }}</div>
<table class="kv">
    <tr><td><span class="k">שם פרטי:</span> {{ $d['firstNameHe'] }}</td><td><span class="k">שם משפחה:</span> {{ $d['lastNameHe'] }}</td></tr>
    <tr><td><span class="k">תאריך:</span> {{ $today }}</td><td></td></tr>
</table>

<h2>סעיפי רשות (אופציונלי)</h2>
<div class="check">{!! $box($d['consentPlacement']) !!} {{ $cfg['consent_placement'] }}</div>
@if ($d['consentPlacement'])
    <table class="kv"><tr><td><span class="k">שם:</span> {{ $fullName }}</td><td><span class="k">תאריך:</span> {{ $today }}</td></tr></table>
@endif
<div class="check">{!! $box($d['consentMessages']) !!} {{ $cfg['consent_messages'] }}</div>
@if ($d['consentMessages'])
    <table class="kv"><tr><td><span class="k">טלפון נייד:</span> {{ $d['mobile'] }}</td><td><span class="k">שם:</span> {{ $fullName }}</td></tr></table>
@endif

<h2>ויתור על סודיות</h2>
<p>{{ $cfg['waiver_text'] }}</p>
@foreach ($cfg['waiver_subjects'] as $field => $label)
    <div class="check">{!! $box($d[$field]) !!} {{ $label }}</div>
@endforeach
<table class="kv"><tr><td><span class="k">ת"ז:</span> {{ $d['idNumber'] }}</td><td><span class="k">תאריך:</span> {{ $today }}</td></tr></table>
<div class="check">{!! $box($d['waiverAccepted']) !!} {{ $cfg['waiver_confirm'] }}</div>
<table class="kv"><tr><td><span class="k">שם הסטודנט:</span> {{ $fullName }}</td><td><span class="k">שם המכללה:</span> {{ $cfg['college_name'] }}</td></tr></table>

<h2>חתימה דיגיטלית</h2>
<div class="sig">
    @if ($hasSignature)
        <img src="var:signature" style="width: 70mm;">
    @else
        —
    @endif
</div>

</body>
</html>
