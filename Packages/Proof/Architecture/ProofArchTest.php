<?php

declare(strict_types=1);
/**
 * Anchor Framework
 *
 * Proof Arch Test.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Proof\Architecture;

test('Proof package follows architecture standards')
    ->expect('Proof')
    ->toOnlyUse([
        'Proof',
        'Core',
        'Database',
        'Audit',
        'Media',
        'Link',
        'Verify',
        'Workflow',
        'Stack',
        'Helpers',
        'Carbon',
    ])
    ->ignoring('Proof\Models');

test('Proof package does not use other package models directly')
    ->expect('Proof')
    ->not->toUse([
        'Audit\Models',
        'Link\Models',
        'Verify\Models',
        'Workflow\Models',
        'Stack\Models',
    ])
    ->ignoring('Proof\Proof'); // Allow Facade to use models for type hinting if necessary, though ideally it should use contracts.
