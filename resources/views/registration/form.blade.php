@extends('layouts.app')

@php($cfg = config('registration'))

@section('body')

@if ($clientConfig['demoMode'])
  <div class="demo-ribbon">גרסת דמו לבדיקת ממשק בלבד · הנתונים לא נשלחים לשום מקום</div>
@endif

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="{{ $cfg['brand']['site_url'] }}" target="_blank" rel="noopener">
      <img class="brand-logo" src="{{ $cfg['brand']['logo_url'] }}" alt="המכללה למינהל" width="198" height="52"
           onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
      <span class="brand-fallback" hidden>המכללה למינהל</span>
    </a>
    <div class="header-actions">
      <a class="header-phone" href="tel:{{ $cfg['brand']['phone'] }}" aria-label="טלפון {{ $cfg['brand']['phone'] }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/></svg>
        {{ $cfg['brand']['phone'] }}
      </a>
    </div>
  </div>
</header>

<section class="hero">
  <div class="wrap hero-inner">
    <h1 class="form-title">{{ $cfg['form_title'] }}</h1>
    <div><span class="college-name">{{ $cfg['college_name'] }}</span></div>
  </div>
</section>

<main class="wrap">

  {{-- מסך פתיחה / חזרה לטופס --}}
  <section class="card intro" id="introScreen">
    <h2>ברוכים הבאים לרישום ללימודי הנדסאים</h2>
    <p>מילוי הטופס לוקח כ-10 דקות. ההתקדמות נשמרת אוטומטית <strong>במכשיר ובדפדפן הזה</strong> עד 12 חודשים, אז אפשר לעצור ולחזור בכל שלב.</p>
    <ul class="intro-list">
      <li>שם ותעודת זהות ממלאים פעם אחת, והם יופיעו אוטומטית בכל מקום בטופס</li>
      <li>בסיום חותמים על המסך, וטופס הרישום נשלח ישירות לשלוחה שבחרתם</li>
      <li>במחשב משותף מומלץ ללחוץ על "ניקוי נתונים מהמכשיר" בסיום</li>
    </ul>
    <div class="resume-box" id="resumeBox" hidden>
      <div>
        <strong>מצאנו טופס שהתחלתם למלא</strong>
        <div class="muted" id="resumeMeta"></div>
      </div>
      <div class="resume-actions">
        <button type="button" class="btn btn-primary" id="resumeBtn">המשך מאיפה שעצרתי</button>
        <button type="button" class="btn btn-ghost" id="restartBtn">התחלה מחדש</button>
      </div>
    </div>
    <div class="intro-actions" id="startActions">
      <button type="button" class="btn btn-primary btn-lg" id="startBtn">התחלת הרישום</button>
    </div>
  </section>

  {{-- אשף הטופס --}}
  <div id="wizard" hidden>
    <nav class="stepper" aria-label="שלבי הטופס">
      <ol id="stepperList"></ol>
      <div class="stepper-mobile">
        <span id="stepperMobileLabel"></span>
        <div class="progress"><div class="progress-bar" id="progressBar"></div></div>
      </div>
    </nav>

    <form id="regForm" novalidate autocomplete="on">
      {{-- honeypot נגד בוטים --}}
      <div class="hp" aria-hidden="true">
        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>

      @include('registration.steps.student')
      @include('registration.steps.personal')
      @include('registration.steps.contact')
      @include('registration.steps.commitment')
      @include('registration.steps.consents')
      @include('registration.steps.signature')

      <div class="wizard-nav">
        <button type="button" class="btn btn-ghost" id="prevBtn">חזרה</button>
        <span class="save-status" id="saveStatus" aria-live="polite"></span>
        <button type="button" class="btn btn-primary" id="nextBtn">המשך</button>
        <button type="submit" class="btn btn-primary" id="submitBtn" hidden>שליחת טופס הרישום</button>
      </div>
    </form>
  </div>

  {{-- מסך סיום --}}
  <section class="card done" id="doneScreen" hidden>
    <div class="done-icon" aria-hidden="true">✓</div>
    <h2>טופס הרישום נשלח בהצלחה</h2>
    <p>תודה <strong id="doneName"></strong>! טופס הרישום הועבר לשלוחת <strong id="doneBranch"></strong>, ונציג/ה יחזרו אליך בהקדם.</p>
    @if ($clientConfig['demoMode'])
      <div class="demo-mail">
        <div class="demo-mail-title">בפועל יישלח המייל הבא (בדמו לא נשלח דבר):</div>
        <div><span class="muted">אל:</span> <span id="mailTo" dir="ltr"></span></div>
        <div><span class="muted">נושא:</span> <span id="mailSubject"></span></div>
        <div><span class="muted">קובץ מצורף:</span> <span id="mailAttachment" dir="ltr"></span></div>
      </div>
    @endif
    <div class="done-actions">
      @if ($clientConfig['demoMode'])
        <button type="button" class="btn btn-primary" id="previewDocBtn">תצוגה מקדימה של מסמך ה-PDF</button>
      @endif
      <button type="button" class="btn btn-ghost" id="newFormBtn">מילוי טופס חדש</button>
    </div>
  </section>
</main>

@if ($clientConfig['demoMode'])
  {{-- תצוגה מקדימה של המסמך (דמו בלבד; בשרת ה-PDF נוצר ב-mPDF) --}}
  <div class="doc-overlay" id="docOverlay" hidden>
    <div class="doc-toolbar">
      <button type="button" class="btn btn-primary" id="printDocBtn">הדפסה / שמירה כ-PDF</button>
      <button type="button" class="btn btn-ghost" id="closeDocBtn">סגירה</button>
    </div>
    <article class="doc" id="docBody"></article>
  </div>
@endif

<footer class="site-footer">
  <div class="wrap footer-inner">
    <div class="footer-contact">
      <a href="tel:{{ $cfg['brand']['phone'] }}" dir="ltr">{{ $cfg['brand']['phone'] }}</a>
      <a href="mailto:{{ $cfg['brand']['email'] }}">{{ $cfg['brand']['email'] }}</a>
      <span>{{ $cfg['brand']['address'] }}</span>
    </div>
    <button type="button" class="link-btn footer-clear" id="clearDataBtn" title="מחיקת הנתונים השמורים במכשיר זה">ניקוי נתונים מהמכשיר</button>
  </div>
</footer>

<div class="toast" id="toast" role="status" hidden></div>

<script>window.APP_CONFIG = @json($clientConfig + ['csrfToken' => $clientConfig['demoMode'] ? null : csrf_token()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);</script>
<script src="{{ $assetBase }}assets/js/app.js?v={{ @filemtime(public_path('assets/js/app.js')) }}"></script>
@endsection
