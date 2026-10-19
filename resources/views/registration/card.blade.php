{{--
    The registration card a branch manager reads: every answer the candidate
    gave, the approvals they ticked, the commitment text they signed, and the
    signature itself.

    Laid out with tables rather than with flexbox, because mPDF renders this —
    see App\Services\Registration\RegistrationCardRenderer. Fields arrive
    already packed into rows; the column maths lives in RegistrationCardData.

    Values are printed exactly as captured. An optional field left empty shows
    as {{ config('registration.pdf_empty_label') }} rather than as a blank, so
    the branch can tell "skipped" from "missing from the card".
--}}
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $card['reference'] }}</title>
    <style>
        body { font-family: dejavusans; font-size: 8.4pt; color: #1d1d1b; }

        .eyebrow { font-size: 7.2pt; color: #8a8878; margin: 0 0 1mm; }
        .who { font-size: 17pt; font-weight: bold; color: #1f8dbb; margin: 0; }
        .meta { font-size: 7.4pt; color: #6b6b6b; margin: 1mm 0 0; }
        .brand-text { font-size: 13pt; font-weight: bold; color: #1f8dbb; }

        table.head { width: 100%; border: none; margin-bottom: 1mm; }
        table.head td { border: none; vertical-align: top; padding: 0; }

        .rule { border-bottom: 0.6mm solid #1f8dbb; margin: 2mm 0 3mm; }

        table.badge { border: 0.35mm solid #1f8dbb; border-radius: 1.5mm; background-color: #e8f4f9; }
        table.badge td { border: none; text-align: center; padding: 1.6mm 3.5mm; }
        .badge-title { font-size: 9pt; font-weight: bold; color: #1f8dbb; }
        .badge-why { font-size: 6.8pt; color: #1d1d1b; }

        table.sec { width: 100%; border: 0.2mm solid #dcdcdc; margin-bottom: 2.2mm; }
        table.sec td { border: none; padding: 1.2mm 2.2mm; vertical-align: top; }
        td.sec-title {
            background-color: #eef4f7; border-bottom: 0.2mm solid #dcdcdc;
            font-size: 8.4pt; font-weight: bold; color: #1f8dbb; padding: 1.4mm 2.5mm;
        }
        td.k { color: #6b6b6b; text-align: right; font-size: 7.5pt; }
        td.v { font-weight: bold; }
        .null { font-weight: normal; color: #b0b0b0; font-size: 7.4pt; }
        .mono { font-family: dejavusansmono; font-size: 7.6pt; }

        table.dec { width: 100%; border: none; }
        table.dec td { border: none; border-bottom: 0.15mm solid #efefef; padding: 1.4mm 2.5mm; vertical-align: top; }
        td.mark { font-weight: bold; width: 23mm; }
        .yes { color: #1e6b33; }
        .no { color: #a32121; }
        .req { color: #ff8a00; font-weight: bold; }
        td.when { color: #8a8878; font-size: 6.4pt; width: 34mm; text-align: left; }

        .tuition { font-weight: bold; font-size: 8.6pt; }
        ol.clauses { margin: 1mm 0; padding-right: 5mm; }
        ol.clauses li { font-size: 7pt; color: #3a3a38; margin-bottom: 0.6mm; }
        table.charges { width: 100%; border-collapse: collapse; margin-top: 1.5mm; }
        table.charges td, table.charges th { border: 0.15mm solid #dcdcdc; padding: 1mm 2mm; font-size: 7pt; text-align: right; }
        table.charges th { background-color: #1f8dbb; color: #ffffff; font-weight: bold; }
    </style>
</head>
<body>

<table class="head">
    <tr>
        {{-- Right-hand side of the page: the document is laid out RTL. --}}
        <td width="25%">
            @if ($card['logo'])
                <img src="{{ $card['logo'] }}" style="width: 38mm;">
            @else
                <span class="brand-text">המכללה למינהל</span>
            @endif
        </td>
        <td width="48%" style="padding-top: 2mm;">
            <p class="eyebrow">טופס רישום · {{ config('registration.college_name') }}</p>
            <p class="who">{{ $card['name'] }}</p>
            <p class="meta">
                {{-- A Hebrew word between the reference and the date keeps the
                     bidi algorithm from merging them into one LTR run. --}}
                אסמכתא <b>{{ $card['reference'] }}</b> &nbsp;·&nbsp;
                נשלח ב-{{ $card['generated_at'] }} מהטופס הדיגיטלי
            </p>
        </td>
        <td width="27%" align="left" style="padding-top: 2mm;">
            <table class="badge" align="left">
                <tr><td>
                    <span class="badge-title">שלוחת {{ $card['branch'] }}</span>
                    <br><span class="badge-why">מגמת {{ $card['major'] }}</span>
                    <br><span class="badge-why">{{ $card['term'] }}</span>
                </td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="rule"></div>

@foreach ($card['sections'] as $section)
    <table class="sec">
        <tr><td class="sec-title" colspan="6">{{ $section['title'] }}</td></tr>
        @foreach ($section['rows'] as $row)
            <tr>
                @foreach ($row['fields'] as $field)
                    <td class="k" width="17%">{{ $field['label'] }}</td>
                    <td class="v" @if ($field['wide']) colspan="3" @endif>
                        @if ($field['value'] === null)
                            <span class="null">{{ config('registration.pdf_empty_label') }}</span>
                        @else
                            <span @class(['mono' => $field['mono']])>{{ $field['value'] }}</span>
                        @endif
                    </td>
                @endforeach
                @if ($row['pad'] > 0)
                    <td colspan="{{ $row['pad'] }}"></td>
                @endif
            </tr>
        @endforeach
    </table>
@endforeach

<table class="sec">
    <tr><td class="sec-title" colspan="3">הצהרות ואישורים</td></tr>
    <tr><td colspan="3" style="padding: 0;">
        <table class="dec">
            @foreach ($card['declarations'] as $declaration)
                <tr>
                    <td class="mark {{ $declaration['accepted'] ? 'yes' : 'no' }}">
                        {{ $declaration['accepted'] ? '✔ אושר' : '✘ לא אושר' }}
                    </td>
                    <td>{{ $declaration['text'] }}@if ($declaration['required'])<span class="req">*</span>@endif</td>
                    <td class="when mono">{{ $declaration['at'] ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    </td></tr>
</table>

<table class="sec">
    <tr><td class="sec-title">חתימה דיגיטלית</td></tr>
    <tr><td>
        @if ($card['signature'])
            <img src="{{ $card['signature'] }}" style="width: 62mm;">
        @else
            <span class="null">{{ config('registration.pdf_empty_label') }}</span>
        @endif
        <br><span class="meta">{{ $card['name'] }} · {{ $card['generated_at'] }}</span>
    </td></tr>
</table>

{{-- What the candidate signed, word for word, so the card stands on its own.
     An appendix on its own page, so its heading never strands at a page foot. --}}
<pagebreak />
<table class="sec">
    <tr><td class="sec-title">כתב התחייבות והצהרת הסטודנט — הנוסח שנחתם</td></tr>
    <tr><td>
        <p class="tuition">{{ $card['commitment']['tuition'] }}</p>
        <p>{{ $card['commitment']['declaration'] }}</p>
        <ol class="clauses">
            @foreach ($card['commitment']['clauses'] as $clause)
                <li>{{ $clause }}</li>
            @endforeach
        </ol>
        <table class="charges">
            <tr><th>מועד ההודעה</th><th>היקף החיוב</th></tr>
            @foreach ($card['commitment']['charges'] as [$when, $charge])
                <tr><td>{{ $when }}</td><td>{{ $charge }}</td></tr>
            @endforeach
        </table>
    </td></tr>
</table>

</body>
</html>
