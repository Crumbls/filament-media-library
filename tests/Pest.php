<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

if (class_exists(Crumbls\FilamentMediaLibrary\Tests\TestCase::class)) {
    uses(Crumbls\FilamentMediaLibrary\Tests\TestCase::class, RefreshDatabase::class)->in('Feature');
    uses(Crumbls\FilamentMediaLibrary\Tests\TestCase::class)->in('Unit');
} else {
    uses(TestCase::class, DatabaseTransactions::class)->in('Feature');
    uses(TestCase::class)->in('Unit');
}

require_once __DIR__.'/Helpers.php';
