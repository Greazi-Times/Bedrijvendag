<?php

namespace App\Console\Commands;

use App\Services\FeedbackSurveys;
use Illuminate\Console\Command;

class ProvisionFeedbackSurveys extends Command
{
    protected $signature = 'feedback:provision';

    protected $description = 'Create the missing student and company feedback surveys of every edition (safe to repeat; never overwrites anything)';

    public function handle(FeedbackSurveys $surveys): int
    {
        $this->info($surveys->backfill().' edition(s) were missing a survey and received it.');

        return self::SUCCESS;
    }
}
