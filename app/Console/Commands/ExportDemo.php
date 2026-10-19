<?php

namespace App\Console\Commands;

use App\Support\ClientConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Renders the real Blade form in demo mode into docs/ for GitHub Pages,
 * so the public UI demo never drifts from the application.
 */
class ExportDemo extends Command
{
    protected $signature = 'demo:export {--path=docs : Output folder, relative to the project root}';

    protected $description = 'Export the registration form as a static UI demo (GitHub Pages)';

    public function handle(): int
    {
        $out = base_path($this->option('path'));

        File::ensureDirectoryExists($out);
        File::deleteDirectory($out.'/assets');
        File::copyDirectory(public_path('assets'), $out.'/assets');

        $html = view('registration.form', [
            'clientConfig' => ClientConfig::make(null, demo: true),
            'assetBase' => '',
        ])->render();

        File::put($out.'/index.html', $html);
        File::put($out.'/.nojekyll', '');

        $this->components->info("Demo exported to {$this->option('path')}/");

        return self::SUCCESS;
    }
}
