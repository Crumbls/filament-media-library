<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

if (class_exists(\Crumbls\FilamentMediaLibrary\Tests\TestCase::class)) {
    uses(\Crumbls\FilamentMediaLibrary\Tests\TestCase::class, RefreshDatabase::class)->in('Feature');
    uses(\Crumbls\FilamentMediaLibrary\Tests\TestCase::class)->in('Unit');
} else {
    uses(Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class)->in('Feature');
    uses(Tests\TestCase::class)->in('Unit');
}

require_once __DIR__.'/Helpers.php';
