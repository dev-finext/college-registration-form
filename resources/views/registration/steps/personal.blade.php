<section class="step card" data-title="פרטים אישיים">
  <h2 class="block-title">טופס בקשת הרשמה</h2>

  <h3 class="sub-title">המסלול</h3>
  <div class="grid">
    <div class="field">
      <label>תאריך הרשמה</label>
      <div class="readonly" data-fill="today"></div>
    </div>
    <div class="field">
      <label for="certificateType">סוג תעודה <span class="req">*</span></label>
      <select id="certificateType" name="certificateType" required>
        <option value="">בחירה</option>
        @foreach (config('registration.certificate_types') as $type)
          <option value="{{ $type }}">{{ $type }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <h3 class="sub-title">פרטים אישיים</h3>
  <div class="autofill-note">
    <span>שם: <strong data-fill="fullNameHe"></strong></span>
    <span>ת"ז: <strong data-fill="idNumber"></strong></span>
    <span class="muted">(מולא אוטומטית מהשלב הקודם)</span>
  </div>
  <div class="grid">
    <div class="field">
      <label for="birthDate">תאריך לידה <span class="req">*</span></label>
      <input type="date" id="birthDate" name="birthDate" required data-max="today">
    </div>
    <div class="field">
      <label for="birthCountry">ארץ לידה <span class="req">*</span></label>
      <input id="birthCountry" name="birthCountry" required list="countries" value="ישראל" maxlength="60">
      <datalist id="countries">
        @foreach (config('registration.countries') as $country)
          <option value="{{ $country }}">
        @endforeach
      </datalist>
    </div>
    <div class="field" data-show-if="birthCountry!=ישראל">
      <label for="aliyahDate">תאריך עלייה <span class="req">*</span></label>
      <input type="date" id="aliyahDate" name="aliyahDate" required data-max="today">
    </div>
    <fieldset class="field">
      <legend>מגדר <span class="req">*</span></legend>
      <div class="choice-row">
        @foreach (config('registration.genders') as $gender)
          <label class="choice-card"><input type="radio" name="gender" value="{{ $gender }}" @required($loop->first)><span>{{ $gender }}</span></label>
        @endforeach
      </div>
    </fieldset>
  </div>

  <h3 class="sub-title">כתובת מגורים</h3>
  <div class="grid">
    <div class="field">
      <label for="city">ישוב <span class="req">*</span></label>
      <input id="city" name="city" required maxlength="80" autocomplete="address-level2">
    </div>
    <div class="field">
      <label for="street">רחוב / ת.ד. <span class="req">*</span></label>
      <input id="street" name="street" required maxlength="80" autocomplete="address-line1">
    </div>
    <div class="field">
      <label for="houseNumber">מספר</label>
      <input id="houseNumber" name="houseNumber" maxlength="10">
    </div>
    <div class="field">
      <label for="zip">מיקוד</label>
      <input id="zip" name="zip" data-rule="zip" inputmode="numeric" maxlength="7" dir="ltr" autocomplete="postal-code">
    </div>
  </div>
</section>
