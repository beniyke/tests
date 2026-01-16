<?php

declare(strict_types=1);

use Mail\Core\EmailBuilder;
use Mail\Mailer;

afterEach(function () {
    Mockery::close();
});

describe('Mailer', function () {
    test('creates mailer instance', function () {
        expect(class_exists(Mailer::class))->toBeTrue();
    });

    test('mailer requires mailable with toMail method', function () {
        $driver = Mockery::mock('Mail\Contracts\MailDriverInterface');
        $config = Mockery::mock('Core\Services\ConfigServiceInterface');
        $builder = Mockery::mock('Mail\Core\EmailBuilder');

        $mailer = new Mailer($driver, $config, $builder);

        expect($mailer)->toBeInstanceOf(Mailer::class);
    });
});

describe('EmailBuilder', function () {
    beforeEach(function () {
        $this->loader = Mockery::mock('Mail\Contracts\TemplateLoaderInterface');
        $this->renderer = Mockery::mock('Mail\Contracts\TemplateRendererInterface');
        $this->debugSaver = Mockery::mock('Mail\Services\MailDebugSaver');
        $this->config = Mockery::mock('Core\Services\ConfigServiceInterface');
        $this->assets = Mockery::mock('Helpers\Html\Assets');

        // Setup default config expectations
        $this->config->shouldReceive('get')
            ->with('mail.builder.brand.logo')
            ->andReturn('logo.png');
        $this->config->shouldReceive('get')
            ->with('mail.builder.brand.name')
            ->andReturn('Test App');

        // Setup assets mock
        $this->assets->shouldReceive('url')
            ->with('logo.png')
            ->andReturn('http://example.com/assets/logo.png');
    });

    test('sets subject', function () {
        $builder = new EmailBuilder(
            $this->loader,
            $this->renderer,
            $this->debugSaver,
            $this->config,
            $this->assets
        );

        $result = $builder->subject('Test Subject');

        expect($result)->toBeInstanceOf(EmailBuilder::class);
    });

    test('sets content', function () {
        $builder = new EmailBuilder(
            $this->loader,
            $this->renderer,
            $this->debugSaver,
            $this->config,
            $this->assets
        );

        $result = $builder->content('Test body content');

        expect($result)->toBeInstanceOf(EmailBuilder::class);
    });

    test('sets title', function () {
        $builder = new EmailBuilder(
            $this->loader,
            $this->renderer,
            $this->debugSaver,
            $this->config,
            $this->assets
        );

        $result = $builder->title('Custom Title');

        expect($result)->toBeInstanceOf(EmailBuilder::class);
    });

    test('sets logo', function () {
        $builder = new EmailBuilder(
            $this->loader,
            $this->renderer,
            $this->debugSaver,
            $this->config,
            $this->assets
        );

        $result = $builder->logo('custom-logo.png');

        expect($result)->toBeInstanceOf(EmailBuilder::class);
    });

    test('sets footnote', function () {
        $builder = new EmailBuilder(
            $this->loader,
            $this->renderer,
            $this->debugSaver,
            $this->config,
            $this->assets
        );

        $result = $builder->footnote('Custom footnote');

        expect($result)->toBeInstanceOf(EmailBuilder::class);
    });

    test('sets template', function () {
        $this->config->shouldReceive('get')
            ->with('mail.builder.templates')
            ->andReturn(['welcome' => 'templates/welcome.html']);

        $builder = new EmailBuilder(
            $this->loader,
            $this->renderer,
            $this->debugSaver,
            $this->config,
            $this->assets
        );

        $result = $builder->template('welcome');

        expect($result)->toBeInstanceOf(EmailBuilder::class);
    });

    test('builds email content', function () {
        $this->config->shouldReceive('isDebugEnabled')->andReturn(false);
        $this->loader->shouldReceive('load')->andReturn('<html>{{content}}</html>');
        $this->renderer->shouldReceive('render')->andReturn('<html>Test Content</html>');

        $builder = new EmailBuilder(
            $this->loader,
            $this->renderer,
            $this->debugSaver,
            $this->config,
            $this->assets
        );

        $html = $builder
            ->subject('Test')
            ->content('Test Content')
            ->build();

        expect($html)->toBeString();
        expect($html)->toContain('Test Content');
    });

    test('supports method chaining', function () {
        $builder = new EmailBuilder(
            $this->loader,
            $this->renderer,
            $this->debugSaver,
            $this->config,
            $this->assets
        );

        $result = $builder
            ->subject('Test Subject')
            ->content('Test Content')
            ->title('Test Title')
            ->logo('test.png')
            ->footnote('Test Footer');

        expect($result)->toBeInstanceOf(EmailBuilder::class);
    });
});
