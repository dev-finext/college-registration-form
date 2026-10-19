<?php

namespace Tests\Feature;

use App\Services\Registration\RegistrationCard;
use App\Services\Registration\RegistrationCardData;
use App\Services\Registration\RegistrationCardRenderer;
use Carbon\CarbonImmutable;
use RuntimeException;
use Tests\TestCase;

/**
 * Renders a card for real, rather than against a stubbed renderer.
 *
 * Worth the second or so it costs: this is what would catch a missing font, a
 * broken template or an encoding regression, none of which a stubbed renderer
 * can see.
 */
class RegistrationCardRenderingTest extends TestCase
{
    private function registration(array $overrides = []): array
    {
        return array_merge(require base_path('tests/Fixtures/registration.php'), $overrides);
    }

    private function at(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-19 14:05:09');
    }

    public function test_a_card_renders_to_a_real_pdf(): void
    {
        $pdf = app(RegistrationCard::class)->pdf($this->registration(), $this->at());

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('%%EOF', substr($pdf, -1024));
        $this->assertGreaterThan(20_000, strlen($pdf), 'A card with its fonts and signature should not be nearly empty.');
    }

    public function test_the_template_puts_hebrew_and_a_hebrew_capable_font_into_mpdf(): void
    {
        // A check on the template, not on what mPDF makes of it: the Hebrew
        // reaches mPDF and is asked for in a font that carries it.
        $html = app(RegistrationCard::class)->html($this->registration(), $this->at());

        $this->assertStringContainsString('פרטי הסטודנט/ית', $html);
        $this->assertStringContainsString('ענת', $html);
        $this->assertStringContainsString('dejavusans', $html);
    }

    public function test_the_card_carries_what_the_branch_needs(): void
    {
        $html = app(RegistrationCard::class)->html($this->registration(), $this->at());

        $this->assertStringContainsString('REG-261019-1405-6782', $html);
        $this->assertStringContainsString('שלוחת ירושלים', $html);
        $this->assertStringContainsString('050-5773045', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html, 'The signature is missing.');
        // Blade escapes the quotes in מה"ט, so compare against the escaped text.
        $this->assertStringContainsString(e(config('registration.tuition_text')), $html);
        $this->assertStringContainsString(e(config('registration.clauses')[24]), $html);
    }

    public function test_an_empty_optional_field_is_shown_as_empty_not_left_blank(): void
    {
        $card = app(RegistrationCardData::class)->for($this->registration(['phone2' => null]), $this->at());
        $contact = collect($card['sections'])->firstWhere('title', 'דרכי התקשרות ומשפחה');
        $phone2 = collect($contact['rows'])->flatMap(fn ($row) => $row['fields'])->firstWhere('label', 'טלפון נוסף');

        $this->assertNull($phone2['value']);
        $this->assertStringContainsString(
            config('registration.pdf_empty_label'),
            app(RegistrationCard::class)->html($this->registration(['phone2' => null]), $this->at()),
        );
    }

    public function test_fields_that_do_not_apply_are_left_off(): void
    {
        $html = app(RegistrationCard::class)->html($this->registration([
            'birthCountry' => 'ישראל',
            'aliyahDate' => null,
            'serviceType' => 'לא',
            'personalNumber' => null,
            'enlistDate' => null,
            'releaseDate' => null,
        ]), $this->at());

        $this->assertStringNotContainsString('תאריך עלייה', $html);
        $this->assertStringNotContainsString('תאריך גיוס', $html);
        $this->assertStringNotContainsString('מס\' אישי', $html);
    }

    public function test_declarations_record_what_was_and_was_not_approved(): void
    {
        $card = app(RegistrationCardData::class)->for($this->registration(), $this->at());
        $placement = collect($card['declarations'])->firstWhere('text', config('registration.consent_placement'));
        $commitment = collect($card['declarations'])->firstWhere('text', config('registration.commitment_confirm'));

        $this->assertFalse($placement['accepted']);
        $this->assertNull($placement['at']);
        $this->assertTrue($commitment['accepted']);
        $this->assertTrue($commitment['required']);
        $this->assertSame('19.10.2026 14:05:09', $commitment['at']);
    }

    public function test_the_busiest_possible_registration_still_renders(): void
    {
        $pdf = app(RegistrationCard::class)->pdf($this->registration([
            'firstNameHe' => 'אלכסנדרה-מרגריטה',
            'lastNameHe' => 'בן-אברהם רוזנבלום',
            'firstNameEn' => 'Alexandra-Margarita',
            'lastNameEn' => 'Ben-Abraham Rosenblum',
            'birthCountry' => 'הרפובליקה הדמוקרטית של קונגו',
            'street' => 'שדרות יהודית ובנימין רוטשילד פינת הרצל',
            'email' => 'alexandra.margarita.ben-abraham@a-very-long-engineering-domain.co.il',
            'consentPlacement' => true,
            'waiverHebrew' => true,
        ]), $this->at());

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_without_a_logo_file_the_header_falls_back_to_the_college_name(): void
    {
        $card = app(RegistrationCardData::class)->for($this->registration(), $this->at());

        if ($card['logo'] !== null) {
            $this->assertFileExists($card['logo']);
            $this->markTestSkipped('A logo file is present.');
        }

        $html = app(RegistrationCard::class)->html($this->registration(), $this->at());
        $this->assertStringContainsString('<span class="brand-text">המכללה למינהל</span>', $html);
    }

    public function test_an_unusable_temporary_directory_is_reported_instead_of_failing_inside_mpdf(): void
    {
        $blocked = storage_path('app/mpdf-blocked-'.uniqid());

        // A file where the directory should be. mkdir cannot create it and
        // is_dir stays false, on every platform — no chmod involved, which is
        // what makes this reliable on Windows as well as on the server.
        file_put_contents($blocked, 'not a directory');

        $renderer = new class($blocked) extends RegistrationCardRenderer
        {
            public function __construct(private readonly string $path) {}

            protected function tempPath(): string
            {
                return $this->path;
            }
        };

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('temporary directory');

            $renderer->pdf('<p>שלום</p>');
        } finally {
            unlink($blocked);
        }
    }
}
