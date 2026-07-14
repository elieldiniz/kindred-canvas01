<?php

test('renders empty state with drag copy and cloud_upload icon', function (): void {
    $html = Blade::render(
        '<x-blocks.photo-dropzone :slot-index="0" :slot-count="1" />'
    );

    expect($html)
        ->toContain('data-test="photo-dropzone-slot-0"')
        ->toContain('Drag your photo here')
        ->toContain('JPEG / PNG / WEBP up to 10 MB')
        ->toContain('cloud_upload')
        ->toContain('data-test="photo-input-slot-0"')
        ->toContain('wire:model="photo"');
});

test('renders preview state when preview URL provided', function (): void {
    $html = Blade::render(
        '<x-blocks.photo-dropzone :slot-index="0" preview="https://example.com/photo.jpg" />'
    );

    expect($html)
        ->toContain('data-test="photo-preview-slot-0"')
        ->toContain('https://example.com/photo.jpg')
        ->not->toContain('Drag your photo here');
});

test('renders error banner when error provided', function (): void {
    $html = Blade::render(
        '<x-blocks.photo-dropzone :slot-index="0" error="File too large" />'
    );

    expect($html)
        ->toContain('data-test="photo-error-slot-0"')
        ->toContain('File too large')
        ->toContain('role="alert"');
});

test('slot number appears in label and testid', function (): void {
    $html1 = Blade::render('<x-blocks.photo-dropzone :slot-index="0" :slot-count="2" />');
    $html2 = Blade::render('<x-blocks.photo-dropzone :slot-index="1" :slot-count="2" />');

    expect($html1)->toContain('data-test="photo-dropzone-slot-0"')
        ->toContain('Photo 1');
    expect($html2)->toContain('data-test="photo-dropzone-slot-1"')
        ->toContain('Photo 2');
});

test('custom label overrides default', function (): void {
    $html = Blade::render(
        '<x-blocks.photo-dropzone :slot-index="0" label="Primary photo" />'
    );

    expect($html)->toContain('Primary photo');
});

test('remove button is rendered in single-slot mode when photo exists', function (): void {
    $html = Blade::render(
        '<x-blocks.photo-dropzone :slot-index="0" :slot-count="1" preview="https://example.com/x.jpg" wire-remove="removePhoto(0)" />'
    );

    expect($html)
        ->toContain('data-test="photo-remove-slot-0"')
        ->toContain('wire:click="removePhoto(0)"');
});

test('no remove button when single slot and no preview', function (): void {
    $html = Blade::render(
        '<x-blocks.photo-dropzone :slot-index="0" :slot-count="1" />'
    );

    expect($html)->not->toContain('photo-remove-slot-0');
});
