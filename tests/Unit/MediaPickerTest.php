<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Forms\Components\MediaPicker;

test('picker defaults to single mode', function (): void {
    $picker = MediaPicker::make('media_id');

    expect($picker->isMultiple())->toBeFalse();
});

test('picker can be set to multiple mode', function (): void {
    $picker = MediaPicker::make('media_ids')->multiple();

    expect($picker->isMultiple())->toBeTrue();
});

test('picker can disable multiple mode', function (): void {
    $picker = MediaPicker::make('media_ids')->multiple(false);

    expect($picker->isMultiple())->toBeFalse();
});

test('picker defaults to default collection', function (): void {
    $picker = MediaPicker::make('media_id');

    expect($picker->getCollection())->toBe('default');
});

test('picker can set collection', function (): void {
    $picker = MediaPicker::make('media_id')->collection('gallery');

    expect($picker->getCollection())->toBe('gallery');
});

test('picker defaults to zero max items', function (): void {
    $picker = MediaPicker::make('media_id');

    expect($picker->getMaxItems())->toBe(0);
});

test('picker can set max items', function (): void {
    $picker = MediaPicker::make('media_id')->maxItems(5);

    expect($picker->getMaxItems())->toBe(5);
});

test('multiple returns fluent instance', function (): void {
    $picker = MediaPicker::make('media_id');

    expect($picker->multiple())->toBeInstanceOf(MediaPicker::class);
});

test('collection returns fluent instance', function (): void {
    $picker = MediaPicker::make('media_id');

    expect($picker->collection('test'))->toBeInstanceOf(MediaPicker::class);
});

test('maxItems returns fluent instance', function (): void {
    $picker = MediaPicker::make('media_id');

    expect($picker->maxItems(3))->toBeInstanceOf(MediaPicker::class);
});
