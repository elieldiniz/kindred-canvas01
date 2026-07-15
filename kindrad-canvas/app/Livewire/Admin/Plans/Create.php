<?php

namespace App\Livewire\Admin\Plans;

use App\Actions\Billing\EnsureStripePriceAction;
use App\Livewire\Admin\Plans\Concerns\ParsesPlanPrice;
use App\Models\SubscriptionInterval;
use App\Models\SubscriptionPlan;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{
    use ParsesPlanPrice;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public int $credits_per_period = 50;

    public string $price_display = '19,90';

    public string $currency = 'BRL';

    public ?int $interval_id = null;

    public int $sort_order = 10;

    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function updatedName(): void
    {
        if ($this->slug === '') {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(AuditLogger $audit, EnsureStripePriceAction $price): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:subscription_plans,slug'],
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

        $plan = SubscriptionPlan::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'credits_per_period' => $this->credits_per_period,
            'price_cents' => $cents,
            'currency' => $this->currency,
            'interval_id' => $this->interval_id,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        try {
            $price->handle($plan);
        } catch (\Throwable $e) {
            $this->addError('stripe', $e->getMessage());
            $plan->delete();

            return;
        }

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_subscription_plan',
            target: $plan,
            payload: [
                'event' => 'created',
                'attributes' => $plan->only([
                    'name', 'slug', 'description', 'credits_per_period', 'price_cents',
                    'currency', 'interval_id', 'sort_order', 'is_active',
                    'stripe_product_id', 'stripe_price_id',
                ]),
            ],
        );

        $this->redirectRoute('admin.plans.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.plans.create', [
            'intervals' => SubscriptionInterval::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Novo Plano'),
        ]);
    }
}
