# טופס רישום דיגיטלי להנדסאים — המכללה למינהל

טופס רישום דיגיטלי רב-שלבי ללימודי הנדסאים (Laravel 13 + Blade). בסיום הרישום השרת מפיק PDF בעברית ושולח אותו בדוא"ל לכל הכתובות של השלוחה שנבחרה.

- **דמו UI (GitHub Pages):** https://dev-finext.github.io/college-registration-form/ (שום נתון לא נשלח)
- **קישור ייעודי לשלוחה:** `/{branch}` בשרת (למשל `/jerusalem`), `?branch=jerusalem` בדמו

## עקרונות

- **אין DB.** ההתקדמות של המועמד נשמרת ב-`localStorage` בדפדפן שלו עד 12 חודשים. המסמך לא נשמר בשרת: המייל לשלוחה הוא העותק היחיד.
- **מקור אמת אחד:** השלוחות, המגמות, המיילים, הרשימות והנוסח המשפטי נמצאים ב-[config/registration.php](config/registration.php), ומשמשים גם את הטופס וגם את ה-PDF.
- כתובות המייל של השלוחות לא נשלחות לדפדפן.

## מה יש

| נושא | מימוש |
|---|---|
| 6 שלבים, רספונסיבי, RTL | `resources/views/registration/**`, `public/assets/{css,js}` |
| ספרת ביקורת בת"ז | בדפדפן ב-`app.js`, בשרת ב-`App\Rules\IsraeliId` |
| עקביות תאריכים ושדות חובה | בדפדפן, ובשרת ב-`App\Http\Requests\RegistrationRequest` |
| שם ות"ז פעם אחת, מילוי אוטומטי | `data-fill` ב-Blade |
| חתימה על המסך | canvas → PNG על רקע לבן |
| PDF בעברית | אותו מבנה כמו במדנס: `RegistrationCardData` (שדות בשורות) → `registration/card.blade.php` (טבלאות) → `RegistrationCardRenderer` (mPDF) |
| מייל לשלוחה, כמה כתובות | `App\Mail\RegistrationSubmitted`, נושא: `טופס רישום הנדסאים - {שם} - {מגמה}`, Reply-To למועמד |
| הגנה מפני הצפה | throttle לפי IP (3 בדקה, 10 בשעה), חסימת שליחה כפולה של אותו מועמד ל-10 דקות, honeypot, CSRF |

## פיתוח מקומי

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

- `MAIL_MAILER=log`: המייל (כולל ה-PDF) נכתב ל-`storage/logs/laravel.log`
- `php artisan registration:card --out=sample.pdf`: מפיק PDF לדוגמה מנתוני הבדיקה
- בדיקות: `php artisan test`

## עדכון הדמו ב-GitHub Pages

הדמו נוצר מאותם קבצי Blade, כך שאינו מתרחק מהאפליקציה:

```bash
php artisan demo:export
```

הפקודה כותבת `docs/index.html` ומעתיקה את `public/assets` ל-`docs/assets`. אחרי commit ו-push, Pages מתעדכן.

## העלאה לשרת (Cloudways)

1. PHP 8.3+, document root ל-`public/`
2. `composer install --no-dev --optimize-autoloader`
3. הגדרות `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, פרטי SMTP (`MAIL_*`), `MAIL_FROM_ADDRESS` מדומיין עם SPF/DKIM
4. `php artisan key:generate && php artisan config:cache && php artisan route:cache && php artisan view:cache`
5. הרשאות כתיבה ל-`storage/` ול-`bootstrap/cache/`
6. SSL (Let's Encrypt ב-Cloudways)
