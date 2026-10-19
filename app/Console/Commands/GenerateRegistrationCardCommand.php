<?php

namespace App\Console\Commands;

use App\Services\Registration\RegistrationCard;
use Illuminate\Console\Command;
use Throwable;

/**
 * Produces a registration card from sample data.
 *
 * Cards are normally made on their own, when a candidate submits, and go
 * straight into the branch's email. This is for checking what a card looks
 * like (after a template change, or on a new server) without submitting a
 * form and digging the attachment out of a mailbox.
 */
class GenerateRegistrationCardCommand extends Command
{
    protected $signature = 'registration:card
        {--branch= : Branch id to use instead of the sample\'s}
        {--out= : Where to write the pdf (default: storage/app/registration-card.pdf)}';

    protected $description = 'Produce a sample registration card PDF';

    public function handle(RegistrationCard $card): int
    {
        $data = require base_path('tests/Fixtures/registration.php');

        if ($branch = $this->option('branch')) {
            $config = config("registration.branches.{$branch}");

            if ($config === null) {
                $this->error("No branch called {$branch}. Known: ".implode(', ', array_keys(config('registration.branches'))));

                return self::FAILURE;
            }

            $data['branch'] = $branch;
            $data['major'] = $config['majors'][0];
        }

        try {
            $pdf = $card->pdf($data, now());
        } catch (Throwable $e) {
            $this->error('Could not produce the card: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! str_starts_with($pdf, '%PDF')) {
            $this->error('What was rendered does not look like a PDF.');

            return self::FAILURE;
        }

        $path = $this->option('out') ?: storage_path('app/registration-card.pdf');
        file_put_contents($path, $pdf);

        $this->info(sprintf('Card for %s %s — %s KB.', $data['firstNameHe'], $data['lastNameHe'], number_format(strlen($pdf) / 1024, 1)));
        $this->line("At        {$path}");

        return self::SUCCESS;
    }
}
