<?php

test('renders with icon title and helper', function (): void {
    $html = Blade::render(
        '<x-blocks.card icon="auto_awesome" title="Subject type" helper="Pick who is in the photo."><p>Inner content</p></x-blocks.card>'
    );

    expect($html)
        ->toContain('data-test="block-subject-type"')
        ->toContain('Subject type')
        ->toContain('Pick who is in the photo.')
        ->toContain('Inner content')
        ->toContain('auto_awesome')
        ->toContain('material-symbols-outlined');
});

test('uses custom slug when provided', function (): void {
    $html = Blade::render(
        '<x-blocks.card title="My Block" slug="my-custom-slug" />'
    );

    expect($html)->toContain('data-test="block-my-custom-slug"');
});

test('renders empty when only title is given (no icon, no helper)', function (): void {
    $html = Blade::render('<x-blocks.card title="Bare" />');

    expect($html)
        ->toContain('data-test="block-bare"')
        ->toContain('Bare')
        ->not->toContain('material-symbols-outlined');
});

test('slot content is rendered', function (): void {
    $html = Blade::render(
        '<x-blocks.card title="With slot"><span class="custom-class">hello</span></x-blocks.card>'
    );

    expect($html)->toContain('<span class="custom-class">hello</span>');
});

test('uses glass-card base class', function (): void {
    $html = Blade::render('<x-blocks.card title="Styled" />');

    expect($html)->toContain('glass-card');
});
