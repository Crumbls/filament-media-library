<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Tables\Columns\MediaColumn;

test('column defaults to default collection', function (): void {
    $column = MediaColumn::make('media');

    expect($column->getCollection())->toBe('default');
});

test('column can set collection', function (): void {
    $column = MediaColumn::make('media')->collection('gallery');

    expect($column->getCollection())->toBe('gallery');
});

test('column defaults to size 40', function (): void {
    $column = MediaColumn::make('media');

    expect($column->getSize())->toBe(40);
});

test('column can set size as integer', function (): void {
    $column = MediaColumn::make('media')->size(64);

    expect($column->getSize())->toBe(64);
});

test('column can set size as string', function (): void {
    $column = MediaColumn::make('media')->size('3rem');

    expect($column->getSize())->toBe('3rem');
});

test('column defaults to not circular', function (): void {
    $column = MediaColumn::make('media');

    expect($column->isCircular())->toBeFalse();
});

test('column can be set to circular', function (): void {
    $column = MediaColumn::make('media')->circular();

    expect($column->isCircular())->toBeTrue();
});

test('column can disable circular', function (): void {
    $column = MediaColumn::make('media')->circular(false);

    expect($column->isCircular())->toBeFalse();
});

test('column defaults to square', function (): void {
    $column = MediaColumn::make('media');

    expect($column->isSquare())->toBeTrue();
});

test('column can disable square', function (): void {
    $column = MediaColumn::make('media')->square(false);

    expect($column->isSquare())->toBeFalse();
});

test('collection returns fluent instance', function (): void {
    $column = MediaColumn::make('media');

    expect($column->collection('test'))->toBeInstanceOf(MediaColumn::class);
});

test('size returns fluent instance', function (): void {
    $column = MediaColumn::make('media');

    expect($column->size(50))->toBeInstanceOf(MediaColumn::class);
});

test('circular returns fluent instance', function (): void {
    $column = MediaColumn::make('media');

    expect($column->circular())->toBeInstanceOf(MediaColumn::class);
});

test('square returns fluent instance', function (): void {
    $column = MediaColumn::make('media');

    expect($column->square())->toBeInstanceOf(MediaColumn::class);
});
