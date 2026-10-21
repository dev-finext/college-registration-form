(() => {
  'use strict';

  const CFG = window.APP_CONFIG;
  const STORAGE_KEY = 'cfm-registration-v1';
  const TTL_MS = CFG.storageTtlDays * 24 * 60 * 60 * 1000;

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

  const form = $('#regForm');
  const steps = $$('.step', form);
  const state = { step: 0, maxStep: 0, data: {}, signature: null };

  // לינק ייעודי לשלוחה (/jerusalem בשרת, ?branch=jerusalem בדמו) נועל את השלוחה
  const urlBranch = CFG.lockedBranch || new URLSearchParams(location.search).get('branch');
  const lockedBranch = CFG.branches.find(b => b.id === urlBranch) || null;

  /* ---------- עזרי תאריך ---------- */
  const pad = n => String(n).padStart(2, '0');
  const isoToday = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
  const heDate = iso => { if (!iso) return '—'; const [y, m, d] = iso.split('-'); return `${d}/${m}/${y}`; };
  const parseDate = iso => iso ? new Date(iso + 'T00:00:00') : null;
  const yearsBetween = (a, b) => (b - a) / (365.25 * 24 * 3600 * 1000);

  /* ---------- בדיקות תקינות ---------- */
  function isValidIsraeliId(raw) {
    const id = String(raw || '').trim();
    if (!/^\d{5,9}$/.test(id)) return false;
    const padded = id.padStart(9, '0');
    let sum = 0;
    for (let i = 0; i < 9; i++) {
      let n = Number(padded[i]) * ((i % 2) + 1);
      if (n > 9) n -= 9;
      sum += n;
    }
    return sum % 10 === 0;
  }
  const digits = v => String(v || '').replace(/[\s\-()]/g, '');

  const RULES = {
    id: v => isValidIsraeliId(v) || 'מספר תעודת הזהות אינו תקין (ספרת ביקורת שגויה)',
    he: v => /^[֐-׿\s'"\-׳״]+$/.test(v) || 'יש להזין אותיות בעברית בלבד',
    en: v => /^[A-Za-z\s'\-]+$/.test(v) || 'Please use English letters only',
    mobile: v => /^05\d{8}$/.test(digits(v)) || 'מספר נייד לא תקין (לדוגמה 050-1234567)',
    phone: v => /^0\d{8,9}$/.test(digits(v)) || 'מספר טלפון לא תקין',
    email: v => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) || 'כתובת דוא"ל לא תקינה',
    zip: v => /^\d{7}$/.test(v) || 'מיקוד צריך להכיל 7 ספרות'
  };

  // בדיקות עקביות בין שדות, לפי שלב
  const CROSS_CHECKS = {
    1: d => {
      const errs = [];
      const birth = parseDate(d.birthDate), now = new Date();
      if (birth) {
        const age = yearsBetween(birth, now);
        if (age < 15) errs.push(['birthDate', 'גיל המועמד/ת נמוך מ-15, יש לבדוק את תאריך הלידה']);
        if (age > 90) errs.push(['birthDate', 'יש לבדוק את תאריך הלידה']);
      }
      const aliyah = parseDate(d.aliyahDate);
      if (birth && aliyah && isVisible('aliyahDate') && aliyah < birth) errs.push(['aliyahDate', 'תאריך העלייה מוקדם מתאריך הלידה']);
      return errs;
    },
    2: d => {
      const errs = [];
      if (!isVisible('enlistDate')) return errs;
      const birth = parseDate(d.birthDate), enlist = parseDate(d.enlistDate), release = parseDate(d.releaseDate);
      if (birth && enlist && yearsBetween(birth, enlist) < 16) errs.push(['enlistDate', 'תאריך הגיוס לא מתאים לתאריך הלידה']);
      if (enlist && release && release <= enlist) errs.push(['releaseDate', 'תאריך השחרור חייב להיות אחרי תאריך הגיוס']);
      return errs;
    }
  };

  /* ---------- איסוף ושחזור נתונים ---------- */
  function readForm() {
    const data = {};
    for (const el of form.elements) {
      if (!el.name || el.name === 'website') continue;
      if (el.type === 'checkbox') data[el.name] = el.checked;
      else if (el.type === 'radio') { if (el.checked) data[el.name] = el.value; else if (!(el.name in data)) data[el.name] = ''; }
      else data[el.name] = el.value.trim();
    }
    if (lockedBranch) data.branch = lockedBranch.id;
    return data;
  }

  function writeForm(data) {
    for (const el of form.elements) {
      if (!el.name || !(el.name in data) || el.name === 'major') continue;
      if (el.name === 'branch' && lockedBranch) continue; // קישור שלוחה גובר על שלוחה שנשמרה קודם
      if (el.type === 'checkbox') el.checked = !!data[el.name];
      else if (el.type === 'radio') el.checked = data[el.name] === el.value;
      else el.value = data[el.name];
    }
    populateMajors(data.major);
  }

  /* ---------- שמירה בדפדפן (onboarding) ---------- */
  function save() {
    state.data = readForm();
    const payload = { v: 1, savedAt: Date.now(), expiresAt: Date.now() + TTL_MS, step: state.step, maxStep: state.maxStep, data: state.data, signature: state.signature };
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
      setSaveStatus('נשמר במכשיר ✓');
    } catch (e) {
      setSaveStatus('לא ניתן לשמור במכשיר זה');
    }
  }

  function load() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      const p = JSON.parse(raw);
      if (!p || p.v !== 1 || Date.now() > p.expiresAt) { localStorage.removeItem(STORAGE_KEY); return null; }
      return p;
    } catch (e) { return null; }
  }

  function clearStorage() { try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* ignore */ } }

  let saveTimer, statusTimer;
  const scheduleSave = () => { clearTimeout(saveTimer); setSaveStatus('שומר…'); saveTimer = setTimeout(save, 400); };
  function setSaveStatus(text) {
    const el = $('#saveStatus');
    el.textContent = text;
    clearTimeout(statusTimer);
    statusTimer = setTimeout(() => { el.textContent = ''; }, 2500);
  }

  /* ---------- רשימות בחירה ---------- */
  function fillSelect(select, values) {
    for (const v of values) select.add(new Option(v, v));
  }

  function populateBranches() {
    const sel = $('#branch');
    for (const b of CFG.branches) sel.add(new Option(b.name, b.id));
    if (lockedBranch) { sel.value = lockedBranch.id; sel.disabled = true; }
  }

  function populateMajors(selected) {
    const sel = $('#major');
    const branch = CFG.branches.find(b => b.id === $('#branch').value);
    sel.length = 0;
    if (!branch) { sel.add(new Option('יש לבחור שלוחה קודם', '')); sel.disabled = true; return; }
    sel.add(new Option('בחירת מגמה', ''));
    fillSelect(sel, branch.majors);
    sel.disabled = false;
    if (selected && branch.majors.includes(selected)) sel.value = selected;
    else if (branch.majors.length === 1) sel.value = branch.majors[0]; // שלוחה עם מגמה אחת (אילת)
  }

  function populateStatic() {
    populateBranches();
    populateMajors();
    $$('[data-max="today"]').forEach(el => { el.max = isoToday(); });
  }

  /* ---------- שדות מותנים ומילוי אוטומטי ---------- */
  function applyConditions() {
    const d = readForm();
    $$('[data-show-if]').forEach(el => {
      const cond = el.dataset.showIf;
      const neg = cond.includes('!=');
      const [name, value] = cond.split(neg ? '!=' : '=');
      const current = String(d[name] ?? '');
      el.hidden = neg ? (current === '' || current === value) : current !== value;
    });
  }

  function isVisible(name) {
    const el = form.elements[name];
    const node = el && (el.length && !el.tagName ? el[0] : el);
    return !!node && !node.closest('[hidden]');
  }

  function applyFills() {
    const d = readForm();
    const values = {
      ...d,
      fullNameHe: [d.firstNameHe, d.lastNameHe].filter(Boolean).join(' '),
      today: heDate(isoToday())
    };
    $$('[data-fill]').forEach(el => { el.textContent = values[el.dataset.fill] || '—'; });
  }

  /* ---------- הצגת שגיאות ---------- */
  function fieldWrap(el) { return el.closest('.field') || el.parentElement; }

  function setError(el, msg) {
    const wrap = fieldWrap(el);
    wrap.classList.add('has-error');
    let err = wrap.querySelector(':scope > .field-error');
    if (!err) { err = document.createElement('div'); err.className = 'field-error'; wrap.appendChild(err); }
    err.textContent = msg;
  }

  function clearError(el) {
    const wrap = fieldWrap(el);
    wrap.classList.remove('has-error');
    wrap.querySelector(':scope > .field-error')?.remove();
  }

  function validateField(el) {
    if (el.disabled && !(lockedBranch && el.name === 'branch')) return true;
    if (el.closest('[hidden]')) { clearError(el); return true; }
    let msg = null;
    if (el.type === 'radio') {
      const group = $$(`input[name="${el.name}"]`, form);
      if (group.some(r => r.required) && !group.some(r => r.checked)) msg = 'יש לבחור אחת מהאפשרויות';
    } else if (el.type === 'checkbox') {
      if (el.required && !el.checked) msg = 'יש לאשר כדי להמשיך';
    } else {
      const v = el.value.trim();
      if (!v && el.required) msg = el.tagName === 'SELECT' ? 'יש לבחור מהרשימה' : 'שדה חובה';
      else if (v && el.dataset.rule && RULES[el.dataset.rule]) {
        const r = RULES[el.dataset.rule](v);
        if (r !== true) msg = r;
      } else if (v && el.type === 'date' && el.max && v > el.max) msg = 'התאריך לא יכול להיות עתידי';
    }
    if (msg) { setError(el, msg); return false; }
    clearError(el);
    return true;
  }

  function validateStep(i) {
    const section = steps[i];
    let firstBad = null;
    const seenRadio = new Set();
    for (const el of $$('input, select', section)) {
      if (!el.name || el.name === 'website') continue;
      if (el.type === 'radio') { if (seenRadio.has(el.name)) continue; seenRadio.add(el.name); }
      if (!validateField(el) && !firstBad) firstBad = el;
    }
    const cross = CROSS_CHECKS[i] ? CROSS_CHECKS[i](readForm()) : [];
    for (const [name, msg] of cross) {
      const el = form.elements[name];
      setError(el, msg);
      if (!firstBad) firstBad = el;
    }
    if (i === steps.length - 1 && !state.signature) {
      $('#sigField').classList.add('has-error');
      setError($('#sigCanvas'), 'נדרשת חתימה');
      if (!firstBad) firstBad = $('#sigCanvas');
    }
    if (firstBad) {
      fieldWrap(firstBad).scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (firstBad.focus && firstBad.type !== 'radio') setTimeout(() => firstBad.focus({ preventScroll: true }), 300);
      toast('יש לתקן את השדות המסומנים');
      return false;
    }
    return true;
  }

  /* ---------- ניווט בין שלבים ---------- */
  function renderStepper() {
    const list = $('#stepperList');
    list.innerHTML = steps.map((s, i) => {
      const cls = i === state.step ? 'is-current' : (i <= state.maxStep ? 'is-done' : '');
      return `<li class="${cls}"><button type="button" data-goto="${i}" ${i <= state.maxStep && i !== state.step ? '' : 'tabindex="-1"'}>
        <span class="dot">${i < state.maxStep || (i <= state.maxStep && i !== state.step) ? '✓' : i + 1}</span><span>${s.dataset.title}</span></button></li>`;
    }).join('');
    $('#stepperMobileLabel').textContent = `שלב ${state.step + 1} מתוך ${steps.length} · ${steps[state.step].dataset.title}`;
    $('#progressBar').style.width = `${((state.step + 1) / steps.length) * 100}%`;
  }

  function showStep(i, { scroll = true } = {}) {
    state.step = Math.max(0, Math.min(i, steps.length - 1));
    state.maxStep = Math.max(state.maxStep, state.step);
    steps.forEach((s, idx) => { s.hidden = idx !== state.step; });
    const last = state.step === steps.length - 1;
    $('#prevBtn').style.visibility = state.step === 0 ? 'hidden' : 'visible';
    $('#nextBtn').hidden = last;
    $('#submitBtn').hidden = !last;
    if (last) { renderSummary(); sig.resize(); }
    applyConditions();
    applyFills();
    renderStepper();
    if (scroll) window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /* ---------- סיכום ---------- */
  function summaryData(d) {
    const branch = CFG.branches.find(b => b.id === d.branch);
    const yes = v => v ? 'כן' : 'לא';
    return [
      { step: 0, title: 'פרטי הסטודנט/ית', rows: [
        ['שם', `${d.firstNameHe} ${d.lastNameHe}`], ['Name', `${d.firstNameEn} ${d.lastNameEn}`],
        ['ת"ז', d.idNumber], ['שלוחה', branch?.name], ['מגמה', d.major],
        ['מועד פתיחה', d.startTerm], ['מסלול', d.studyTrack]] },
      { step: 1, title: 'פרטים אישיים', rows: [
        ['תאריך הרשמה', heDate(isoToday())], ['סוג תעודה', d.certificateType],
        ['תאריך לידה', heDate(d.birthDate)], ['ארץ לידה', d.birthCountry],
        ...(d.birthCountry && d.birthCountry !== 'ישראל' ? [['תאריך עלייה', heDate(d.aliyahDate)]] : []),
        ['מגדר', d.gender], ['כתובת', [d.street, d.houseNumber, d.city].filter(Boolean).join(' ')], ['מיקוד', d.zip]] },
      { step: 2, title: 'קשר ושירות', rows: [
        ['נייד', d.mobile], ['טלפון נוסף', d.phone2], ['דוא"ל', d.email],
        ['שם האב', d.fatherName], ['שם האם', d.motherName], ['שירות', d.serviceType],
        ...(d.serviceType && d.serviceType !== 'לא' ? [['גיוס', heDate(d.enlistDate)], ['שחרור', heDate(d.releaseDate)]] : []),
        ['מקור מידע', d.infoSource]] },
      { step: 3, title: 'כתב התחייבות', rows: [['אישור תנאים', yes(d.commitmentAccepted)]] },
      { step: 4, title: 'רשות וסודיות', rows: [
        ['העברה לחברות השמה', yes(d.consentPlacement)], ['קבלת הודעות', yes(d.consentMessages)],
        ['ויתור סודיות', yes(d.waiverAccepted)],
        ['מקצועות', [d.waiverMath && 'מתמטיקה', d.waiverEnglish && 'אנגלית', d.waiverHebrew && 'עברית'].filter(Boolean).join(', ')]] }
    ];
  }

  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function renderSummary() {
    const d = readForm();
    $('#summary').innerHTML = summaryData(d).map(b => `
      <div class="summary-block">
        <div class="summary-head"><h4>${b.title}</h4><button type="button" class="link-btn" data-goto="${b.step}">עריכה</button></div>
        <dl>${b.rows.map(([k, v]) => `<div><dt>${k}:</dt><dd>${esc(v || '—')}</dd></div>`).join('')}</dl>
      </div>`).join('');
  }

  /* ---------- חתימה דיגיטלית ---------- */
  const sig = (() => {
    const canvas = $('#sigCanvas');
    const ctx = canvas.getContext('2d');
    let drawing = false, last = null, dirty = false;

    function resize() {
      const rect = canvas.getBoundingClientRect();
      if (!rect.width) return;
      const ratio = window.devicePixelRatio || 1;
      canvas.width = rect.width * ratio;
      canvas.height = rect.height * ratio;
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#14284b';
      if (state.signature) {
        const img = new Image();
        img.onload = () => ctx.drawImage(img, 0, 0, rect.width, rect.height);
        img.src = state.signature;
      }
      $('#sigPlaceholder').hidden = !!state.signature;
    }

    // PNG על רקע לבן (שקיפות לא מוצגת נכון בחלק מקוראי ה-PDF), בגודל CSS ולא ברזולוציית המסך
    function exportPng() {
      const rect = canvas.getBoundingClientRect();
      const out = document.createElement('canvas');
      out.width = Math.round(rect.width); out.height = Math.round(rect.height);
      const o = out.getContext('2d');
      o.fillStyle = '#fff'; o.fillRect(0, 0, out.width, out.height);
      o.drawImage(canvas, 0, 0, out.width, out.height);
      return out.toDataURL('image/png');
    }

    const pos = e => { const r = canvas.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; };

    canvas.addEventListener('pointerdown', e => {
      drawing = true; last = pos(e); canvas.setPointerCapture(e.pointerId);
      $('#sigPlaceholder').hidden = true;
      ctx.beginPath(); ctx.arc(last.x, last.y, 1, 0, Math.PI * 2); ctx.fillStyle = '#14284b'; ctx.fill();
    });
    canvas.addEventListener('pointermove', e => {
      if (!drawing) return;
      const p = pos(e);
      ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
      last = p; dirty = true;
    });
    const end = () => {
      if (!drawing) return;
      drawing = false;
      state.signature = exportPng();
      $('#sigField').classList.remove('has-error');
      clearError(canvas);
      if (dirty) scheduleSave();
    };
    canvas.addEventListener('pointerup', end);
    canvas.addEventListener('pointercancel', end);

    function clear(silent = false) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      state.signature = null; dirty = false;
      $('#sigPlaceholder').hidden = false;
      if (!silent) scheduleSave();
    }
    $('#sigClear').addEventListener('click', () => clear());
    window.addEventListener('resize', () => { if (!steps[steps.length - 1].hidden) resize(); });
    return { resize, clear };
  })();

  /* ---------- מסמך PDF (תצוגה מקדימה) ---------- */
  function renderDoc(d) {
    const branch = CFG.branches.find(b => b.id === d.branch);
    const row = (k, v) => `<div><dt>${k}:</dt><dd>${esc(v || '—')}</dd></div>`;
    const box = v => `<span class="box">${v ? '✕' : ''}</span>`;
    const clauses = $$('.clauses li').map(li => `<li>${esc(li.textContent)}</li>`).join('');
    return `
      <h1>${CFG.formTitle}</h1><div class="doc-sub">${CFG.collegeName}</div>
      <h2>פרטי הסטודנט/ית</h2>
      <dl>${row('שם פרטי (עברית)', d.firstNameHe)}${row('שם משפחה (עברית)', d.lastNameHe)}
      ${row('First name', d.firstNameEn)}${row('Last name', d.lastNameEn)}${row('מס\' ת"ז', d.idNumber)}<div></div>
      ${row('שלוחה', branch?.name)}${row('מגמה', d.major)}${row('מועד פתיחת לימודים', d.startTerm)}${row('מסלול לימודים', d.studyTrack)}</dl>
      <h2>טופס בקשת הרשמה</h2>
      <h3>המסלול</h3><dl>${row('תאריך הרשמה', heDate(isoToday()))}${row('סוג תעודה', d.certificateType)}</dl>
      <h3>פרטים אישיים</h3><dl>${row('תאריך לידה', heDate(d.birthDate))}${row('ארץ לידה', d.birthCountry)}
      ${d.birthCountry !== 'ישראל' ? row('תאריך עלייה', heDate(d.aliyahDate)) : ''}${row('מגדר', d.gender)}</dl>
      <h3>כתובת מגורים</h3><dl>${row('ישוב', d.city)}${row('רחוב / ת.ד.', d.street)}${row('מספר', d.houseNumber)}${row('מיקוד', d.zip)}</dl>
      <h3>דרכי התקשרות</h3><dl>${row('טלפון נייד', d.mobile)}${row('טלפון נוסף', d.phone2)}${row('דוא"ל', d.email)}</dl>
      <h3>משפחה</h3><dl>${row('שם האב', d.fatherName)}${row('שם האם', d.motherName)}</dl>
      <h3>שירות צבאי / לאומי</h3><dl>${row('שירות בצה"ל / שירות לאומי', d.serviceType)}
      ${d.serviceType === 'צה"ל' ? row('מס\' אישי', d.personalNumber) : ''}
      ${d.serviceType && d.serviceType !== 'לא' ? row('תאריך גיוס', heDate(d.enlistDate)) + row('תאריך שחרור', heDate(d.releaseDate)) : ''}</dl>
      <h3>מקור מידע על המכללה</h3><dl>${row('סימון', d.infoSource)}</dl>
      <h2>כתב התחייבות והצהרת הסטודנט</h2>
      <p><strong>שכר הלימוד במלואו (שייקבע בנהלי מה"ט) + 200 ₪ דמי פתיחת תיק ורישום</strong></p>
      <ol>${clauses}</ol>
      <div class="doc-check">${box(d.commitmentAccepted)} קראתי, הבנתי ואני מאשר/ת את כל תנאי כתב ההתחייבות וסעיפים 1–25 כמפורט לעיל.</div>
      <h2>סעיפי רשות (אופציונלי)</h2>
      <div class="doc-check">${box(d.consentPlacement)} אני מאשר/ת למכללה להעביר את פרטיי האישיים (שם וטלפון) לחברות כוח אדם למטרות השמה.</div>
      <div class="doc-check">${box(d.consentMessages)} אני מאשר/ת קבלת הודעות/SMS ודוא"ל (מה"ט והמכללה) לטלפון הנייד שלי: ${esc(d.mobile)}</div>
      <h2>ויתור על סודיות</h2>
      <div class="doc-check">${box(d.waiverMath)} מתמטיקה (3 יח' ומעלה)</div>
      <div class="doc-check">${box(d.waiverEnglish)} אנגלית (3 יח' ומעלה)</div>
      <div class="doc-check">${box(d.waiverHebrew)} עברית (2 יח' ומעלה)</div>
      <div class="doc-check">${box(d.waiverAccepted)} אני מאשר/ת את ויתור הסודיות כמפורט לעיל.</div>
      <dl>${row('ת"ז', d.idNumber)}${row('תאריך', heDate(isoToday()))}${row('שם הסטודנט', `${d.firstNameHe} ${d.lastNameHe}`)}${row('שם המכללה', CFG.collegeName)}</dl>
      <h2>חתימה דיגיטלית</h2>
      <div class="doc-sig">${state.signature ? `<img src="${state.signature}" alt="חתימה">` : '—'}</div>`;
  }

  /* ---------- שליחה ---------- */
  function stepOf(name) {
    const el = form.elements[name];
    const node = el && (el.length && !el.tagName ? el[0] : el);
    return node ? steps.findIndex(s => s.contains(node)) : -1;
  }

  // שגיאות ולידציה מהשרת: מסמנים את השדות וקופצים לשלב של השגיאה הראשונה
  function showServerErrors(errors) {
    let firstStep = steps.length;
    for (const [name, msgs] of Object.entries(errors)) {
      const idx = name === 'signature' ? steps.length - 1 : stepOf(name);
      if (idx < 0) continue;
      firstStep = Math.min(firstStep, idx);
    }
    if (firstStep < steps.length) showStep(firstStep);
    for (const [name, msgs] of Object.entries(errors)) {
      if (name === 'signature') { $('#sigField').classList.add('has-error'); setError($('#sigCanvas'), msgs[0]); continue; }
      const el = form.elements[name];
      if (!el) continue;
      setError(el.length && !el.tagName ? el[0] : el, msgs[0]);
    }
  }

  async function post(payload, retry = true) {
    const res = await fetch(CFG.submitUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CFG.csrfToken },
      body: JSON.stringify(payload)
    });
    // דף שנשאר פתוח יותר מזמן הסשן: מביאים טוקן חדש ומנסים פעם נוספת
    if (res.status === 419 && retry && CFG.tokenUrl) {
      const t = await fetch(CFG.tokenUrl, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
      CFG.csrfToken = t.token;
      return post(payload, false);
    }
    const body = await res.json().catch(() => ({}));
    return { status: res.status, body };
  }

  function showDone(d, branchName) {
    $('#doneName').textContent = d.firstNameHe;
    $('#doneBranch').textContent = branchName || '';
    if (CFG.demoMode) {
      const branch = CFG.branches.find(b => b.id === d.branch);
      $('#mailTo').textContent = (branch?.emails || []).join(', ');
      $('#mailSubject').textContent = `טופס רישום הנדסאים - ${d.firstNameHe} ${d.lastNameHe} - ${d.major}`;
      $('#mailAttachment').textContent = `registration-${d.idNumber.padStart(9, '0')}-${isoToday().replace(/-/g, '')}.pdf`;
      $('#docBody').innerHTML = renderDoc(d);
    }
    clearTimeout(saveTimer);
    clearStorage();
    $('#wizard').hidden = true;
    $('#doneScreen').hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  let submitting = false;
  async function submit(e) {
    e.preventDefault();
    if (submitting) return; // הגנה מפני לחיצה כפולה
    for (let i = 0; i < steps.length; i++) {
      if (i !== state.step) { showStep(i, { scroll: false }); }
      if (!validateStep(i)) return;
    }
    const d = readForm();
    if (form.elements.website.value) return; // honeypot
    submitting = true;
    const btn = $('#submitBtn');
    btn.disabled = true; btn.textContent = 'שולח…';

    try {
      if (CFG.demoMode) {
        await new Promise(r => setTimeout(r, 1200));
        showDone(d, CFG.branches.find(b => b.id === d.branch)?.name);
        return;
      }
      const { status, body } = await post({ ...d, website: form.elements.website.value, signature: state.signature });
      if (status === 200 && body.ok) { showDone(d, body.branch); return; }
      if (status === 422 && body.errors) { showServerErrors(body.errors); toast('יש לתקן את השדות המסומנים'); return; }
      toast(body.message || 'אירעה תקלה בשליחה. הפרטים שמורים, אפשר לנסות שוב.');
    } catch (err) {
      toast('אין חיבור לשרת. הפרטים שמורים במכשיר, אפשר לנסות שוב.');
    } finally {
      submitting = false;
      btn.disabled = false; btn.textContent = 'שליחת טופס הרישום';
    }
  }

  /* ---------- הודעות ---------- */
  let toastTimer;
  function toast(msg) {
    const t = $('#toast');
    t.textContent = msg; t.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.hidden = true; }, 2800);
  }

  /* ---------- התחלה ---------- */
  function startWizard(step = 0) {
    $('#introScreen').hidden = true;
    $('#doneScreen').hidden = true;
    $('#wizard').hidden = false;
    showStep(step);
  }

  function resetAll() {
    clearTimeout(saveTimer);
    form.reset();
    if (lockedBranch) $('#branch').value = lockedBranch.id;
    populateMajors();
    state.step = 0; state.maxStep = 0; state.signature = null; state.data = {};
    sig.clear(true);
    clearStorage();
    $$('.has-error', form).forEach(el => el.classList.remove('has-error'));
    $$('.field-error', form).forEach(el => el.remove());
  }

  function init() {
    populateStatic();

    const saved = load();
    if (saved) {
      writeForm(saved.data);
      state.signature = saved.signature || null;
      state.maxStep = saved.maxStep || 0;
      state.step = saved.step || 0;
      const when = new Date(saved.savedAt).toLocaleDateString('he-IL');
      const name = saved.data.firstNameHe ? `${saved.data.firstNameHe}, ` : '';
      $('#resumeMeta').textContent = `${name}נשמר לאחרונה ב-${when} · שלב ${state.step + 1} מתוך ${steps.length}`;
      $('#resumeBox').hidden = false;
      $('#startActions').hidden = true;
    }
    if (lockedBranch) {
      $('.intro h2').textContent = `ברוכים הבאים לרישום ללימודי הנדסאים · שלוחת ${lockedBranch.name}`;
    }

    $('#startBtn').addEventListener('click', () => startWizard(0));
    $('#resumeBtn').addEventListener('click', () => startWizard(state.step));
    $('#restartBtn').addEventListener('click', () => { resetAll(); startWizard(0); });
    $('#newFormBtn').addEventListener('click', () => { resetAll(); startWizard(0); });

    $('#clearDataBtn').addEventListener('click', () => {
      if (!confirm('למחוק את כל הנתונים שנשמרו במכשיר זה?')) return;
      resetAll();
      $('#wizard').hidden = true; $('#doneScreen').hidden = true;
      $('#introScreen').hidden = false; $('#resumeBox').hidden = true; $('#startActions').hidden = false;
      toast('הנתונים נמחקו מהמכשיר');
    });

    $('#nextBtn').addEventListener('click', () => {
      if (!validateStep(state.step)) return;
      showStep(state.step + 1);
      save();
    });
    $('#prevBtn').addEventListener('click', () => { showStep(state.step - 1); save(); });

    document.addEventListener('click', e => {
      const btn = e.target.closest('[data-goto]');
      if (!btn) return;
      const target = Number(btn.dataset.goto);
      if (target <= state.maxStep && target !== state.step) { showStep(target); save(); }
    });

    form.addEventListener('input', e => {
      if (e.target.name === 'idNumber') e.target.value = e.target.value.replace(/\D/g, '');
      if (fieldWrap(e.target).classList.contains('has-error') && e.target.type !== 'radio') validateField(e.target);
      applyConditions(); applyFills(); scheduleSave();
    });
    form.addEventListener('change', e => {
      if (e.target.name === 'branch') populateMajors();
      if (e.target.type === 'radio' || e.target.type === 'checkbox' || e.target.tagName === 'SELECT') validateField(e.target);
      applyConditions(); applyFills(); scheduleSave();
    });
    form.addEventListener('focusout', e => {
      const el = e.target;
      if (el.matches('input:not([type=radio]):not([type=checkbox]), select') && el.value) validateField(el);
    });
    form.addEventListener('submit', submit);
    form.addEventListener('keydown', e => {
      if (e.key === 'Enter' && e.target.tagName === 'INPUT') { e.preventDefault(); $('#nextBtn').hidden || $('#nextBtn').click(); }
    });

    if (CFG.demoMode) {
      $('#previewDocBtn').addEventListener('click', () => { $('#docOverlay').hidden = false; document.body.style.overflow = 'hidden'; });
      $('#closeDocBtn').addEventListener('click', () => { $('#docOverlay').hidden = true; document.body.style.overflow = ''; });
      $('#printDocBtn').addEventListener('click', () => window.print());
    }

    applyConditions();
    applyFills();
  }

  init();
})();
