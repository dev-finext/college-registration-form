<section class="step card" data-title="רשות וסודיות">
  <h2 class="block-title">סעיפי רשות (אופציונלי)</h2>

  <div class="optional-clause">
    <label class="check"><input type="checkbox" name="consentPlacement"><span>{{ config('registration.consent_placement') }}</span></label>
    <div class="sign-line" data-show-if="consentPlacement=true">
      <span>שם פרטי: <strong data-fill="firstNameHe"></strong></span>
      <span>שם משפחה: <strong data-fill="lastNameHe"></strong></span>
      <span>תאריך: <strong data-fill="today"></strong></span>
    </div>
  </div>

  <div class="optional-clause">
    <label class="check"><input type="checkbox" name="consentMessages"><span>{{ config('registration.consent_messages') }}</span></label>
    <div class="sign-line" data-show-if="consentMessages=true">
      <span>טלפון נייד: <strong data-fill="mobile" dir="ltr"></strong></span>
      <span>שם פרטי: <strong data-fill="firstNameHe"></strong></span>
      <span>שם משפחה: <strong data-fill="lastNameHe"></strong></span>
    </div>
  </div>

  <h2 class="block-title">ויתור על סודיות</h2>
  <p class="legal-lead">{{ config('registration.waiver_text') }}</p>
  <div class="check-list">
    @foreach (config('registration.waiver_subjects') as $name => $label)
      <label class="check"><input type="checkbox" name="{{ $name }}"><span>{{ $label }}</span></label>
    @endforeach
  </div>
  <div class="sign-line">
    <span>ת"ז: <strong data-fill="idNumber"></strong></span>
    <span>תאריך: <strong data-fill="today"></strong></span>
  </div>
  <div class="field">
    <label class="check"><input type="checkbox" name="waiverAccepted"><span>{{ config('registration.waiver_confirm') }}</span></label>
  </div>
  <div class="sign-line">
    <span>שם הסטודנט: <strong data-fill="fullNameHe"></strong></span>
    <span>שם המכללה: <strong>{{ config('registration.college_name') }}</strong></span>
  </div>
</section>
