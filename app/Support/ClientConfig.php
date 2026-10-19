<?php

namespace App\Support;

/**
 * The subset of config/registration.php the browser needs.
 * Recipient emails stay on the server.
 */
class ClientConfig
{
    public static function make(?string $lockedBranch = null, ?bool $demo = null): array
    {
        $cfg = config('registration');
        $demo ??= $cfg['demo'];

        return [
            'collegeName' => $cfg['college_name'],
            'formTitle' => $cfg['form_title'],
            'storageTtlDays' => $cfg['storage_ttl_days'],
            'demoMode' => $demo,
            'submitUrl' => $demo ? null : route('registration.store'),
            'tokenUrl' => $demo ? null : route('registration.token'),
            'lockedBranch' => isset($cfg['branches'][$lockedBranch]) ? $lockedBranch : null,
            'branches' => collect($cfg['branches'])->map(fn ($b, $id) => [
                'id' => $id,
                'name' => $b['name'],
                'majors' => $b['majors'],
                // Shown only on the demo's "this is the email that would be sent" box.
                'emails' => $demo ? $b['emails'] : [],
            ])->values()->all(),
        ];
    }
}
