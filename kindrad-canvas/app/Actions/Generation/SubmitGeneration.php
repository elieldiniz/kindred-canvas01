<?php

namespace App\Actions\Generation;

use App\Exceptions\BillingAccessDeniedException;
use App\Jobs\GenerateArtworkJob;
use App\Models\Generation;
use App\Models\GenerationProvider as GenerationProviderModel;
use App\Models\GenerationStatus;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CreditLedger;
use App\Services\Exceptions\CreditInsufficientException;
use App\Services\Generation\ProviderRegistry;
use App\Services\PromptAssembler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SubmitGeneration
{
    public function __construct(
        private readonly PromptAssembler $assembler,
        private readonly ProviderRegistry $registry,
        private readonly CreditLedger $ledger,
    ) {}

    public function execute(User $user, Project $project): Generation
    {
        return DB::transaction(function () use ($user, $project): Generation {
            if ($project->user_id !== $user->id && ! $user->is_admin) {
                throw new AuthorizationException('You are not allowed to submit this project for generation.');
            }

            if ((int) $user->credit_balance < 1) {
                throw CreditInsufficientException::for((int) $user->credit_balance, 1);
            }

            $this->ensureNotDunningExpired($user);

            $assembled = $this->assembler->assemble($project);
            $prompt = $assembled['prompt'];
            $constraints = $assembled['constraints'];

            $provider = $this->registry->resolveActive();
            $providerSlug = $provider->getProviderKey();

            $providerId = GenerationProviderModel::where('slug', $providerSlug)->value('id');

            if ($providerId === null) {
                throw new InvalidArgumentException("Unknown generation provider slug: {$providerSlug}");
            }

            $generation = Generation::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'status_id' => GenerationStatus::where('slug', 'waiting')->value('id'),
                'provider_id' => $providerId,
                'prompt_snapshot' => $prompt,
                'constraints_snapshot' => $constraints,
                'idempotency_key' => (string) Str::uuid(),
                'credits_charged' => 1,
            ]);

            $this->ledger->debit($user, 1, $generation);

            GenerateArtworkJob::dispatch($generation->id, $providerSlug);

            return $generation->refresh();
        });
    }

    private function ensureNotDunningExpired(User $user): void
    {
        $graceDays = (int) config('billing.grace_days', 7);

        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($subscription === null) {
            return;
        }

        if ($subscription->isPastDueAndExpired($graceDays)) {
            throw BillingAccessDeniedException::forUser($user);
        }
    }
}
