<?php

namespace App\Jobs;

use App\Models\CreditTransaction;
use App\Models\CreditTransactionReason;
use App\Models\Generation;
use App\Models\GenerationStatus;
use App\Models\Project;
use App\Services\CreditLedger;
use App\Services\Generation\ProviderRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateArtworkJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public int $timeout = 120;

    public function __construct(public int $generationId, public string $providerKey) {}

    public function handle(ProviderRegistry $registry, CreditLedger $ledger): void
    {
        $generation = Generation::find($this->generationId);

        if ($generation === null) {
            return;
        }

        $status = GenerationStatus::find($generation->status_id);

        if ($status !== null && in_array($status->slug, ['completed', 'failed'], true)) {
            return;
        }

        $debitReasonId = CreditTransactionReason::where('slug', 'generation_debit')->value('id');

        $hasDebit = CreditTransaction::where('reference_type', Generation::class)
            ->where('reference_id', $generation->id)
            ->where('reason_id', $debitReasonId)
            ->exists();

        if (! $hasDebit) {
            $this->failGeneration($generation, $ledger, 'Missing credit debit row; aborted before provider call.');

            return;
        }

        $generation->markProcessing();

        $project = Project::find($generation->project_id);
        $sourceImage = $project?->sourceImage;

        try {
            $provider = $registry->resolve($this->providerKey);
            $result = $provider->generate(
                $generation->prompt_snapshot,
                is_array($generation->constraints_snapshot) ? $generation->constraints_snapshot : [],
                $sourceImage,
            );

            $generation->markCompleted($result->path, $result->mime, $result->width, $result->height);

            $project = $generation->project;
            if ($project !== null && $project->first_generated_at === null) {
                $project->first_generated_at = now();
                $project->save();
            }
        } catch (Throwable $e) {
            Log::warning('GenerateArtworkJob failed', [
                'generation_id' => $generation->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            $this->failGeneration($generation, $ledger, $e->getMessage());

            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    private function failGeneration(Generation $generation, CreditLedger $ledger, string $reason): void
    {
        $generation->markFailed($reason);

        try {
            $ledger->refund($generation, $reason);
        } catch (Throwable $refundError) {
            Log::error('Refund failed for generation', [
                'generation_id' => $generation->id,
                'error' => $refundError->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('GenerateArtworkJob permanently failed', [
            'generation_id' => $this->generationId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
