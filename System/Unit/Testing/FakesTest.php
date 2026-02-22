<?php

declare(strict_types=1);

use Notify\Contracts\Notifiable;
use Testing\Fakes\AuditFake;
use Testing\Fakes\AuthFake;
use Testing\Fakes\DeferFake;
use Testing\Fakes\LogFake;
use Testing\Fakes\MailFake;
use Testing\Fakes\NotificationFake;
use Testing\Fakes\ScheduleFake;
use Testing\Fakes\SessionFake;
use Testing\Fakes\StorageFake;
use Tests\System\Support\Mail\StubMailable;
use Tests\System\Support\Security\StubAuthenticatable;

describe('Testing Fakes', function () {

    describe('StorageFake', function () {
        it('can store and retrieve files in memory', function () {
            $storage = new StorageFake();
            $storage->put('test.txt', 'Hello World');

            expect($storage->exists('test.txt'))->toBeTrue();
            expect($storage->get('test.txt'))->toBe('Hello World');
            $storage->assertExists('test.txt', 'Hello World');
        });

        it('can delete files', function () {
            $storage = new StorageFake();
            $storage->put('test.txt', 'Hello World');
            $storage->delete('test.txt');

            expect($storage->exists('test.txt'))->toBeFalse();
            $storage->assertMissing('test.txt');
        });

        it('handles directories', function () {
            $storage = new StorageFake();
            $storage->makeDirectory('logs');
            $storage->put('logs/app.log', 'test log');

            expect($storage->files('logs'))->toContain('logs/app.log');
        });
    });

    describe('NotificationFake', function () {
        it('tracks sent notifications', function () {
            $fake = new NotificationFake();
            $notification = new class () implements Notifiable {};

            $fake->send('mail', $notification);

            $fake->assertSentTo(null, get_class($notification));
        });

        it('can assert nothing sent', function () {
            $fake = new NotificationFake();
            $fake->assertNothingSent();
        });
    });

    describe('AuditFake', function () {
        it('tracks audit logs', function () {
            $fake = new AuditFake();
            $fake->log('user.login', ['ip' => '127.0.0.1']);

            $fake->assertLogged('user.login', function ($data) {
                return $data['ip'] === '127.0.0.1';
            });
        });

        it('can assert not logged', function () {
            $fake = new AuditFake();
            $fake->assertNotLogged('user.deleted');
        });
    });

    describe('LogFake', function () {
        it('tracks log entries', function () {
            $fake = new LogFake();
            $fake->error('Something went wrong', ['code' => 500]);

            $fake->assertLogged('error', function ($message, $context) {
                return $message === 'Something went wrong' && $context['code'] === 500;
            });
        });

        it('can assert nothing logged', function () {
            $fake = new LogFake();
            $fake->assertNothingLogged();
        });
    });

    describe('SessionFake', function () {
        it('tracks session data', function () {
            $fake = new SessionFake();
            $fake->set('user_id', 123);

            expect($fake->get('user_id'))->toBe(123);
            $fake->assertHas('user_id', 123);
        });

        it('can assert missing key', function () {
            $fake = new SessionFake();
            $fake->assertMissing('theme');
        });
    });

    describe('ScheduleFake', function () {
        it('tracks scheduled commands', function () {
            $fake = new ScheduleFake();
            $fake->command('email:send')->daily();

            $fake->assertScheduled('email:send');
        });
    });

    describe('DeferFake', function () {
        it('tracks deferred tasks', function () {
            $fake = new DeferFake();
            $fake->push(fn () => 'hello');

            $fake->assertDeferred();
        });
    });

    describe('AuthFake', function () {
        it('tracks authentication state', function () {
            $fake = new AuthFake();
            $user = new StubAuthenticatable();

            $fake->actingAs($user);
            $fake->assertAuthenticated();
            expect($fake->guard()->user())->toBe($user);
        });

        it('can assert guest state', function () {
            $fake = new AuthFake();
            $fake->assertGuest();
        });
    });

    describe('MailFake', function () {
        it('tracks sent mailables', function () {
            $fake = new MailFake();
            $mailable = new StubMailable();

            $fake->send($mailable);
            $fake->assertSent(get_class($mailable));
        });
    });
});
