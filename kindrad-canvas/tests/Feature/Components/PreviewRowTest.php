<?php

test('renders label and value when both provided', function (): void {
    $html = Blade::render(
        '<x-blocks.preview-row icon="auto_awesome" label="Style" value="Watercolor" />'
    );

    expect($html)
        ->toContain('data-test="preview-row"')
        ->toContain('Style')
        ->toContain('Watercolor')
        ->toContain('data-test="preview-row-value"');
});

test('renders dash when value is null', function (): void {
    $html = Blade::render(
        '<x-blocks.preview-row icon="auto_awesome" label="Style" :value="null" />'
    );

    expect($html)
        ->toContain('—')
        ->toContain('Style');
});

test('renders dash when value is empty string', function (): void {
    $html = Blade::render(
        '<x-blocks.preview-row icon="auto_awesome" label="Style" value="" />'
    );

    expect($html)->toContain('—');
});

test('renders icon and label when icon provided', function (): void {
    $html = Blade::render(
        '<x-blocks.preview-row icon="auto_awesome" label="Style" value="Watercolor" />'
    );

    expect($html)
        ->toContain('material-symbols-outlined')
        ->toContain('auto_awesome');
});

test('does not render icon when icon prop is null', function (): void {
    $html = Blade::render(
        '<x-blocks.preview-row label="Style" value="Watercolor" />'
    );

    expect($html)
        ->toContain('Style')
        ->toContain('Watercolor')
        ->not->toContain('material-symbols-outlined');
});
