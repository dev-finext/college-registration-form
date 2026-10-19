<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;

/**
 * Renders the signed registration as a Hebrew, right-to-left PDF.
 * The file is built in memory and never written to the server.
 */
class RegistrationPdf
{
    public function render(array $data): string
    {
        $tempDir = storage_path('framework/mpdf');
        File::ensureDirectoryExists($tempDir);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            // DejaVu Sans covers Hebrew, Latin and punctuation with a real bold weight,
            // so one font is used throughout instead of mPDF's per-language fallback.
            'autoScriptToLang' => true,
            'autoLangToFont' => false,
            'margin_top' => 14,
            'margin_bottom' => 16,
            'margin_left' => 14,
            'margin_right' => 14,
            'tempDir' => $tempDir,
        ]);

        $mpdf->SetTitle(config('registration.form_title').' - '.$data['firstNameHe'].' '.$data['lastNameHe']);
        $mpdf->SetAuthor(config('registration.college_name'));
        $mpdf->SetHTMLFooter('<div style="text-align: center; font-size: 8pt; color: #6b7486;">עמוד {PAGENO} מתוך {nbpg}</div>');

        $signature = base64_decode(substr($data['signature'], strlen('data:image/png;base64,')), true);
        if ($signature !== false) {
            $mpdf->imageVars['signature'] = $signature;
        }

        $mpdf->WriteHTML(view('pdf.registration', [
            'd' => $data,
            'cfg' => config('registration'),
            'branch' => config('registration.branches.'.$data['branch']),
            'hasSignature' => $signature !== false,
            'today' => now()->format('d/m/Y'),
        ])->render());

        return $mpdf->Output('', 'S');
    }

    public static function filename(array $data): string
    {
        return 'registration-'.$data['idNumber'].'-'.now()->format('Ymd').'.pdf';
    }
}
