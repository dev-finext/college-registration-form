<?php

namespace App\Services\Registration;

use Carbon\CarbonInterface;

/**
 * Produces the PDF a branch receives for one registration.
 *
 * Nothing is filed: there is no database and the document is not written to
 * disk. The bytes go straight into the email, which is the only copy (as
 * agreed with the college).
 */
class RegistrationCard
{
    public function __construct(
        private readonly RegistrationCardData $data,
        private readonly RegistrationCardRenderer $renderer,
    ) {}

    /**
     * @param  array<string, mixed>  $registration  RegistrationRequest::validated()
     */
    public function pdf(array $registration, CarbonInterface $submittedAt): string
    {
        return $this->renderer->pdf($this->html($registration, $submittedAt));
    }

    /**
     * @param  array<string, mixed>  $registration
     */
    public function html(array $registration, CarbonInterface $submittedAt): string
    {
        return view('registration.card', [
            'card' => $this->data->for($registration, $submittedAt),
        ])->render();
    }

    public static function filename(array $registration, CarbonInterface $submittedAt): string
    {
        return 'registration-'.$registration['idNumber'].'-'.$submittedAt->format('Ymd').'.pdf';
    }
}
