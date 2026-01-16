<?php

declare(strict_types=1);

use Stack\Stack;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Audit', 'Stack'], true);
    $this->bootPackage('Stack');
    $this->fakeAudit();
});

describe('Stack Submissions', function () {
    test('a user can submit a valid form', function () {
        $form = Stack::form()
            ->title('Contact')
            ->active()
            ->withField('name', 'Name')->type('text')->required()->add()
            ->withField('email', 'Email')->type('email')->required()->add()
            ->create();

        // 2. Submit data
        $submission = Stack::submit($form, [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // 3. Assert submission exists
        $this->assertDatabaseHas('stack_submission', [
            'id' => $submission->id,
            'stack_form_id' => $form->id
        ]);

        // 4. Assert values were stored
        $this->assertDatabaseHas('stack_submission_value', [
            'stack_submission_id' => $submission->id,
            'value' => 'John Doe'
        ]);

        $this->assertDatabaseHas('stack_submission_value', [
            'stack_submission_id' => $submission->id,
            'value' => 'john@example.com'
        ]);
    });
});

afterEach(function () {
    DatabaseTestHelper::dropAllTables();
    DatabaseTestHelper::resetDefaultConnection();
});
