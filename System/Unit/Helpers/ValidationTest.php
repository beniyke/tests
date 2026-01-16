<?php

declare(strict_types=1);

use Helpers\Validation\Password;
use Helpers\Validation\Regex;
use Helpers\Validation\Validator;

describe('Validator', function () {
    test('validates required fields', function () {
        $validator = new Validator();
        $validator->rules(['name' => ['required' => true]])
            ->parameters(['name' => 'Name'])
            ->validate(['name' => '']);

        expect($validator->has_error())->toBeTrue();
        expect($validator->errors())->toHaveKey('name');
    });

    test('validates email type', function () {
        $validator = new Validator();
        $validator->rules(['email' => ['type' => 'email']])
            ->parameters(['email' => 'Email'])
            ->validate(['email' => 'invalid-email']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['email' => ['type' => 'email']])
            ->parameters(['email' => 'Email'])
            ->validate(['email' => 'test@example.com']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates boolean type', function () {
        $validator = new Validator();
        $validator->rules(['is_active' => ['type' => 'boolean']])
            ->parameters(['is_active' => 'Active'])
            ->validate(['is_active' => 'not-bool']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['is_active' => ['type' => 'boolean']])
            ->parameters(['is_active' => 'Active'])
            ->validate(['is_active' => true]);

        expect($validator->has_error())->toBeFalse();

        $validator->reset()->rules(['is_active' => ['type' => 'boolean']])
            ->parameters(['is_active' => 'Active'])
            ->validate(['is_active' => '1']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates numeric type', function () {
        $validator = new Validator();
        $validator->rules(['age' => ['type' => 'numeric']])
            ->parameters(['age' => 'Age'])
            ->validate(['age' => 'abc']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['age' => ['type' => 'numeric']])
            ->parameters(['age' => 'Age'])
            ->validate(['age' => '123']);

        expect($validator->has_error())->toBeFalse();

        $validator->reset()->rules(['price' => ['type' => 'numeric']])
            ->parameters(['price' => 'Price'])
            ->validate(['price' => 12.50]);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates alphanumeric type', function () {
        $validator = new Validator();
        $validator->rules(['code' => ['type' => 'alnum']])
            ->parameters(['code' => 'Code'])
            ->validate(['code' => 'user_123']); // underscore not allowed

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['code' => ['type' => 'alnum']])
            ->parameters(['code' => 'Code'])
            ->validate(['code' => 'user123']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates alpha type', function () {
        $validator = new Validator();
        $validator->rules(['name' => ['type' => 'alpha']])
            ->parameters(['name' => 'Name'])
            ->validate(['name' => 'John Doe']); // space not allowed

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['name' => ['type' => 'alpha']])
            ->parameters(['name' => 'Name'])
            ->validate(['name' => 'JohnDoe']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates alphabetical type', function () {
        $validator = new Validator();
        $validator->rules(['name' => ['type' => 'alphabetical']])
            ->parameters(['name' => 'Name'])
            ->validate(['name' => 'A']); // min 2 chars

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['name' => ['type' => 'alphabetical']])
            ->parameters(['name' => 'Name'])
            ->validate(['name' => 'John']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates url type', function () {
        $validator = new Validator();
        $validator->rules(['website' => ['type' => 'url']])
            ->parameters(['website' => 'Website'])
            ->validate(['website' => 'example.com']); // needs protocol

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['website' => ['type' => 'url']])
            ->parameters(['website' => 'Website'])
            ->validate(['website' => 'https://example.com']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates phone type', function () {
        $validator = new Validator();
        $validator->rules(['phone' => ['type' => 'phone']])
            ->parameters(['phone' => 'Phone'])
            ->validate(['phone' => '123']); // too short

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['phone' => ['type' => 'phone']])
            ->parameters(['phone' => 'Phone'])
            ->validate(['phone' => '+1234567890']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates coordinate type', function () {
        $validator = new Validator();
        $validator->rules(['location' => ['type' => 'coordinate']])
            ->parameters(['location' => 'Location'])
            ->validate(['location' => '91,0']); // invalid lat

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['location' => ['type' => 'coordinate']])
            ->parameters(['location' => 'Location'])
            ->validate(['location' => '40.7128,-74.0060']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates date rule', function () {
        $validator = new Validator();
        $validator->rules(['dob' => ['date' => 'Y-m-d']])
            ->parameters(['dob' => 'Date of Birth'])
            ->validate(['dob' => 'invalid-date']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['dob' => ['date' => 'Y-m-d']])
            ->parameters(['dob' => 'Date of Birth'])
            ->validate(['dob' => '2023-01-01']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates regex rule', function () {
        $validator = new Validator();
        $validator->rules(['code' => ['regex' => '/^[A-Z]{3}$/']])
            ->parameters(['code' => 'Code'])
            ->validate(['code' => 'abc']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['code' => ['regex' => '/^[A-Z]{3}$/']])
            ->parameters(['code' => 'Code'])
            ->validate(['code' => 'ABC']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates is_valid rule', function () {
        $validator = new Validator();
        $validator->rules(['ip' => ['is_valid' => ['ipv4']]])
            ->parameters(['ip' => 'IP Address'])
            ->validate(['ip' => 'invalid-ip']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['ip' => ['is_valid' => ['ipv4']]])
            ->parameters(['ip' => 'IP Address'])
            ->validate(['ip' => '192.168.1.1']);

        expect($validator->has_error())->toBeFalse();

        // Test invalid range
        $validator->reset()->rules(['ip' => ['is_valid' => ['ipv4']]])
            ->parameters(['ip' => 'IP Address'])
            ->validate(['ip' => '256.256.256.256']);

        expect($validator->has_error())->toBeTrue();
    });

    test('validates contains_valid rule', function () {
        $validator = new Validator();
        $validator->rules(['text' => ['contains_valid' => ['email']]])
            ->parameters(['text' => 'Text'])
            ->validate(['text' => 'No email here']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['text' => ['contains_valid' => ['email']]])
            ->parameters(['text' => 'Text'])
            ->validate(['text' => 'Contact us at test@example.com']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates contains_any_valid rule', function () {
        $validator = new Validator();
        $validator->rules(['text' => ['contains_any_valid' => ['email', 'url']]])
            ->parameters(['text' => 'Text'])
            ->validate(['text' => 'Just plain text']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['text' => ['contains_any_valid' => ['email', 'url']]])
            ->parameters(['text' => 'Text'])
            ->validate(['text' => 'Visit https://example.com']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates less_than rule', function () {
        $validator = new Validator();
        $validator->rules(['price' => ['less_than' => 'max_price']])
            ->parameters(['price' => 'Price', 'max_price' => 'Max Price'])
            ->validate(['price' => 100, 'max_price' => 50]);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['price' => ['less_than' => 'max_price']])
            ->parameters(['price' => 'Price', 'max_price' => 'Max Price'])
            ->validate(['price' => 40, 'max_price' => 50]);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates greater_than rule', function () {
        $validator = new Validator();
        $validator->rules(['price' => ['greater_than' => 'min_price']])
            ->parameters(['price' => 'Price', 'min_price' => 'Min Price'])
            ->validate(['price' => 10, 'min_price' => 50]);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['price' => ['greater_than' => 'min_price']])
            ->parameters(['price' => 'Price', 'min_price' => 'Min Price'])
            ->validate(['price' => 60, 'min_price' => 50]);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates limit rule', function () {
        $validator = new Validator();
        $validator->rules(['age' => ['limit' => '18|100']])
            ->parameters(['age' => 'Age'])
            ->validate(['age' => 15]);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['age' => ['limit' => '18|100']])
            ->parameters(['age' => 'Age'])
            ->validate(['age' => 25]);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates confirm rule', function () {
        $validator = new Validator();
        $validator->rules(['password' => ['confirm' => 'password_confirmation']])
            ->parameters(['password' => 'Password', 'password_confirmation' => 'Confirmation'])
            ->validate(['password' => 'secret']); // missing confirmation field

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['password' => ['confirm' => 'password_confirmation']])
            ->parameters(['password' => 'Password', 'password_confirmation' => 'Confirmation'])
            ->validate(['password' => 'secret', 'password_confirmation' => 'secret']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates not_same rule', function () {
        $validator = new Validator();
        $validator->rules(['new_password' => ['not_same' => 'old_password']])
            ->parameters(['new_password' => 'New Password', 'old_password' => 'Old Password'])
            ->validate(['new_password' => 'secret', 'old_password' => 'secret']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['new_password' => ['not_same' => 'old_password']])
            ->parameters(['new_password' => 'New Password', 'old_password' => 'Old Password'])
            ->validate(['new_password' => 'newsecret', 'old_password' => 'oldsecret']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates not_contain rule', function () {
        $validator = new Validator();
        $validator->rules(['username' => ['not_contain' => 'password']])
            ->parameters(['username' => 'Username', 'password' => 'Password'])
            ->validate(['username' => 'user_secret', 'password' => 'secret']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['username' => ['not_contain' => 'password']])
            ->parameters(['username' => 'Username', 'password' => 'Password'])
            ->validate(['username' => 'john_doe', 'password' => 'secret']);

        expect($validator->has_error())->toBeFalse();

        // Case sensitivity check
        $validator->reset()->rules(['username' => ['not_contain' => 'password']])
            ->parameters(['username' => 'Username', 'password' => 'Password'])
            ->validate(['username' => 'USER_SECRET', 'password' => 'secret']);

        expect($validator->has_error())->toBeTrue();
    });

    test('validates custom rule', function () {
        $validator = new Validator();
        $validator->rules(['code' => ['custom' => fn ($val) => $val === 'valid']])
            ->parameters(['code' => 'Code'])
            ->validate(['code' => 'invalid']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['code' => ['custom' => fn ($val) => $val === 'valid']])
            ->parameters(['code' => 'Code'])
            ->validate(['code' => 'valid']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates minlength', function () {
        $validator = new Validator();
        $validator->rules(['password' => ['minlength' => 8]])
            ->parameters(['password' => 'Password'])
            ->validate(['password' => 'short']);

        expect($validator->has_error())->toBeTrue();
    });

    test('validates maxlength', function () {
        $validator = new Validator();
        $validator->rules(['username' => ['maxlength' => 10]])
            ->parameters(['username' => 'Username'])
            ->validate(['username' => 'verylongusername']);

        expect($validator->has_error())->toBeTrue();
    });

    test('validates same rule', function () {
        $validator = new Validator();
        $validator->rules([
            'password' => ['required' => true],
            'confirm_password' => ['same' => 'password'],
        ])
            ->parameters(['password' => 'Password', 'confirm_password' => 'Confirm Password'])
            ->validate(['password' => 'secret', 'confirm_password' => 'different']);

        expect($validator->has_error())->toBeTrue();
    });

    test('passes valid data', function () {
        $validator = new Validator();
        $validator->rules([
            'email' => ['required' => true, 'type' => 'email'],
            'age' => ['type' => 'int'],
        ])
            ->parameters(['email' => 'Email', 'age' => 'Age'])
            ->validate(['email' => 'test@example.com', 'age' => 25]);

        expect($validator->has_error())->toBeFalse();
        expect($validator->validated())->not->toBeNull();
    });

    test('transforms data', function () {
        $validator = new Validator();
        $validator->rules(['email' => ['type' => 'email']])
            ->parameters(['email' => 'Email'])
            ->transform(fn ($data) => ['email' => strtolower($data['email'])])
            ->validate(['email' => 'TEST@EXAMPLE.COM']);

        expect($validator->validated()->email)->toBe('test@example.com');
    });

    test('excludes fields from validated data', function () {
        $validator = new Validator();
        $validator->rules(['name' => ['required' => true], 'temp' => []])
            ->parameters(['name' => 'Name'])
            ->exclude(['temp'])
            ->validate(['name' => 'John', 'temp' => 'value']);

        $validated = $validator->validated();
        expect($validated->has('temp'))->toBeFalse();
    });

    test('modifies field names', function () {
        $validator = new Validator();
        $validator->rules(['user_email' => ['type' => 'email']])
            ->parameters(['user_email' => 'Email'])
            ->modify(['user_email' => 'email'])
            ->validate(['user_email' => 'test@example.com']);

        $validated = $validator->validated();
        expect($validated->has('email'))->toBeTrue();
        expect($validated->has('user_email'))->toBeFalse();
    });
});

describe('Password', function () {
    test('validates minimum length', function () {
        $password = new Password();
        $password->minimum_length(8)->check('short');

        expect($password->is_valid())->toBeFalse();
        expect($password->errors())->not->toBeEmpty();
    });

    test('requires uppercase', function () {
        $password = new Password();
        $password->require_uppercase()->check('lowercase123');

        expect($password->is_valid())->toBeFalse();
    });

    test('requires lowercase', function () {
        $password = new Password();
        $password->require_lowercase()->check('UPPERCASE123');

        expect($password->is_valid())->toBeFalse();
    });

    test('requires numeric', function () {
        $password = new Password();
        $password->require_numeric()->check('NoNumbers');

        expect($password->is_valid())->toBeFalse();
    });

    test('requires special characters', function () {
        $password = new Password();
        $password->require_special()->check('NoSpecial123');

        expect($password->is_valid())->toBeFalse();
    });

    test('passes strong password', function () {
        $password = new Password();
        $password->minimum_length(8)
            ->require_uppercase()
            ->require_lowercase()
            ->require_numeric()
            ->require_special()
            ->check('Strong@Pass123');

        expect($password->is_valid())->toBeTrue();
    });

    test('rejects common passwords', function () {
        $password = new Password();
        $password->not_in(['password123', '123456'])->check('password123');

        expect($password->is_valid())->toBeFalse();
    });

    test('validates maximum length', function () {
        $password = new Password();
        $password->maximum_length(10)->check('verylongpassword');

        expect($password->is_valid())->toBeFalse();
    });

    test('validates not_having rule', function () {
        $password = new Password();
        $password->not_having(['123', 'abc'])->check('password123');

        expect($password->is_valid())->toBeFalse();
    });
});

describe('Regex', function () {
    test('validates email', function () {
        expect(Regex::is('email', 'test@example.com'))->toBeTrue();
        expect(Regex::is('email', 'invalid-email'))->toBeFalse();
    });

    test('validates URL', function () {
        expect(Regex::is('url', 'https://example.com'))->toBeTrue();
        expect(Regex::is('url', 'not-a-url'))->toBeFalse();
    });

    test('validates phone number', function () {
        expect(Regex::is('intphone', '+1234567890'))->toBeTrue();
    });

    test('has checks for substring match', function () {
        expect(Regex::has('email', 'Contact us at test@example.com'))->toBeTrue();
    });

    test('hasAny checks for any pattern match', function () {
        $text = 'Visit https://example.com or email test@example.com';
        expect(Regex::hasAny(['url', 'email'], $text))->toBeTrue();
    });

    test('getDescription returns pattern description', function () {
        $description = Regex::getDescription('email');
        expect($description)->toBeString()->not->toBeEmpty();
    });

    test('validates IPv4', function () {
        expect(Regex::is('ipv4', '192.168.1.1'))->toBeTrue();
        expect(Regex::is('ipv4', '256.256.256.256'))->toBeFalse();
    });

    test('validates IPv6', function () {
        expect(Regex::is('ipv6', '2001:0db8:85a3:0000:0000:8a2e:0370:7334'))->toBeTrue();
        expect(Regex::is('ipv6', 'invalid-ipv6'))->toBeFalse();
    });

    test('validates credit card', function () {
        expect(Regex::is('creditcard', '1234-5678-9012-3456'))->toBeTrue();
        // Regex only checks format
        expect(Regex::is('creditcard', '1234567890123456'))->toBeTrue();
    });

    test('validates UUID', function () {
        expect(Regex::is('uuid', '123e4567-e89b-12d3-a456-426614174000'))->toBeTrue();
        expect(Regex::is('uuid', 'invalid-uuid'))->toBeFalse();
    });
});

describe('ValidationTrait Strictness', function () {
    test('validates strict ipv4', function () {
        $validator = new Validator();
        $validator->rules(['ip' => ['type' => 'ipv4']])
            ->parameters(['ip' => 'IP'])
            ->validate(['ip' => '256.256.256.256']);

        expect($validator->has_error())->toBeTrue();

        $validator->reset()->rules(['ip' => ['type' => 'ipv4']])
            ->parameters(['ip' => 'IP'])
            ->validate(['ip' => '192.168.1.1']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates strict ipv6', function () {
        $validator = new Validator();
        $validator->rules(['ip' => ['type' => 'ipv6']])
            ->parameters(['ip' => 'IP'])
            ->validate(['ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);

        expect($validator->has_error())->toBeFalse();

        $validator->reset()->rules(['ip' => ['type' => 'ipv6']])
            ->parameters(['ip' => 'IP'])
            ->validate(['ip' => 'invalid-ipv6']);

        expect($validator->has_error())->toBeTrue();
    });

    test('validates strict credit card', function () {
        $validator = new Validator();
        // Valid Luhn (Visa)
        $validator->rules(['cc' => ['type' => 'creditcard']])
            ->parameters(['cc' => 'Credit Card'])
            ->validate(['cc' => '4539 1488 0343 6467']);

        expect($validator->has_error())->toBeFalse();

        // Invalid Luhn
        $validator->reset()->rules(['cc' => ['type' => 'creditcard']])
            ->parameters(['cc' => 'Credit Card'])
            ->validate(['cc' => '4539 1488 0343 6468']);

        expect($validator->has_error())->toBeTrue();
    });
});
