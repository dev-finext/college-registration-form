<section class="step card" data-title="כתב התחייבות">
  <h2 class="block-title">כתב התחייבות והצהרת הסטודנט</h2>

  <div class="fee-box">
    <div class="fee-label">שכר לימוד</div>
    <div class="fee-value">{{ config('registration.tuition_text') }}</div>
  </div>

  <p class="legal-lead">{{ config('registration.declaration') }}</p>

  <h3 class="sub-title">עיקרי ההתחייבות (סעיפים 1–{{ count(config('registration.clauses')) }})</h3>
  <div class="legal-scroll" tabindex="0">
    <ol class="clauses">
      @foreach (config('registration.clauses') as $clause)
        <li>{{ $clause }}</li>
      @endforeach
    </ol>

    <table class="charge-table">
      <thead><tr><th>מועד ההודעה</th><th>היקף החיוב</th></tr></thead>
      <tbody>
        @foreach (config('registration.charge_table') as [$when, $charge])
          <tr><td>{{ $when }}</td><td>{{ $charge }}</td></tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="field">
    <label class="check"><input type="checkbox" name="commitmentAccepted" required><span>{{ config('registration.commitment_confirm') }} <span class="req">*</span></span></label>
  </div>

  <div class="field">
    <label class="check"><input type="checkbox" name="privacyPolicyAccepted" required><span>{{ config('registration.privacy_policy_confirm') }} <a href="{{ config('registration.privacy_policy_url') }}" target="_blank" rel="noopener">מדיניות הגנת הפרטיות</a> <span class="req">*</span></span></label>
  </div>
</section>
