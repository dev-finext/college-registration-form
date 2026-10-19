<?php

namespace App\Services\Registration;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Turns a validated registration into the shape the card template prints.
 *
 * Nothing is invented here. An optional field the candidate left empty stays
 * null and the card prints it as such, so the branch can tell a skipped field
 * from a filled one. Fields that do not apply to this candidate (an aliyah
 * date for someone born in Israel, service dates for someone who did not
 * serve) are left off the card rather than shown as empty.
 *
 * Fields arrive already arranged into rows. The card is rendered by mPDF,
 * which lays out with tables rather than with flexbox, so how many fields fit
 * on a line is decided here instead of in the stylesheet.
 */
class RegistrationCardData
{
    /** Columns in a section's table: three fields, each a label and a value. */
    private const ROW_COLUMNS = 6;

    /**
     * @param  array<string, mixed>  $d  RegistrationRequest::validated()
     * @return array<string, mixed>
     */
    public function for(array $d, CarbonInterface $submittedAt): array
    {
        $cfg = config('registration');
        $branch = $cfg['branches'][$d['branch']] ?? ['name' => $d['branch']];
        $served = ($d['serviceType'] ?? 'לא') !== 'לא';
        $at = $submittedAt->format('d.m.Y H:i:s');
        $logo = resource_path('images/college-logo.png');

        return [
            'logo' => is_file($logo) ? $logo : null,
            'reference' => self::reference($d, $submittedAt),
            'name' => trim($d['firstNameHe'].' '.$d['lastNameHe']),
            'generated_at' => $submittedAt->format('d.m.Y H:i'),
            'branch' => $branch['name'],
            'major' => $d['major'],
            'term' => $d['startTerm'].' · '.$d['studyTrack'],
            'sections' => [
                [
                    'title' => 'פרטי הסטודנט/ית',
                    'rows' => $this->rows([
                        $this->field('שם פרטי', $d['firstNameHe']),
                        $this->field('שם משפחה', $d['lastNameHe']),
                        $this->field('מס\' ת"ז', $d['idNumber'], mono: true),
                        $this->field('First name', $d['firstNameEn']),
                        $this->field('Last name', $d['lastNameEn']),
                        $this->field('תאריך הרשמה', $submittedAt->format('d.m.Y'), mono: true),
                        $this->field('שלוחה', $branch['name']),
                        $this->field('מגמה', $d['major']),
                        $this->field('סוג תעודה', $d['certificateType']),
                        $this->field('מועד פתיחת לימודים', $d['startTerm']),
                        $this->field('מסלול לימודים', $d['studyTrack']),
                    ]),
                ],
                [
                    'title' => 'פרטים אישיים וכתובת',
                    'rows' => $this->rows(array_filter([
                        $this->field('תאריך לידה', $this->date($d['birthDate']), mono: true),
                        $this->field('ארץ לידה', $d['birthCountry']),
                        filled($d['aliyahDate'] ?? null)
                            ? $this->field('תאריך עלייה', $this->date($d['aliyahDate']), mono: true)
                            : null,
                        $this->field('מגדר', $d['gender']),
                        $this->field('ישוב', $d['city']),
                        $this->field('רחוב / ת.ד.', $d['street']),
                        $this->field('מספר', $d['houseNumber'] ?? null),
                        $this->field('מיקוד', $d['zip'] ?? null, mono: true),
                    ])),
                ],
                [
                    'title' => 'דרכי התקשרות ומשפחה',
                    'rows' => $this->rows([
                        $this->field('טלפון נייד', $this->phone($d['mobile']), mono: true),
                        $this->field('טלפון נוסף', $this->phone($d['phone2'] ?? null), mono: true),
                        $this->field('דוא"ל', $d['email'], wide: true, mono: true),
                        $this->field('שם האב', $d['fatherName'] ?? null),
                        $this->field('שם האם', $d['motherName'] ?? null),
                    ]),
                ],
                [
                    'title' => 'שירות צבאי / לאומי ומקור מידע',
                    'rows' => $this->rows(array_filter([
                        $this->field('שירות', $d['serviceType']),
                        ($d['serviceType'] ?? null) === 'צה"ל'
                            ? $this->field('מס\' אישי', $d['personalNumber'] ?? null, mono: true)
                            : null,
                        $served ? $this->field('תאריך גיוס', $this->date($d['enlistDate'] ?? null), mono: true) : null,
                        $served ? $this->field('תאריך שחרור', $this->date($d['releaseDate'] ?? null), mono: true) : null,
                        $this->field('מקור מידע על המכללה', $d['infoSource'] ?? null, wide: true),
                    ])),
                ],
                [
                    'title' => 'ויתור על סודיות — מקצועות שאושרו להעברה למה"ט',
                    'rows' => $this->rows(array_map(
                        fn ($field, $label) => $this->field($label, ! empty($d[$field]) ? 'כן' : 'לא'),
                        array_keys($cfg['waiver_subjects']),
                        $cfg['waiver_subjects'],
                    )),
                ],
            ],
            'declarations' => [
                $this->declaration($cfg['commitment_confirm'], $d['commitmentAccepted'] ?? false, $at, required: true),
                $this->declaration($cfg['consent_placement'], $d['consentPlacement'] ?? false, $at),
                $this->declaration($cfg['consent_messages'], $d['consentMessages'] ?? false, $at),
                $this->declaration($cfg['waiver_confirm'], $d['waiverAccepted'] ?? false, $at),
            ],
            'commitment' => [
                'tuition' => $cfg['tuition_text'],
                'declaration' => $cfg['declaration'],
                'clauses' => $cfg['clauses'],
                'charges' => $cfg['charge_table'],
            ],
            'signature' => $this->signature($d['signature'] ?? null),
        ];
    }

