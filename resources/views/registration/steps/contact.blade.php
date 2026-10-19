<section class="step card" data-title="קשר ושירות">
  <h2 class="block-title">טופס בקשת הרשמה</h2>

  <h3 class="sub-title">דרכי התקשרות</h3>
  <div class="grid">
    <div class="field">
      <label for="mobile">טלפון נייד <span class="req">*</span></label>
      <input type="tel" id="mobile" name="mobile" required data-rule="mobile" dir="ltr" autocomplete="tel">
    </div>
    <div class="field">
      <label for="phone2">טלפון נוסף</label>
      <input type="tel" id="phone2" name="phone2" data-rule="phone" dir="ltr">
    </div>
    <div class="field field-wide">
      <label for="email">דוא"ל <span class="req">*</span></label>
      <input type="email" id="email" name="email" required data-rule="email" maxlength="120" dir="ltr" autocomplete="email">
    </div>
  </div>

  <h3 class="sub-title">משפחה</h3>
  <div class="grid">
    <div class="field">
      <label for="fatherName">שם האב</label>
      <input id="fatherName" name="fatherName" maxlength="50">
    </div>
    <div class="field">
      <label for="motherName">שם האם</label>
      <input id="motherName" name="motherName" maxlength="50">
    </div>
  </div>

  <h3 class="sub-title">שירות צבאי / לאומי</h3>
  <fieldset class="field">
    <legend>שירות בצה"ל / שירות לאומי <span class="req">*</span></legend>
    <div class="choice-row">
      @foreach (config('registration.service_types') as $type)
        <label class="choice-card"><input type="radio" name="serviceType" value="{{ $type }}" @required($loop->first)><span>{{ $type }}</span></label>
      @endforeach
    </div>
  </fieldset>
  <div class="grid" data-show-if="serviceType!=לא">
    <div class="field" data-show-if="serviceType=צה&quot;ל">
      <label for="personalNumber">מס' אישי</label>
      <input id="personalNumber" name="personalNumber" inputmode="numeric" maxlength="9" dir="ltr">
    </div>
    <div class="field">
      <label for="enlistDate">תאריך גיוס</label>
      <input type="date" id="enlistDate" name="enlistDate" data-max="today">
    </div>
    <div class="field">
      <label for="releaseDate">תאריך שחרור</label>
      <input type="date" id="releaseDate" name="releaseDate">
    </div>
  </div>

  <h3 class="sub-title">מקור מידע על המכללה</h3>
  <div class="field">
    <label for="infoSource">איך שמעת עלינו?</label>
    <select id="infoSource" name="infoSource">
      <option value="">בחירה</option>
      @foreach (config('registration.info_sources') as $source)
        <option value="{{ $source }}">{{ $source }}</option>
      @endforeach
    </select>
  </div>
</section>
