/*
 * נתוני תצורה לדמו בלבד.
 * רשימת השלוחות, המגמות וכתובות הדוא"ל תתקבל מהמכללה ותחליף את הערכים כאן.
 * בגרסת Laravel הנתונים יעברו ל-config/branches.php ויוזרקו ל-Blade.
 */
window.APP_CONFIG = {
  collegeName: 'המכללה למינהל — בית הספר להנדסאים',
  formTitle: 'טופס רישום להנדסאים',
  storageTtlDays: 365,
  demoMode: true,

  branches: [
    {
      id: 'rishon',
      name: 'ראשון לציון',
      emails: ['manager.rishon@example.org', 'office.rishon@example.org'],
      majors: ['תוכנה', 'חשמל', 'אדריכלות ועיצוב פנים', 'מכשור רפואי', 'תעשייה וניהול']
    },
    {
      id: 'jerusalem',
      name: 'ירושלים',
      emails: ['manager.jerusalem@example.org'],
      majors: ['תוכנה', 'חשמל', 'אדריכלות ועיצוב פנים']
    },
    {
      id: 'haifa',
      name: 'חיפה',
      emails: ['manager.haifa@example.org', 'secretary.haifa@example.org'],
      majors: ['תוכנה', 'מכונות', 'תעשייה וניהול']
    },
    {
      id: 'beersheva',
      name: 'באר שבע',
      emails: ['manager.beersheva@example.org'],
      majors: ['תוכנה', 'חשמל', 'מכשור רפואי']
    }
  ],

  // רשימות בחירה. ערכים שמסומנים "לאישור" עדיין לא התקבלו מהמכללה.
  studyTracks: ['בוקר', 'ערב'],
  certificateTypes: ['בגרות מלאה', 'בגרות חלקית', '12 שנות לימוד', 'תעודת טכנאי', 'אחר'],
  infoSources: ['אתר המכללה', 'גוגל', 'פייסבוק / אינסטגרם', 'חבר/ה או בן/בת משפחה', 'יום פתוח', 'אחר']
};
