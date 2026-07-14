<?php

namespace App\Console\Commands;

use App\Models\Generation;
use App\Models\GenerationStatus;
use App\Services\CreditLedger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:recover-stale-generations {--threshold=90 : Minutes before a generation is considered stale}')]
#[Description('Recover generations stuck in processing state for too long and refund credits.')]
class RecoverStaleGenerations extends Command
{
    public function handle(CreditLedger $ledger): int
    {
        $thresholdMinutes = (int) $this->option('threshold');

        $processingStatusId = GenerationStatus::where('slug', 'processing')->value('id');

        if ($processingStatusId === null) {
            $this->info('No processing status found.');

            return self::SUCCESS;
        }

        $staleGenerations = Generation::where('status_id', $processingStatusId)
            ->where('updated_at', '<', now()->subMinutes($thresholdMinutes))
            ->get();

        if ($staleGenerations->isEmpty()) {
            $this->info('No stale generations found.');

            return self::SUCCESS;
        }

        $failedStatusId = GenerationStatus::where('slug', 'failed')->value('id');

        $recovered = 0;

        foreach ($staleGenerations as $generation) {
            $generation->update(['status_id' => $failedStatusId]);

            try {
                $ledger->refund($generation, "Stale generation recovered after {$thresholdMinutes} minutes");
                $recovered++;
            } catch (\Throwable $e) {
                $this->error("Failed to refund generation {$generation->id}: {$e->getMessage()}");
            }
        }

        $this->info("Recovered {$recovered} stale generations.");

        return self::SUCCESS;
    }
}
