<section class="step card" data-title="רשות וסודיות">
  <h2 class="block-title">העברת פרטים לחברות השמה (אופציונלי)</h2>
  <div class="field">
    <label class="check"><input type="checkbox" name="consentPlacement"><span>{{ config('registration.consent_placement') }}</span></label>
  </div>

  <h2 class="block-title">קבלת הודעות ועדכונים (אופציונלי)</h2>
  <div class="field">
    <label class="check"><input type="checkbox" name="consentMessages"><span>{{ config('registration.consent_messages') }}</span></label>
  </div>

  <h2 class="block-title">ויתור על סודיות</h2>
  <p class="legal-lead">{{ config('registration.waiver_text') }}</p>
  <div class="check-list">
    @foreach (config('registration.waiver_subjects') as $name => $label)
      <label class="check"><input type="checkbox" name="{{ $name }}"><span>{{ $label }}</span></label>
    @endforeach
  </div>
  <div class="field">
    <label class="check"><input type="checkbox" name="waiverAccepted"><span>{{ config('registration.waiver_confirm') }}</span></label>
  </div>
</section>
