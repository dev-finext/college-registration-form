<section class="step card" data-title="פרטי הסטודנט/ית">
  <h2 class="block-title">פרטי הסטודנט/ית</h2>

  <div class="grid">
    <div class="field">
      <label for="firstNameHe">שם פרטי (עברית) <span class="req">*</span></label>
      <input id="firstNameHe" name="firstNameHe" required data-rule="he" maxlength="50" autocomplete="given-name">
    </div>
    <div class="field">
      <label for="lastNameHe">שם משפחה (עברית) <span class="req">*</span></label>
      <input id="lastNameHe" name="lastNameHe" required data-rule="he" maxlength="50" autocomplete="family-name">
    </div>
    <div class="field">
      <label for="firstNameEn">First name <span class="req">*</span></label>
      <input id="firstNameEn" name="firstNameEn" required data-rule="en" maxlength="50" dir="ltr">
    </div>
    <div class="field">
      <label for="lastNameEn">Last name <span class="req">*</span></label>
      <input id="lastNameEn" name="lastNameEn" required data-rule="en" maxlength="50" dir="ltr">
    </div>
    <div class="field">
      <label for="idNumber">מס' תעודת זהות <span class="req">*</span></label>
      <input id="idNumber" name="idNumber" required data-rule="id" inputmode="numeric" maxlength="9" dir="ltr">
      <small class="hint">9 ספרות כולל ספרת ביקורת</small>
    </div>
  </div>

  <div class="grid">
    <div class="field">
      <label for="branch">שלוחה <span class="req">*</span></label>
      <select id="branch" name="branch" required><option value="">בחירת שלוחה</option></select>
    </div>
    <div class="field">
      <label for="major">מגמה <span class="req">*</span></label>
      <select id="major" name="major" required>
        <option value="">בחירת מגמה</option>
        @foreach (config('registration.majors') as $major)
          <option value="{{ $major }}">{{ $major }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="grid">
    <fieldset class="field">
      <legend>מועד פתיחת לימודים <span class="req">*</span></legend>
      <div class="choice-row">
        @foreach (config('registration.start_terms') as $term)
          <label class="choice-card"><input type="radio" name="startTerm" value="{{ $term }}" @required($loop->first)><span>{{ $term }}</span></label>
        @endforeach
      </div>
    </fieldset>
    <fieldset class="field">
      <legend>מסלול לימודים <span class="req">*</span></legend>
      <div class="choice-row">
        @foreach (config('registration.study_tracks') as $track)
          <label class="choice-card"><input type="radio" name="studyTrack" value="{{ $track }}" @required($loop->first)><span>{{ $track }}</span></label>
        @endforeach
      </div>
    </fieldset>
  </div>
</section>
