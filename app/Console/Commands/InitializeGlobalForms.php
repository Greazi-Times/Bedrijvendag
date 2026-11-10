<?php

namespace App\Console\Commands;

use App\Services\GlobalForms;
use Illuminate\Console\Command;

class InitializeGlobalForms extends Command
{
    protected $signature = 'forms:initialize';

    protected $description = 'Create the two global default forms if missing and move editions with untouched generated forms onto them (safe to repeat)';

    public function handle(GlobalForms $globals): int
    {
        $result = $globals->initialize();

        $this->info($result['created'] === [] ? 'Both global forms already exist; nothing was reset.' : 'Created the global '.implode(' and ', $result['created']).' form.');

        $rows = collect($result['editions'])->flatMap(fn (array $kinds, int $event) => collect($kinds)->map(fn (string $outcome, string $kind) => [$event, $kind, $outcome]))->values()->all();

        if ($rows !== []) {
            $this->table(['Edition', 'Form', 'Outcome'], $rows);
            $this->line('"adopted": the edition now inherits the global form. "kept": its own form is preserved and still used.');
        }

        return self::SUCCESS;
    }
}
