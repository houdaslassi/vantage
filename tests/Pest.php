<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "HoudaSlassi\Vantage\Tests\TestCase". You may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use HoudaSlassi\Vantage\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

