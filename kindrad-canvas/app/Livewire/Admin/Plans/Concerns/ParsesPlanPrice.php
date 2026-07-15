<?php

namespace App\Livewire\Admin\Plans\Concerns;

trait ParsesPlanPrice
{
    protected function parsePriceToCents(string $raw): int
    {
        $normalized = str_replace(',', '.', $raw);

        if (! preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', $normalized)) {
            return 0;
        }

        return (int) round(((float) $normalized) * 100);
    }

    protected function centsToDisplay(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }
}
