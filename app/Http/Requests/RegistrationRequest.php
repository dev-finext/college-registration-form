<?php

namespace App\Http\Requests;

use App\Rules\IsraeliId;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegistrationRequest extends FormRequest
{
    private const HEBREW = '/^[\x{0590}-\x{05FF}\s\'"\-׳״]+$/u';

    private const ENGLISH = '/^[A-Za-z\s\'\-]+$/';

    private const BOOLEANS = [
        'commitmentAccepted', 'consentPlacement', 'consentMessages',
        'waiverMath', 'waiverEnglish', 'waiverHebrew', 'waiverAccepted',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $digits = fn ($v) => $v === null ? null : preg_replace('/\D/', '', (string) $v);
        $data = [];

        foreach (['idNumber', 'mobile', 'phone2', 'zip', 'personalNumber'] as $field) {
            if ($this->has($field)) {
                $data[$field] = $digits($this->input($field)) ?: null;
            }
        }

        if (! empty($data['idNumber']) && strlen($data['idNumber']) <= 9) {
            $data['idNumber'] = str_pad($data['idNumber'], 9, '0', STR_PAD_LEFT);
        }

        foreach (self::BOOLEANS as $field) {
            $data[$field] = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN);
        }

        // Fields that only apply under a condition are dropped otherwise.
        if (trim((string) $this->input('birthCountry')) === 'ישראל') {
            $data['aliyahDate'] = null;
        }
        if ($this->input('serviceType') !== 'צה"ל') {
            $data['personalNumber'] = null;
        }
        if ($this->input('serviceType') === 'לא') {
            $data['enlistDate'] = null;
            $data['releaseDate'] = null;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $cfg = config('registration');
        $date = ['nullable', 'date_format:Y-m-d'];

        return [
            'firstNameHe' => ['required', 'string', 'max:50', 'regex:'.self::HEBREW],
            'lastNameHe' => ['required', 'string', 'max:50', 'regex:'.self::HEBREW],
            'firstNameEn' => ['required', 'string', 'max:50', 'regex:'.self::ENGLISH],
            'lastNameEn' => ['required', 'string', 'max:50', 'regex:'.self::ENGLISH],
            'idNumber' => ['required', 'digits:9', new IsraeliId],
            'branch' => ['required', Rule::in(array_keys($cfg['branches']))],
            'major' => ['required', Rule::in($cfg['majors'])],
            'startTerm' => ['required', Rule::in($cfg['start_terms'])],
            'studyTrack' => ['required', Rule::in($cfg['study_tracks'])],

            'certificateType' => ['required', Rule::in($cfg['certificate_types'])],
            'birthDate' => ['required', 'date_format:Y-m-d', 'before:today'],
            'birthCountry' => ['required', 'string', 'max:60'],
            'aliyahDate' => [...$date, 'required_unless:birthCountry,ישראל', 'before_or_equal:today'],
            'gender' => ['required', Rule::in($cfg['genders'])],
            'city' => ['required', 'string', 'max:80'],
            'street' => ['required', 'string', 'max:80'],
            'houseNumber' => ['nullable', 'string', 'max:10'],
            'zip' => ['nullable', 'digits:7'],

            'mobile' => ['required', 'regex:/^05\d{8}$/'],
            'phone2' => ['nullable', 'regex:/^0\d{8,9}$/'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'fatherName' => ['nullable', 'string', 'max:50'],
            'motherName' => ['nullable', 'string', 'max:50'],
            'serviceType' => ['required', Rule::in($cfg['service_types'])],
            'personalNumber' => ['nullable', 'digits_between:5,9'],
            'enlistDate' => [...$date, 'before_or_equal:today'],
            'releaseDate' => [...$date, 'after:enlistDate'],
            'infoSource' => ['nullable', Rule::in($cfg['info_sources'])],

            'commitmentAccepted' => ['accepted'],
            'consentPlacement' => ['boolean'],
            'consentMessages' => ['boolean'],
            'waiverMath' => ['boolean'],
            'waiverEnglish' => ['boolean'],
            'waiverHebrew' => ['boolean'],
            'waiverAccepted' => ['boolean'],

            // ~300KB of base64 is far more than a hand-drawn PNG signature needs.
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:400000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $birth = $this->date('birthDate');
                if (! $birth || $validator->errors()->has('birthDate')) {
                    return;
                }

                $age = $birth->diffInYears(CarbonImmutable::today());
                if ($age < 15 || $age > 90) {
                    $validator->errors()->add('birthDate', 'יש לבדוק את תאריך הלידה');
                }

                $aliyah = $this->date('aliyahDate');
                if ($aliyah && $aliyah->lt($birth)) {
                    $validator->errors()->add('aliyahDate', 'תאריך העלייה מוקדם מתאריך הלידה');
                }

                $enlist = $this->date('enlistDate');
                if ($enlist && $birth->diffInYears($enlist) < 16) {
                    $validator->errors()->add('enlistDate', 'תאריך הגיוס לא מתאים לתאריך הלידה');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'שדה חובה',
            'required_unless' => 'שדה חובה',
            'in' => 'יש לבחור ערך מהרשימה',
            'regex' => 'הערך שהוזן אינו תקין',
            'firstNameHe.regex' => 'יש להזין אותיות בעברית בלבד',
            'lastNameHe.regex' => 'יש להזין אותיות בעברית בלבד',
            'firstNameEn.regex' => 'Please use English letters only',
            'lastNameEn.regex' => 'Please use English letters only',
            'mobile.regex' => 'מספר נייד לא תקין (לדוגמה 050-1234567)',
            'phone2.regex' => 'מספר טלפון לא תקין',
            'idNumber.digits' => 'מספר תעודת הזהות אינו תקין',
            'zip.digits' => 'מיקוד צריך להכיל 7 ספרות',
            'email' => 'כתובת דוא"ל לא תקינה',
            'max' => 'הערך ארוך מדי',
            'date_format' => 'תאריך לא תקין',
            'before' => 'התאריך לא יכול להיות עתידי',
            'before_or_equal' => 'התאריך לא יכול להיות עתידי',
            'releaseDate.after' => 'תאריך השחרור חייב להיות אחרי תאריך הגיוס',
            'digits_between' => 'מספר לא תקין',
            'accepted' => 'יש לאשר כדי להמשיך',
            'signature.*' => 'נדרשת חתימה',
        ];
    }
}
