<?php

declare(strict_types=1);

use Mail\Contracts\Mailable;
use Mail\Mail;

describe('Mail Facade', function () {
    it('has static send method', function () {
        expect(method_exists(Mail::class, 'send'))->toBeTrue();
    });

    it('send method accepts Mailable parameter', function () {
        $reflection = new ReflectionMethod(Mail::class, 'send');
        $parameters = $reflection->getParameters();

        expect($parameters)->toHaveCount(1);
        expect($parameters[0]->getType()->getName())->toBe(Mailable::class);
    });

    it('send method returns MailStatus', function () {
        $reflection = new ReflectionMethod(Mail::class, 'send');
        $returnType = $reflection->getReturnType();

        expect($returnType->getName())->toBe('Mail\MailStatus');
    });
});
