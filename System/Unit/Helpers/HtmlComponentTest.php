<?php

declare(strict_types=1);

use Helpers\File\Adapters\Interfaces\PathResolverInterface;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\Html\HtmlComponent;
use Helpers\Http\Flash;

beforeEach(function () {
    $this->flash = Mockery::mock(Flash::class);
    $this->flash->shouldReceive('old')->andReturn(null)->byDefault();
    $this->flash->shouldReceive('peekInputError')->andReturn(null)->byDefault();
    $this->flash->shouldReceive('getInputError')->andReturn(null)->byDefault();

    $this->paths = Mockery::mock(PathResolverInterface::class);
});

afterEach(function () {
    Mockery::close();
});

describe('HtmlComponent', function () {

    describe('Component Instantiation', function () {
        test('creates component instance', function () {
            $this->paths->shouldReceive('systemPath')->andReturn(Paths::systemPath('Helpers/Html/components/test'));
            $this->paths->shouldReceive('templatePath')->andReturn(Paths::templatePath('components/test'));

            $component = new HtmlComponent($this->flash, 'test', $this->paths);
            expect($component)->toBeInstanceOf(HtmlComponent::class);
        });

        test('resolves system component path', function () {
            // Create a temporary test component
            $testComponentPath = Paths::testPath('storage/components/test-component.php');
            $dir = dirname($testComponentPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testComponentPath, '<?php echo "System Component"; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/test-component')
                ->andReturn($testComponentPath);

            $component = new HtmlComponent($this->flash, 'test-component', $this->paths);
            $result = $component->render();

            expect($result)->toBe('System Component');

            // Cleanup
            FileSystem::delete($testComponentPath);
        });
    });

    describe('Data Management', function () {
        test('sets data via data method', function () {
            $testPath = Paths::testPath('storage/components/data-test.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $data_test["custom"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/data-test')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'data-test', $this->paths);
            $component->data(['custom' => 'value']);
            $result = $component->render();

            expect($result)->toBe('value');

            // Cleanup
            FileSystem::delete($testPath);
        });

        test('sets content via content method', function () {
            $testPath = Paths::testPath('storage/components/content-test.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $content_test["value"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/content-test')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'content-test', $this->paths);
            $component->content('test content');
            $result = $component->render();

            expect($result)->toBe('test content');

            // Cleanup
            FileSystem::delete($testPath);
        });

        test('merges data from multiple calls', function () {
            $testPath = Paths::testPath('storage/components/merge-test.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo ($merge_test["first"] ?? "") . ($merge_test["second"] ?? ""); ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/merge-test')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'merge-test', $this->paths);
            $component->data(['first' => 'A']);
            $component->data(['second' => 'B']);
            $result = $component->render();

            expect($result)->toBe('AB');

            // Cleanup
            FileSystem::delete($testPath);
        });
    });

    describe('Attributes', function () {
        test('sets attributes', function () {
            $testPath = Paths::testPath('storage/components/attr-test.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $attr_test["attributes"]["class"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/attr-test')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'attr-test', $this->paths);
            $component->attributes(['class' => 'btn btn-primary']);
            $result = $component->render();

            expect($result)->toBe('btn btn-primary');

            // Cleanup
            FileSystem::delete($testPath);
        });

        test('detects field name from attributes', function () {
            $this->flash->shouldReceive('old')->with('email')->andReturn('old@example.com');

            $testPath = Paths::testPath('storage/components/field-test.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $field_test["value"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/field-test')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'field-test', $this->paths);
            $component->attributes(['name' => 'email']);
            $result = $component->render();

            expect($result)->toBe('old@example.com');

            // Cleanup
            FileSystem::delete($testPath);
        });
    });

    describe('Options and Selected Values', function () {
        test('sets options', function () {
            $testPath = Paths::testPath('storage/components/options-test.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo count($options_test["options"]["data"] ?? []); ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/options-test')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'options-test', $this->paths);
            $component->options(['1' => 'One', '2' => 'Two']);
            $result = $component->render();

            expect($result)->toBe('2');

            // Cleanup
            FileSystem::delete($testPath);
        });

        test('sets selected value', function () {
            $testPath = Paths::testPath('storage/components/selected-test.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $selected_test["options"]["selected"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/selected-test')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'selected-test', $this->paths);
            $component->selected('2');
            $result = $component->render();

            expect($result)->toBe('2');

            // Cleanup
            FileSystem::delete($testPath);
        });
    });

    describe('Flash Data Integration', function () {
        test('populates old input value', function () {
            $this->flash->shouldReceive('old')->with('username')->andReturn('john_doe');

            $testPath = Paths::testPath('storage/components/old-input.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $old_input["value"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/old-input')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'old-input', $this->paths);
            $component->attributes(['name' => 'username']);
            $result = $component->render();

            expect($result)->toBe('john_doe');

            // Cleanup
            FileSystem::delete($testPath);
        });

        test('does not override explicit value with old input', function () {
            $this->flash->shouldReceive('old')->with('username')->andReturn('old_value');

            $testPath = Paths::testPath('storage/components/explicit-value.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $explicit_value["value"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/explicit-value')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'explicit-value', $this->paths);
            $component->content('explicit_value');
            $component->attributes(['name' => 'username']);
            $result = $component->render();

            expect($result)->toBe('explicit_value');

            // Cleanup
            FileSystem::delete($testPath);
        });

        test('adds is-invalid class when field has error', function () {
            $this->flash->shouldReceive('peekInputError')->with('email')->andReturn('Email is required');

            $testPath = Paths::testPath('storage/components/invalid-field.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $invalid_field["attributes"]["class"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/invalid-field')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'invalid-field', $this->paths);
            $component->attributes(['name' => 'email', 'class' => 'form-control']);
            $result = $component->render();

            expect($result)->toContain('is-invalid');
            expect($result)->toContain('form-control');

            // Cleanup
            FileSystem::delete($testPath);
        });

        test('includes error message for error component', function () {
            $this->flash->shouldReceive('peekInputError')->with('password')->andReturn('Password too short');
            $this->flash->shouldReceive('getInputError')->with('password')->andReturn('Password too short');

            $testPath = Paths::testPath('storage/components/error.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $error["error_message"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/error')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'error', $this->paths);
            $component->attributes(['name' => 'password']);
            $result = $component->render();

            expect($result)->toBe('Password too short');

            // Cleanup
            FileSystem::delete($testPath);
        });
    });

    describe('Manual Flagging', function () {
        test('manually flags component as invalid', function () {
            $testPath = Paths::testPath('storage/components/manual-flag.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $manual_flag["attributes"]["class"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/manual-flag')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'manual-flag', $this->paths);
            $component->flagIf(true);
            $result = $component->render();

            expect($result)->toContain('is-invalid');

            // Cleanup
            FileSystem::delete($testPath);
        });

        test('does not flag when condition is false', function () {
            $testPath = Paths::testPath('storage/components/no-flag.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo $no_flag["attributes"]["class"] ?? ""; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/no-flag')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'no-flag', $this->paths);
            $component->flagIf(false);
            $result = $component->render();

            expect($result)->not->toContain('is-invalid');

            // Cleanup
            FileSystem::delete($testPath);
        });
    });

    describe('Custom Path', function () {
        test('sets custom component path', function () {
            // Using testPath for both the file and the path resolution expectation
            $testPath = Paths::testPath('storage/custom-dir/Views/Templates/components/custom-test.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo "Custom Path Component"; ?>');

            $this->paths->shouldReceive('systemPath')->andReturn(''); // Not found in system
            $this->paths->shouldReceive('templatePath')
                ->with('components/custom-test')
                ->andReturn(''); // Not found in default template path

            $this->paths->shouldReceive('templatePath')
                ->with('components/custom-test', 'custom-dir')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'custom-test', $this->paths);
            $component->path('custom-dir');
            $result = $component->render();

            expect($result)->toBe('Custom Path Component');

            // Cleanup
            FileSystem::delete(Paths::testPath('storage/custom-dir'));
        });
    });

    describe('Error Handling', function () {
        test('throws exception for missing component', function () {
            $this->paths->shouldReceive('systemPath')->andReturn('/non-existent/system/test');
            $this->paths->shouldReceive('templatePath')->andReturn('/non-existent/template/test');

            $component = new HtmlComponent($this->flash, 'non-existent-component-xyz', $this->paths);

            expect(fn () => $component->render())
                ->toThrow(Exception::class);
        });
    });

    describe('Attribute Class Initialization', function () {
        test('initializes empty class attribute if not set', function () {
            $testPath = Paths::testPath('storage/components/empty-class.php');
            $dir = dirname($testPath);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testPath, '<?php echo isset($empty_class["attributes"]["class"]) ? "set" : "not set"; ?>');

            $this->paths->shouldReceive('systemPath')
                ->with('Helpers/Html/components/empty-class')
                ->andReturn($testPath);

            $component = new HtmlComponent($this->flash, 'empty-class', $this->paths);
            $result = $component->render();

            expect($result)->toBe('set');

            // Cleanup
            FileSystem::delete($testPath);
        });
    });
});
