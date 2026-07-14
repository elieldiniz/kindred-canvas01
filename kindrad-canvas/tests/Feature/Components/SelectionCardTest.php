<?php

test('renders with thumbnail and label', function (): void {
    $html = Blade::render(
        '<x-blocks.selection-card wire-click="select" icon="coffee" name="Mug" thumbnail="https://example.com/mug.jpg" test-id="product-mug" />'
    );

    expect($html)
        ->toContain('data-test="product-mug"')
        ->toContain('wire:click="select"')
        ->toContain('Mug')
        ->toContain('https://example.com/mug.jpg')
        ->toContain('alt="Mug"')
        ->toContain('group-hover:scale-110');
});

test('renders icon fallback when no thumbnail', function (): void {
    $html = Blade::render(
        '<x-blocks.selection-card wire-click="select" icon="coffee" name="Mug" test-id="no-img" />'
    );

    expect($html)
        ->toContain('coffee')
        ->toContain('Mug')
        ->not->toContain('group-hover:scale-110');
});

test('selected state adds selection-glow class and check_circle badge', function (): void {
    $html = Blade::render(
        '<x-blocks.selection-card wire-click="select" name="Mug" :selected="true" test-id="selected-card" />'
    );

    expect($html)
        ->toContain('selection-glow')
        ->toContain('active-selection')
        ->toContain('check_circle')
        ->toContain('data-test="selected-card-selected"');
});

test('unselected state does not render check_circle badge', function (): void {
    $html = Blade::render(
        '<x-blocks.selection-card wire-click="select" name="Mug" :selected="false" test-id="unselected-card" />'
    );

    expect($html)
        ->toContain('aspect-square')
        ->not->toContain('selection-glow')
        ->not->toContain('check_circle');
});

test('description is rendered above the name when provided', function (): void {
    $html = Blade::render(
        '<x-blocks.selection-card name="Mug" description="Ceramic 11oz" test-id="with-desc" />'
    );

    expect($html)->toContain('Ceramic 11oz');
});

test('aspect portrait uses aspect-4/5', function (): void {
    $html = Blade::render(
        '<x-blocks.selection-card name="Tall" aspect="portrait" test-id="portrait" />'
    );

    expect($html)->toContain('aspect-4/5');
});

test('default aspect is square (aspect-square)', function (): void {
    $html = Blade::render(
        '<x-blocks.selection-card name="Square" test-id="square" />'
    );

    expect($html)->toContain('aspect-square');
});

test('no wire:click when wireClick prop is null', function (): void {
    $html = Blade::render(
        '<x-blocks.selection-card name="Static" test-id="static" />'
    );

    expect($html)->not->toContain('wire:click=');
});
