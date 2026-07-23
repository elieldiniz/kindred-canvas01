<?php

use App\Services\PromptEngine\PromptFragment;

test('PromptFragment exposes readonly properties', function (): void {
    $fragment = new PromptFragment(text: 'Hello', priority: 10);

    expect($fragment->text)->toBe('Hello')
        ->and($fragment->priority)->toBe(10)
        ->and($fragment->negativeFragment)->toBeNull();
});

test('PromptFragment accepts a negative fragment', function (): void {
    $fragment = new PromptFragment(text: 'Hello', priority: 5, negativeFragment: 'no blur');

    expect($fragment->negativeFragment)->toBe('no blur');
});
