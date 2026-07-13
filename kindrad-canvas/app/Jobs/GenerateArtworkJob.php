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
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateArtworkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

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
            $ledger->refund($generation, 'No debit ledger row found; idempotency guard aborted.');
            $generation->markFailed('Missing credit debit row; aborted before provider call.');

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
                'error' => $e->getMessage(),
            ]);

            $generation->markFailed($e->getMessage());

            try {
                $ledger->refund($generation, $e->getMessage());
            } catch (Throwable $refundError) {
                Log::error('Refund failed for generation', [
                    'generation_id' => $generation->id,
                    'error' => $refundError->getMessage(),
                ]);
            }

            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
}
