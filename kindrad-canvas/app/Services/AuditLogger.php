<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuditLogAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class AuditLogger
{
    /**
     * @var array<string, int>
     */
    private array $actionIdCache = [];

    /**
     * Record an admin action. Writes one audit_logs row.
     *
     * @param  array<string, mixed>|null  $payload  before/after snapshot or context
     */
    public function record(User $actor, string $actionSlug, EloquentModel $target, ?array $payload = null): AuditLog
    {
        return AuditLog::create([
            'actor_user_id' => $actor->id,
            'action_id' => $this->actionId($actionSlug),
            'target_type' => $target::class,
            'target_id' => $target->getKey(),
            'payload' => $payload,
        ]);
    }

    private function actionId(string $slug): int
    {
        if (! isset($this->actionIdCache[$slug])) {
            $this->actionIdCache[$slug] = AuditLogAction::where('slug', $slug)->value('id')
                ?? AuditLogAction::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('_', ' ', $slug))])->id;
        }

        return $this->actionIdCache[$slug];
    }
}
