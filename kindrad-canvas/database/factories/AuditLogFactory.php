<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\AuditLogAction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory()->admin(),
            'action_id' => AuditLogAction::where('slug', 'edit_product')->value('id'),
            'target_type' => Product::class,
            'target_id' => 1,
            'payload' => null,
        ];
    }
}
