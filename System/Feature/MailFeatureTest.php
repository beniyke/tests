<?php

declare(strict_types=1);

use Mail\Mail;
use Tests\System\Helpers\TestNotification;

beforeEach(function () {
    $this->mailer = $this->fakeMail();
});

describe('Mail - Basic Email Sending', function () {
    test('sends plain text email', function () {
        Mail::send(new TestNotification([
            'recipients' => ['to' => ['recipient@example.com']],
            'subject' => 'Test Subject',
            'message' => 'This is a plain text email body.'
        ]));

        $this->mailer->assertSent(TestNotification::class, function ($mail) {
            $data = $mail->getData();

            return $data['recipients']['to'] === ['recipient@example.com']
                && $data['subject'] === 'Test Subject'
                && str_contains($data['message'], 'plain text email');
        });
    });

    test('sends HTML email', function () {
        $htmlBody = '<html><body><h1>Hello</h1><p>This is HTML email</p></body></html>';

        Mail::send(new TestNotification([
            'recipients' => ['to' => ['recipient@example.com']],
            'subject' => 'HTML Email',
            'message' => $htmlBody,
            'html' => true
        ]));

        $this->mailer->assertSent(TestNotification::class, function ($mail) use ($htmlBody) {
            $data = $mail->getData();

            return str_contains($data['message'], '<html>')
                && ($data['html'] ?? false) === true;
        });
    });

    test('sends email with custom headers', function () {
        Mail::send(new TestNotification([
            'recipients' => ['to' => ['recipient@example.com']],
            'subject' => 'Email with Headers',
            'message' => 'Body content',
            'from' => 'sender@example.com',
            'reply_to' => 'noreply@example.com',
        ]));

        $this->mailer->assertSent(TestNotification::class, function ($mail) {
            $data = $mail->getData();

            return ($data['from'] ?? null) === 'sender@example.com'
                && ($data['reply_to'] ?? null) === 'noreply@example.com';
        });
    });
});

describe('Mail - Multiple Recipients', function () {
    test('sends email to multiple recipients', function () {
        $recipients = ['user1@example.com', 'user2@example.com', 'user3@example.com'];

        foreach ($recipients as $recipient) {
            Mail::send(new TestNotification([
                'recipients' => ['to' => [$recipient]],
                'subject' => 'Bulk Email',
                'message' => 'Message for everyone'
            ]));
        }

        expect($this->mailer->count())->toBe(3);
    });

    test('sends different content to different recipients', function () {
        Mail::send(new TestNotification([
            'recipients' => ['to' => ['admin@example.com']],
            'subject' => 'Admin Report',
            'message' => 'Admin-specific content'
        ]));

        Mail::send(new TestNotification([
            'recipients' => ['to' => ['user@example.com']],
            'subject' => 'User Notification',
            'message' => 'User-specific content'
        ]));

        expect($this->mailer->count())->toBe(2);

        $this->mailer->assertSent(TestNotification::class, fn ($m) => $m->getData()['subject'] === 'Admin Report');
        $this->mailer->assertSent(TestNotification::class, fn ($m) => $m->getData()['subject'] === 'User Notification');
    });
});

describe('Mail - Email Templates', function () {
    test('sends email using template', function () {
        $template = <<<'HTML'
        <html>
        <body>
            <h1>Welcome, {{name}}!</h1>
            <p>Your account has been created successfully.</p>
            <p>Email: {{email}}</p>
        </body>
        </html>
        HTML;

        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $body = str_replace(
            ['{{name}}', '{{email}}'],
            [$data['name'], $data['email']],
            $template
        );

        Mail::send(new TestNotification([
            'recipients' => ['to' => ['john@example.com']],
            'subject' => 'Welcome!',
            'message' => $body,
            'html' => true
        ]));

        $this->mailer->assertSent(TestNotification::class, function ($mail) {
            $data = $mail->getData();

            return str_contains($data['message'], 'Welcome, John Doe!')
                && str_contains($data['message'], 'john@example.com');
        });
    });

    test('sends password reset email', function () {
        $resetToken = bin2hex(random_bytes(32));
        $resetLink = "https://example.com/reset?token={$resetToken}";

        $body = "Click the link to reset your password: {$resetLink}";

        Mail::send(new TestNotification([
            'recipients' => ['to' => ['user@example.com']],
            'subject' => 'Password Reset',
            'message' => $body
        ]));

        $this->mailer->assertSent(TestNotification::class, function ($mail) use ($resetToken) {
            return str_contains($mail->getData()['message'], $resetToken);
        });
    });
});

