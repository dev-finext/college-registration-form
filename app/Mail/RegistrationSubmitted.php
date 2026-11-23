<?php

namespace App\Mail;

use App\Services\Registration\RegistrationCard;
use App\Services\Registration\RegistrationCardData;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RegistrationSubmitted extends Mailable
{
    use Queueable;

    public function __construct(
        public array $data,
        public string $pdf,
        public CarbonInterface $submittedAt,
        // True outside production: the email went to the test recipient,
        // not the branch (see RegistrationController::store()).
        public bool $testMode = false,
    ) {}

    public function envelope(): Envelope
    {
        $name = $this->data['firstNameHe'].' '.$this->data['lastNameHe'];
        $subject = self::subjectFor($this->data);

        return new Envelope(
            subject: $this->testMode ? '[בדיקה] '.$subject : $subject,
            replyTo: [new Address($this->data['email'], $name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.registration-submitted', with: [
            'd' => $this->data,
            'branch' => config('registration.branches.'.$this->data['branch']),
            'reference' => RegistrationCardData::reference($this->data, $this->submittedAt),
            'submittedAt' => $this->submittedAt,
            'testMode' => $this->testMode,
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, RegistrationCard::filename($this->data, $this->submittedAt))
                ->withMime('application/pdf'),
        ];
    }

    /**
     * "טופס רישום הנדסאים + שם המועמד + שם המגמה" (quote, item 7).
     */
    public static function subjectFor(array $data): string
    {
        return 'טופס רישום הנדסאים - '.$data['firstNameHe'].' '.$data['lastNameHe'].' - '.$data['major'];
    }
}
