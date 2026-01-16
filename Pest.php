<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Database\DB;

uses(Tests\TestCase::class)->in('App', 'System/Feature', 'System/Integration', 'Packages/*/Architecture');
uses(Tests\UnitTestCase::class)->in('System/Unit');
uses(Tests\DatabaseTransactionTestCase::class)->in('System/Transaction');
uses(Tests\PackageTestCase::class)->in('Packages/*/Unit', 'Packages/*/Feature', 'Packages/*/Integration');

/*
|--------------------------------------------------------------------------
| Parallel Testing
|--------------------------------------------------------------------------
|
| Pest supports parallel testing out of the box. Enable it to run tests
| across multiple processes, significantly reducing test execution time.
|
*/

// Enable parallel testing by default when --parallel flag is used
// Tests will run across multiple processes for faster execution

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code that is specific
| to your project. By adding your own custom functions to this file, you can create a highly
| customized testing experience that is easy to use and maintain.
|
*/

function skipOnSqlite(string $reason = 'SQLite does not support this operation'): void
{
    try {
        $connection = DB::connection();

        if (! $connection) {
            return;
        }

        $driver = $connection->getDriver();
    } catch (Throwable $e) {
        return;
    }

    if (str_contains(strtolower($driver), 'sqlite')) {
        test()->markTestSkipped($reason);
    }
}