    /**
     * A reference the branch can quote back to the candidate. There is no
     * database to number registrations, so it is built from the submission
     * time and the last digits of the ID: unique enough to find the email.
     */
    public static function reference(array $d, CarbonInterface $submittedAt): string
    {
        return 'REG-'.$submittedAt->format('ymd-Hi').'-'.substr((string) $d['idNumber'], -4);
    }

    /**
     * Packs fields into table rows. A normal field occupies a label column and
     * a value column; a wide one keeps its label column and lets its value run
     * across the rest of the line. A short last row is padded so its cells do
     * not stretch to fill the table.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function rows(array $fields): array
    {
        $rows = [];
        $current = [];
        $used = 0;

        foreach ($fields as $field) {
            $width = $field['wide'] ? 4 : 2;

            if ($used + $width > self::ROW_COLUMNS) {
                $rows[] = ['fields' => $current, 'pad' => self::ROW_COLUMNS - $used];
                $current = [];
                $used = 0;
            }

            $current[] = $field;
            $used += $width;
        }

        if ($current !== []) {
            $rows[] = ['fields' => $current, 'pad' => self::ROW_COLUMNS - $used];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function field(string $label, mixed $value, bool $wide = false, bool $mono = false): array
    {
        $value = is_string($value) ? trim($value) : $value;

        return [
            'label' => $label,
            'value' => ($value === null || $value === '') ? null : (string) $value,
            'wide' => $wide,
            'mono' => $mono,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function declaration(string $text, bool $accepted, string $at, bool $required = false): array
    {
        return [
            'text' => $text,
            'required' => $required,
            'accepted' => $accepted,
            // Every approval is given with the one signed submission.
            'at' => $accepted ? $at : null,
        ];
    }

    private function date(?string $iso): ?string
    {
        return filled($iso) ? Carbon::parse($iso)->format('d.m.Y') : null;
    }

    /** 0501234567 → 050-1234567, the way the branch will dial it. */
    private function phone(?string $digits): ?string
    {
        if (blank($digits)) {
            return null;
        }

        return strlen($digits) === 10
            ? substr($digits, 0, 3).'-'.substr($digits, 3)
            : substr($digits, 0, 2).'-'.substr($digits, 2);
    }

    /**
     * The drawn signature, kept as the PNG data URL the browser sent: mPDF
     * reads data URLs in an img tag, so the renderer only ever deals in HTML.
     */
    private function signature(?string $dataUrl): ?string
    {
        $prefix = 'data:image/png;base64,';

        if (! is_string($dataUrl) || ! str_starts_with($dataUrl, $prefix)) {
            return null;
        }

        return base64_decode(substr($dataUrl, strlen($prefix)), true) === false ? null : $dataUrl;
    }
}
