<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class)->in('Feature');
uses(TestCase::class)->in('Unit');

require_once __DIR__.'/Helpers.php';
