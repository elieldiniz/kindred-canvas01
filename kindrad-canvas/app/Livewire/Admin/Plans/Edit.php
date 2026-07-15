<?php

namespace App\Livewire\Admin\Plans;

use App\Actions\Billing\EnsureStripePriceAction;
use App\Livewire\Admin\Plans\Concerns\ParsesPlanPrice;
use App\Models\SubscriptionInterval;
use App\Models\SubscriptionPlan;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Edit extends Component
{
    use ParsesPlanPrice;

    public SubscriptionPlan $planModel;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public int $credits_per_period = 50;

    public string $price_display = '19,90';

    public string $currency = 'BRL';

    public ?int $interval_id = null;

    public int $sort_order = 10;

    public bool $is_active = true;

    public function mount(int|SubscriptionPlan $plan): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $model = $plan instanceof SubscriptionPlan ? $plan : SubscriptionPlan::findOrFail($plan);
        $this->planModel = $model;
        $this->name = $model->name;
        $this->slug = $model->slug;
        $this->description = $model->description;
        $this->credits_per_period = (int) $model->credits_per_period;
        $this->price_display = $this->centsToDisplay((int) $model->price_cents);
        $this->currency = $model->currency;
        $this->interval_id = $model->interval_id;
        $this->sort_order = (int) $model->sort_order;
        $this->is_active = (bool) $model->is_active;
    }

    public function save(AuditLogger $audit, EnsureStripePriceAction $price): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:subscription_plans,slug,'.$this->planModel->id],
            'description' => ['nullable', 'string'],
            'credits_per_period' => ['required', 'integer', 'min:1'],
            'price_display' => ['required', 'string', 'regex:/^\d{1,6}([.,]\d{1,2})?$/'],
            'currency' => ['required', 'string', 'size:3', 'in:BRL'],
            'interval_id' => ['required', 'exists:subscription_intervals,id'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ], [
            'price_display.regex' => __('Use o formato 19,90 ou 1990 (centavos inteiros).'),
        ]);

        $cents = $this->parsePriceToCents($this->price_display);

        if ($cents < 1) {
            $this->addError('price_display', __('O preço precisa ser maior que zero.'));

            return;
        }

        $tracked = [
            'name', 'slug', 'description', 'credits_per_period',
            'currency', 'interval_id', 'sort_order', 'is_active',
        ];
        $before = $this->planModel->only($tracked);
        $before['price_cents'] = $this->planModel->price_cents;

        $this->planModel->fill([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'credits_per_period' => $this->credits_per_period,
            'price_cents' => $cents,
            'currency' => $this->currency,
            'interval_id' => $this->interval_id,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ])->save();

        try {
            $price->handle($this->planModel);
        } catch (\Throwable $e) {
            $this->addError('stripe', $e->getMessage());

            return;
        }

        $after = $this->planModel->fresh()->only($tracked);
        $changes = array_keys(array_diff_assoc($after, $before));

        if ($changes !== []) {
            $audit->record(
                actor: auth()->user(),
                actionSlug: 'edit_subscription_plan',
                target: $this->planModel,
                payload: [
                    'before' => array_intersect_key($before, array_flip($changes)),
                    'after' => array_intersect_key($after, array_flip($changes)),
                    'changed' => $changes,
                ],
            );
        }

        $this->redirectRoute('admin.plans.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.plans.edit', [
            'intervals' => SubscriptionInterval::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Editar Plano'),
        ]);
    }
}
