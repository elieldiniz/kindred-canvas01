<?php

use Illuminate\Support\Facades\Blade;

test('progress bar fill at step 3 is between 28 and 43 percent', function (): void {
    $markup = Blade::render('<x-wizard.progress-bar :step="3" :total="7" section-name="Mode" />');

    expect($markup)->toContain('STEP 03 OF 07');

    preg_match('/data-fill-percent="([^"]+)"/', $markup, $matches);
    expect($matches)->not->toBeEmpty();
    expect($matches[1])->toBeNumeric();

    $pct = (float) $matches[1];
    expect($pct)->toBeGreaterThanOrEqual(28.0);
    expect($pct)->toBeLessThanOrEqual(43.0);
});

test('progress bar label formatting', function (): void {
    $markup = Blade::render('<x-wizard.progress-bar :step="3" :total="7" section-name="Mode" />');

    expect($markup)->toContain('STEP 03 OF 07');
    expect($markup)->toContain('Mode');
});

test('progress bar fill width matches data attribute', function (): void {
    $markup = Blade::render('<x-wizard.progress-bar :step="3" :total="7" section-name="Mode" />');

    expect($markup)->toMatch('/style="width: 42\.857%;"/');
    expect($markup)->toContain('data-fill-percent="42.857"');
});

test('progress bar clamps step above total to total', function (): void {
    $markup = Blade::render('<x-wizard.progress-bar :step="10" :total="7" section-name="Mode" />');

    expect($markup)->toContain('STEP 07 OF 07');
    expect($markup)->toContain('data-fill-percent="100.000"');
});

test('progress bar clamps step below one to one', function (): void {
    $markup = Blade::render('<x-wizard.progress-bar :step="0" :total="7" section-name="Mode" />');

    expect($markup)->toContain('STEP 01 OF 07');
});

test('progress bar renders fill element with primary color and glow', function (): void {
    $markup = Blade::render('<x-wizard.progress-bar :step="2" :total="7" section-name="Mode" />');

    expect($markup)->toContain('bg-primary');
    expect($markup)->toContain('shadow-[0_0_10px_rgba(192,193,255,0.6)]');
});