describe('Mail - Attachments', function () {
    test('sends email with attachment metadata', function () {
        Mail::send(new TestNotification([
            'recipients' => ['to' => ['recipient@example.com']],
            'subject' => 'Email with Attachment',
            'message' => 'Please find the attached file.',
            'attachment' => [
                '/tmp/document.pdf' => 'document.pdf',
            ]
        ]));

        $this->mailer->assertSent(TestNotification::class, function ($mail) {
            $data = $mail->getData();

            return isset($data['attachment']['/tmp/document.pdf'])
                && $data['attachment']['/tmp/document.pdf'] === 'document.pdf';
        });
    });

    test('sends email with multiple attachments', function () {
        $attachments = [
            '/tmp/file1.pdf' => 'file1.pdf',
            '/tmp/file2.jpg' => 'file2.jpg',
        ];

        Mail::send(new TestNotification([
            'recipients' => ['to' => ['recipient@example.com']],
            'subject' => 'Multiple Attachments',
            'message' => 'Files attached',
            'attachment' => $attachments
        ]));

        $this->mailer->assertSent(TestNotification::class, function ($mail) {
            return count($mail->getData()['attachment']) === 2;
        });
    });
});

describe('Mail - Email Validation', function () {
    test('validates email address format', function () {
        $validEmails = [
            'user@example.com',
            'test.user@example.co.uk',
            'user+tag@example.com',
        ];

        foreach ($validEmails as $email) {
            expect(filter_var($email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
        }
    });

    test('rejects invalid email addresses', function () {
        $invalidEmails = [
            'invalid.email',
            '@example.com',
            'user@',
            'user @example.com',
        ];

        foreach ($invalidEmails as $email) {
            expect(filter_var($email, FILTER_VALIDATE_EMAIL))->toBeFalse();
        }
    });
});

describe('Mail - Complete Workflow', function () {
    test('sends welcome email after user registration', function () {
        $user = ['name' => 'John Doe', 'email' => 'john@example.com'];

        Mail::send(new TestNotification([
            'recipients' => ['to' => [$user['email']]],
            'subject' => 'Welcome to Our Platform!',
            'message' => "Hi {$user['name']},\n\nThank you for registering!"
        ]));

        $this->mailer->assertSent(TestNotification::class, function ($mail) use ($user) {
            return $mail->getData()['recipients']['to'] === [$user['email']];
        });
    });

    test('sends notification email workflow', function () {
        Mail::send(new TestNotification(['subject' => 'Confirm Your Action']));
        Mail::send(new TestNotification(['subject' => 'Reminder']));
        Mail::send(new TestNotification(['subject' => 'Action Completed']));

        expect($this->mailer->count())->toBe(3);
        $this->mailer->assertSent(TestNotification::class, fn ($m) => $m->getData()['subject'] === 'Confirm Your Action');
        $this->mailer->assertSent(TestNotification::class, fn ($m) => $m->getData()['subject'] === 'Reminder');
        $this->mailer->assertSent(TestNotification::class, fn ($m) => $m->getData()['subject'] === 'Action Completed');
    });
});

describe('Mail - Error Handling', function () {
    test('handles empty recipient', function () {
        $result = Mail::send(new TestNotification(['recipients' => ['to' => ['']]]));
        expect($result)->toBeInstanceOf(\Mail\MailStatus::class);
    });

    test('handles empty subject', function () {
        $result = Mail::send(new TestNotification(['subject' => '']));
        expect($result)->toBeInstanceOf(\Mail\MailStatus::class);
    });

    test('handles empty body', function () {
        $result = Mail::send(new TestNotification(['message' => '']));
        expect($result)->toBeInstanceOf(\Mail\MailStatus::class);
    });
});
