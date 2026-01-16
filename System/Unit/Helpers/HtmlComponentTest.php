<?php

declare(strict_types=1);

use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\Html\HtmlComponent;
use Helpers\Http\Flash;

beforeEach(function () {
    $this->flash = Mockery::mock(Flash::class);
    $this->flash->shouldReceive('old')->andReturn(null)->byDefault();
    $this->flash->shouldReceive('peekInputError')->andReturn(null)->byDefault();
    $this->flash->shouldReceive('getInputError')->andReturn(null)->byDefault();
});

afterEach(function () {
    Mockery::close();
});

describe('HtmlComponent', function () {

    describe('Component Instantiation', function () {
        test('creates component instance', function () {
            $component = new HtmlComponent($this->flash, 'test');
            expect($component)->toBeInstanceOf(HtmlComponent::class);
        });

        test('resolves system component path', function () {
            // Create a temporary system component for testing
            $systemPath = Paths::systemPath('Helpers/Html/components/test-component.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo "System Component"; ?>');

            $component = new HtmlComponent($this->flash, 'test-component');
            $result = $component->render();

            expect($result)->toBe('System Component');

            // Cleanup
            FileSystem::delete($systemPath);
        });
    });

    describe('Data Management', function () {
        test('sets data via data method', function () {
            $systemPath = Paths::systemPath('Helpers/Html/components/data-test.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $data_test["custom"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'data-test');
            $component->data(['custom' => 'value']);
            $result = $component->render();

            expect($result)->toBe('value');

            // Cleanup
            FileSystem::delete($systemPath);
        });

        test('sets content via content method', function () {
            $systemPath = Paths::systemPath('Helpers/Html/components/content-test.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $content_test["value"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'content-test');
            $component->content('test content');
            $result = $component->render();

            expect($result)->toBe('test content');

            // Cleanup
            FileSystem::delete($systemPath);
        });

        test('merges data from multiple calls', function () {
            $systemPath = Paths::systemPath('Helpers/Html/components/merge-test.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo ($merge_test["first"] ?? "") . ($merge_test["second"] ?? ""); ?>');

            $component = new HtmlComponent($this->flash, 'merge-test');
            $component->data(['first' => 'A']);
            $component->data(['second' => 'B']);
            $result = $component->render();

            expect($result)->toBe('AB');

            // Cleanup
            FileSystem::delete($systemPath);
        });
    });

    describe('Attributes', function () {
        test('sets attributes', function () {
            $systemPath = Paths::systemPath('Helpers/Html/components/attr-test.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $attr_test["attributes"]["class"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'attr-test');
            $component->attributes(['class' => 'btn btn-primary']);
            $result = $component->render();

            expect($result)->toBe('btn btn-primary');

            // Cleanup
            FileSystem::delete($systemPath);
        });

        test('detects field name from attributes', function () {
            $this->flash->shouldReceive('old')->with('email')->andReturn('old@example.com');

            $systemPath = Paths::systemPath('Helpers/Html/components/field-test.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $field_test["value"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'field-test');
            $component->attributes(['name' => 'email']);
            $result = $component->render();

            expect($result)->toBe('old@example.com');

            // Cleanup
            FileSystem::delete($systemPath);
        });
    });

    describe('Options and Selected Values', function () {
        test('sets options', function () {
            $systemPath = Paths::systemPath('Helpers/Html/components/options-test.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo count($options_test["options"]["data"] ?? []); ?>');

            $component = new HtmlComponent($this->flash, 'options-test');
            $component->options(['1' => 'One', '2' => 'Two']);
            $result = $component->render();

            expect($result)->toBe('2');

            // Cleanup
            FileSystem::delete($systemPath);
        });

        test('sets selected value', function () {
            $systemPath = Paths::systemPath('Helpers/Html/components/selected-test.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $selected_test["options"]["selected"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'selected-test');
            $component->selected('2');
            $result = $component->render();

            expect($result)->toBe('2');

            // Cleanup
            FileSystem::delete($systemPath);
        });
    });

    describe('Flash Data Integration', function () {
        test('populates old input value', function () {
            $this->flash->shouldReceive('old')->with('username')->andReturn('john_doe');

            $systemPath = Paths::systemPath('Helpers/Html/components/old-input.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $old_input["value"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'old-input');
            $component->attributes(['name' => 'username']);
            $result = $component->render();

            expect($result)->toBe('john_doe');

            // Cleanup
            FileSystem::delete($systemPath);
        });

        test('does not override explicit value with old input', function () {
            $this->flash->shouldReceive('old')->with('username')->andReturn('old_value');

            $systemPath = Paths::systemPath('Helpers/Html/components/explicit-value.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $explicit_value["value"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'explicit-value');
            $component->content('explicit_value');
            $component->attributes(['name' => 'username']);
            $result = $component->render();

            expect($result)->toBe('explicit_value');

            // Cleanup
            FileSystem::delete($systemPath);
        });

        test('adds is-invalid class when field has error', function () {
            $this->flash->shouldReceive('peekInputError')->with('email')->andReturn('Email is required');

            $systemPath = Paths::systemPath('Helpers/Html/components/invalid-field.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $invalid_field["attributes"]["class"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'invalid-field');
            $component->attributes(['name' => 'email', 'class' => 'form-control']);
            $result = $component->render();

            expect($result)->toContain('is-invalid');
            expect($result)->toContain('form-control');

            // Cleanup
            FileSystem::delete($systemPath);
        });

        test('includes error message for error component', function () {
            $this->flash->shouldReceive('peekInputError')->with('password')->andReturn('Password too short');
            $this->flash->shouldReceive('getInputError')->with('password')->andReturn('Password too short');

            $systemPath = Paths::systemPath('Helpers/Html/components/error.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $error["error_message"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'error');
            $component->attributes(['name' => 'password']);
            $result = $component->render();

            expect($result)->toBe('Password too short');

            // Cleanup
            FileSystem::delete($systemPath);
        });
    });

    describe('Manual Flagging', function () {
        test('manually flags component as invalid', function () {
            $systemPath = Paths::systemPath('Helpers/Html/components/manual-flag.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $manual_flag["attributes"]["class"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'manual-flag');
            $component->flagIf(true);
            $result = $component->render();

            expect($result)->toContain('is-invalid');

            // Cleanup
            FileSystem::delete($systemPath);
        });

        test('does not flag when condition is false', function () {
            $systemPath = Paths::systemPath('Helpers/Html/components/no-flag.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo $no_flag["attributes"]["class"] ?? ""; ?>');

            $component = new HtmlComponent($this->flash, 'no-flag');
            $component->flagIf(false);
            $result = $component->render();

            expect($result)->not->toContain('is-invalid');

            // Cleanup
            FileSystem::delete($systemPath);
        });
    });

    describe('Custom Path', function () {
        test('sets custom component path', function () {
            $customPath = Paths::templatePath('components/custom-test.php', 'custom-dir');
            $dir = dirname($customPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($customPath, '<?php echo "Custom Path Component"; ?>');

            $component = new HtmlComponent($this->flash, 'custom-test');
            $component->path('custom-dir');
            $result = $component->render();

            expect($result)->toBe('Custom Path Component');

            // Cleanup
            FileSystem::delete($customPath);
        });
    });

    describe('Error Handling', function () {
        test('throws exception for missing component', function () {
            $component = new HtmlComponent($this->flash, 'non-existent-component-xyz');

            expect(fn () => $component->render())
                ->toThrow(Exception::class);
        });
    });

    describe('Attribute Class Initialization', function () {
        test('initializes empty class attribute if not set', function () {
            $systemPath = Paths::systemPath('Helpers/Html/components/empty-class.php');
            $dir = dirname($systemPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($systemPath, '<?php echo isset($empty_class["attributes"]["class"]) ? "set" : "not set"; ?>');

            $component = new HtmlComponent($this->flash, 'empty-class');
            $result = $component->render();

            expect($result)->toBe('set');

            // Cleanup
            FileSystem::delete($systemPath);
        });
    });
});
