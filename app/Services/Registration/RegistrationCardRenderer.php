<?php

namespace App\Services\Registration;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;

/**
 * Turns the registration card's HTML into a PDF.
 *
 * mPDF does the work in PHP, with no browser, no node and no child process.
 * The card is Hebrew and right-to-left, and mPDF carries its own fonts, so the
 * result is identical on every server instead of depending on which fonts
 * happen to be installed.
 *
 * The trade is that mPDF understands table-based layout and not flexbox, which
 * is why the template is built from tables.
 */
class RegistrationCardRenderer
{
    public function pdf(string $html): string
    {
        $mpdf = $this->document();

        $mpdf->SetHTMLFooter($this->footer());
        $mpdf->WriteHTML($html, HTMLParserMode::DEFAULT_MODE);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function document(): Mpdf
    {
        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            // Lays the whole page out right-to-left, including table columns.
            'directionality' => 'rtl',
            // Carried by mPDF itself, so Hebrew renders the same everywhere.
            'default_font' => 'dejavusans',
            'default_font_size' => 8.4,
            'margin_left' => 11,
            'margin_right' => 11,
            'margin_top' => 10,
            'margin_bottom' => 15,
            'margin_footer' => 8,
            'tempDir' => $this->tempDir(),
        ]);
    }

    /**
     * Where mPDF is allowed to write while it builds a document.
     *
     * The mode is 0775 rather than 0755 because this can run as two different
     * users: php-fpm renders the candidate's submission, and
     * `php artisan registration:card` is run from a shell. Whichever gets
     * there first creates the directory, and at 0755 it locks the other out.
     *
     * Both the creation and the result are checked. An unwritable temp
     * directory otherwise fails deep inside mPDF, part-way through a render,
     * and surfaces as a vague 503 on the candidate's submit instead of as
     * something an operator can read in the log and act on.
     */
    protected function tempDir(): string
    {
        $temp = $this->tempPath();

        // mkdir is silenced on purpose. Laravel turns PHP warnings into
        // ErrorException, so an unsilenced failure here throws "mkdir(): File
        // exists" from inside the framework and the readable message below
        // never gets the chance to be the one an operator sees.
        //
        // The second is_dir() is not redundant: under php-fpm two requests can
        // reach the mkdir together, and the one that loses sees it fail.
        if (! is_dir($temp) && ! @mkdir($temp, 0775, true) && ! is_dir($temp)) {
            throw new RuntimeException("Could not create mPDF's temporary directory at {$temp}.");
        }

        if (! is_writable($temp)) {
            throw new RuntimeException("mPDF's temporary directory is not writable: {$temp}.");
        }

        return $temp;
    }

    /** Split out so a test can point the checks above at a path it controls. */
    protected function tempPath(): string
    {
        return storage_path('app/mpdf');
    }

    /**
     * Page numbers come from mPDF rather than from the template, so a card that
     * grows past one page still numbers itself honestly.
     */
    private function footer(): string
    {
        return '<div style="border-top:0.4mm solid #e4e4e4; padding-top:1.5mm;'
            .' font-family:dejavusans; font-size:7pt; color:#8a8878;">'
            .'<table width="100%" style="border:none;"><tr>'
            .'<td style="border:none; text-align:right;">'.e(config('registration.college_name')).' — טופס רישום</td>'
            .'<td style="border:none; text-align:left;">עמוד {PAGENO} מתוך {nbpg}</td>'
            .'</tr></table></div>';
    }
}
