<?php

declare(strict_types=1);

use Cli\Build\MigrationParser;
use Cli\Build\SmartModelBuilder;
use Helpers\File\FileSystem;
use Helpers\File\Paths;

describe('MigrationParser::detectTableName', function () {

    test('extracts table name from Schema::create', function () {
        $content = "Schema::create('posts', function (SchemaBuilder \$table) {";
        expect(MigrationParser::detectTableName($content))->toBe('posts');
    });

    test('returns null when no create statement found', function () {
        expect(MigrationParser::detectTableName('some random content'))->toBeNull();
    });

    test('handles double-quoted table name', function () {
        $content = 'Schema::create("orders", function (SchemaBuilder $table) {';
        expect(MigrationParser::detectTableName($content))->toBe('orders');
    });
})->covers(MigrationParser::class);

describe('MigrationParser::detectTimestamps', function () {

    test('detects timestamps variant', function (string $input, bool $expected) {
        expect(MigrationParser::detectTimestamps($input))->toBe($expected);
    })->with([
        'timestamps()' => ['$table->timestamps();', true],
        'dateTimestamps()' => ['$table->dateTimestamps();', true],
        'unrelated method' => ['$table->string("name");', false],
        'empty content' => ['', false],
    ]);
})->covers(MigrationParser::class);

describe('MigrationParser::detectSoftDeletes', function () {

    test('detects soft delete variant', function (string $input, bool $expected) {
        expect(MigrationParser::detectSoftDeletes($input))->toBe($expected);
    })->with([
        'softDeletes()' => ['$table->softDeletes();', true],
        'softDeletesTz()' => ['$table->softDeletesTz();', true],
        'timestamps (not soft deletes)' => ['$table->timestamps();', false],
        'empty content' => ['', false],
    ]);
})->covers(MigrationParser::class);

describe('MigrationParser::parseColumns', function () {

    test('maps column type to expected cast', function (string $method, string $colName, string $expectedCast) {
        $content = "\$table->{$method}('{$colName}');";
        $columns = MigrationParser::parseColumns($content);

        expect($columns)->toHaveCount(1);
        expect($columns[0]['cast'])->toBe($expectedCast);
    })->with([
        'string' => ['string', 'title', 'string'],
        'text' => ['text', 'body', 'string'],
        'mediumText' => ['mediumText', 'content', 'string'],
        'longText' => ['longText', 'description', 'string'],
        'char' => ['char', 'code', 'string'],
        'uuid' => ['uuid', 'ref', 'string'],
        'enum' => ['enum', 'status', 'string'],
        'integer' => ['integer', 'views', 'int'],
        'tinyInteger' => ['tinyInteger', 'level', 'int'],
        'smallInteger' => ['smallInteger', 'rank', 'int'],
        'bigInteger' => ['bigInteger', 'likes', 'int'],
        'unsignedBigInteger' => ['unsignedBigInteger', 'user_id', 'int'],
        'unsignedInteger' => ['unsignedInteger', 'count', 'int'],
        'boolean' => ['boolean', 'is_active', 'boolean'],
        'decimal' => ['decimal', 'price', 'float'],
        'float' => ['float', 'rate', 'float'],
        'double' => ['double', 'amount', 'float'],
        'dateTime' => ['dateTime', 'published_at', 'datetime'],
        'timestamp' => ['timestamp', 'verified_at', 'datetime'],
        'date' => ['date', 'birth_date', 'date'],
        'json' => ['json', 'metadata', 'array'],
        'year' => ['year', 'graduation_year', 'int'],
    ]);

    test('detects nullable modifier', function () {
        $content = "\$table->string('slug')->nullable();";
        $columns = MigrationParser::parseColumns($content);

        expect($columns[0]['nullable'])->toBeTrue();
    });

    test('detects non-nullable by default', function () {
        $content = "\$table->string('title');";
        $columns = MigrationParser::parseColumns($content);

        expect($columns[0]['nullable'])->toBeFalse();
    });

    test('extracts default values', function (string $input, mixed $expected) {
        $columns = MigrationParser::parseColumns($input);

        expect($columns[0]['default'])->toBe($expected);
    })->with([
        'boolean true' => ["\$table->boolean('is_active')->default(true);", true],
        'boolean false' => ["\$table->boolean('is_active')->default(false);", false],
        'integer zero' => ["\$table->integer('views')->default(0);", 0],
        'integer positive' => ["\$table->integer('rank')->default(10);", 10],
        'string value' => ["\$table->string('status')->default('draft');", 'draft'],
        'no default' => ["\$table->string('title');", null],
    ]);

    test('skips system columns', function (string $systemCol) {
        $content = "\$table->string('{$systemCol}');";
        $columns = MigrationParser::parseColumns($content);

        expect($columns)->toBeEmpty();
    })->with(['id', 'created_at', 'updated_at', 'deleted_at']);

    test('ignores unknown schema methods', function () {
        $content = <<<'PHP'
            $table->index('email');
            $table->unique('slug');
            $table->primary('id');
        PHP;

        expect(MigrationParser::parseColumns($content))->toBeEmpty();
    });
})->covers(MigrationParser::class);

describe('MigrationParser::parseForeignKeys', function () {

    test('extracts single foreign key', function () {
        $content = "\$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');";
        $foreignKeys = MigrationParser::parseForeignKeys($content);

        expect($foreignKeys)->toHaveCount(1);
        expect($foreignKeys[0])->toBe([
            'column' => 'user_id',
            'references' => 'id',
            'on' => 'users',
        ]);
    });

    test('extracts multiple foreign keys', function () {
        $content = <<<'PHP'
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories');
        PHP;

        $foreignKeys = MigrationParser::parseForeignKeys($content);

        expect($foreignKeys)->toHaveCount(2);
        expect($foreignKeys[0]['on'])->toBe('users');
        expect($foreignKeys[1]['on'])->toBe('categories');
    });

    test('returns empty array when no foreign keys', function () {
        expect(MigrationParser::parseForeignKeys('$table->string("name");'))->toBeEmpty();
    });
})->covers(MigrationParser::class);

describe('MigrationParser::parse (full file)', function () {

    test('parses a complete migration file end-to-end', function () {
        $content = <<<'PHP'
        <?php
        Schema::create('post', function (SchemaBuilder $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_published')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        PHP;

        $tmpFile = Paths::storagePath('testing/migration_' . uniqid() . '.php');
        FileSystem::mkdir(dirname($tmpFile));
        FileSystem::put($tmpFile, $content);

        try {
            $result = MigrationParser::parse($tmpFile);

            expect($result['tableName'])->toBe('post');
            expect($result['hasTimestamps'])->toBeTrue();
            expect($result['hasSoftDeletes'])->toBeTrue();
            expect($result['columns'])->toHaveCount(6);
            expect($result['foreignKeys'])->toHaveCount(1);
        } finally {
            FileSystem::delete($tmpFile);
        }
    });

    test('throws RuntimeException for missing file', function () {
        MigrationParser::parse('/nonexistent/migration_' . uniqid() . '.php');
    })->throws(RuntimeException::class);
})->covers(MigrationParser::class);

describe('MigrationParser Inference', function () {

    test('infers fillable from column names', function () {
        $columns = [
            ['name' => 'title', 'type' => 'string', 'cast' => 'string', 'nullable' => false],
            ['name' => 'body', 'type' => 'text', 'cast' => 'string', 'nullable' => false],
            ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'cast' => 'int', 'nullable' => false],
        ];

        expect(MigrationParser::inferFillable($columns))->toBe(['title', 'body', 'user_id']);
    });

    test('returns empty fillable for empty columns', function () {
        expect(MigrationParser::inferFillable([]))->toBe([]);
    });

    test('infers casts excluding string type', function () {
        $columns = [
            ['name' => 'title', 'type' => 'string', 'cast' => 'string', 'nullable' => false],
            ['name' => 'views', 'type' => 'integer', 'cast' => 'int', 'nullable' => false],
            ['name' => 'is_active', 'type' => 'boolean', 'cast' => 'boolean', 'nullable' => false],
        ];

        $casts = MigrationParser::inferCasts($columns, false);

        expect($casts)->toHaveKey('views', 'int');
        expect($casts)->toHaveKey('is_active', 'boolean');
        expect($casts)->not->toHaveKey('title');
    });

    test('includes timestamp casts when timestamps enabled', function () {
        $casts = MigrationParser::inferCasts([], true);

        expect($casts)->toHaveKey('created_at', 'datetime');
        expect($casts)->toHaveKey('updated_at', 'datetime');
    });

    test('always includes id cast', function () {
        $casts = MigrationParser::inferCasts([], false);

        expect($casts)->toHaveKey('id', 'int');
    });

    test('identifies hidden fields by name pattern', function (string $name, bool $shouldBeHidden) {
        $columns = [['name' => $name, 'type' => 'string', 'cast' => 'string', 'nullable' => false]];
        $hidden = MigrationParser::inferHidden($columns);

        if ($shouldBeHidden) {
            expect($hidden)->toContain($name);
        } else {
            expect($hidden)->not->toContain($name);
        }
    })->with([
        'password' => ['password', true],
        'reset_token' => ['reset_token', true],
        'api_secret' => ['api_secret', true],
        'remember_token' => ['remember_token', true],
        'access_token' => ['access_token', true],
        'name (not hidden)' => ['name', false],
        'email (not hidden)' => ['email', false],
        'token_count (not hidden)' => ['token_count', false],
    ]);

    test('infers confirmed belongsTo from foreign keys', function () {
        $columns = [['name' => 'user_id', 'type' => 'unsignedBigInteger', 'cast' => 'int', 'nullable' => false]];
        $foreignKeys = [['column' => 'user_id', 'references' => 'id', 'on' => 'users']];

        $relationships = MigrationParser::inferRelationships($columns, $foreignKeys);

        expect($relationships)->toHaveCount(1);
        expect($relationships[0])->toMatchArray([
            'type' => 'belongsTo',
            'model' => 'User',
            'confirmed' => true,
        ]);
    });

    test('infers unconfirmed belongsTo from _id columns without FK definition', function () {
        $columns = [['name' => 'category_id', 'type' => 'unsignedBigInteger', 'cast' => 'int', 'nullable' => false]];

        $relationships = MigrationParser::inferRelationships($columns, []);

        expect($relationships)->toHaveCount(1);
        expect($relationships[0]['confirmed'])->toBeFalse();
        expect($relationships[0]['model'])->toBe('Category');
    });

    test('does not create duplicate relationship when FK matches _id column', function () {
        $columns = [['name' => 'user_id', 'type' => 'unsignedBigInteger', 'cast' => 'int', 'nullable' => false]];
        $foreignKeys = [['column' => 'user_id', 'references' => 'id', 'on' => 'users']];

        expect(MigrationParser::inferRelationships($columns, $foreignKeys))->toHaveCount(1);
    });

    test('generates boolean scopes with positive and negative variants', function () {
        $columns = [['name' => 'is_published', 'type' => 'boolean', 'cast' => 'boolean', 'nullable' => false]];
        $scopes = MigrationParser::inferScopes($columns);

        expect($scopes)->toHaveCount(2);
        expect($scopes[0])->toMatchArray(['name' => 'Published', 'value' => true, 'type' => 'boolean']);
        expect($scopes[1])->toMatchArray(['name' => 'NotPublished', 'value' => false, 'type' => 'boolean']);
    });

    test('generates enum scope with parameterized value', function () {
        $columns = [['name' => 'status', 'type' => 'enum', 'cast' => 'string', 'nullable' => false]];
        $scopes = MigrationParser::inferScopes($columns);

        expect($scopes)->toHaveCount(1);
        expect($scopes[0])->toMatchArray(['name' => 'WhereStatus', 'type' => 'enum']);
    });

    test('returns no scopes for non-boolean non-enum columns', function () {
        $columns = [
            ['name' => 'title', 'type' => 'string', 'cast' => 'string', 'nullable' => false],
            ['name' => 'views', 'type' => 'integer', 'cast' => 'int', 'nullable' => false],
        ];

        expect(MigrationParser::inferScopes($columns))->toBeEmpty();
    });

    test('builds PHPDoc properties with correct types', function () {
        $columns = [
            ['name' => 'title', 'type' => 'string', 'cast' => 'string', 'nullable' => false],
            ['name' => 'views', 'type' => 'integer', 'cast' => 'int', 'nullable' => true],
        ];

        $properties = MigrationParser::inferProperties($columns, true, false);

        expect($properties[0])->toMatchArray(['name' => 'id', 'type' => 'int', 'nullable' => false]);
        expect($properties[1])->toMatchArray(['name' => 'title', 'type' => 'string', 'nullable' => false]);
        expect($properties[2])->toMatchArray(['name' => 'views', 'type' => 'int', 'nullable' => true]);
        expect($properties[3])->toMatchArray(['name' => 'created_at', 'type' => 'DateTimeHelper']);
        expect($properties[4])->toMatchArray(['name' => 'updated_at', 'type' => 'DateTimeHelper', 'nullable' => true]);
    });

    test('includes deleted_at when soft deletes enabled', function () {
        $properties = MigrationParser::inferProperties([], false, true);
        $names = array_column($properties, 'name');

        expect($names)->toContain('deleted_at');
    });

    test('excludes deleted_at when soft deletes disabled', function () {
        $properties = MigrationParser::inferProperties([], false, false);
        $names = array_column($properties, 'name');

        expect($names)->not->toContain('deleted_at');
    });
})->covers(MigrationParser::class);

describe('SmartModelBuilder', function () {

    test('builds complete replacement map from parsed migration', function () {
        $parsed = [
            'tableName' => 'post',
            'columns' => [
                ['name' => 'title', 'type' => 'string', 'cast' => 'string', 'nullable' => false, 'default' => null],
                ['name' => 'is_published', 'type' => 'boolean', 'cast' => 'boolean', 'nullable' => false, 'default' => false],
                ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'cast' => 'int', 'nullable' => false, 'default' => null],
            ],
            'foreignKeys' => [['column' => 'user_id', 'references' => 'id', 'on' => 'users']],
            'hasTimestamps' => true,
            'hasSoftDeletes' => false,
        ];

        $builder = new SmartModelBuilder($parsed);
        $replacements = $builder->buildReplacements('App', 'Post', 'post');

        expect($replacements['{namespace}'])->toBe('App');
        expect($replacements['{modelname}'])->toBe('Post');
        expect($replacements['{inferredTableName}'])->toBe('post');
        expect($replacements['{fillable}'])->toContain("'title'");
        expect($replacements['{fillable}'])->toContain("'user_id'");
        expect($replacements['{casts}'])->toContain("'is_published' => 'boolean'");
        expect($replacements['{casts}'])->toContain("'created_at' => 'datetime'");
        expect($replacements['{relationships}'])->toContain('belongsTo');
        expect($replacements['{relationships}'])->toContain('User::class');
        expect($replacements['{scopes}'])->toContain('scopePublished');
        expect($replacements['{scopes}'])->toContain('scopeNotPublished');
        expect($replacements['{traits}'])->toBe('');
        expect($replacements['{softDeletesProperty}'])->toBe('');
    });

    test('includes SoftDeletes trait and property when enabled', function () {
        $parsed = [
            'tableName' => 'post',
            'columns' => [],
            'foreignKeys' => [],
            'hasTimestamps' => false,
            'hasSoftDeletes' => true,
        ];

        $builder = new SmartModelBuilder($parsed);
        $replacements = $builder->buildReplacements('App', 'Post', 'post');

        expect($replacements['{traits}'])->toContain('use SoftDeletes;');
        expect($replacements['{traitImports}'])->toContain("use Database\\Traits\\SoftDeletes;");
        expect($replacements['{softDeletesProperty}'])->toContain('$softDeletes = true');
    });

    test('includes hidden array for sensitive columns', function () {
        $parsed = [
            'tableName' => 'user',
            'columns' => [
                ['name' => 'password', 'type' => 'string', 'cast' => 'string', 'nullable' => false, 'default' => null],
                ['name' => 'reset_token', 'type' => 'string', 'cast' => 'string', 'nullable' => true, 'default' => null],
            ],
            'foreignKeys' => [],
            'hasTimestamps' => false,
            'hasSoftDeletes' => false,
        ];

        $builder = new SmartModelBuilder($parsed);
        $replacements = $builder->buildReplacements('App', 'User', 'user');

        expect($replacements['{hidden}'])->toContain("'password'");
        expect($replacements['{hidden}'])->toContain("'reset_token'");
    });

    test('comments out unconfirmed relationships', function () {
        $parsed = [
            'tableName' => 'post',
            'columns' => [
                ['name' => 'category_id', 'type' => 'unsignedBigInteger', 'cast' => 'int', 'nullable' => false, 'default' => null],
            ],
            'foreignKeys' => [],
            'hasTimestamps' => false,
            'hasSoftDeletes' => false,
        ];

        $builder = new SmartModelBuilder($parsed);
        $replacements = $builder->buildReplacements('App', 'Post', 'post');

        expect($replacements['{relationships}'])->toContain('// public function category');
    });

    test('produces syntactically valid PHP from template', function () {
        $templatePath = Paths::cliPath('Build/Templates/SmartModelTemplate.php.stub');

        if (! FileSystem::exists($templatePath)) {
            test()->markTestSkipped('SmartModelTemplate.php.stub not found');
        }

        $parsed = [
            'tableName' => 'article',
            'columns' => [
                ['name' => 'title', 'type' => 'string', 'cast' => 'string', 'nullable' => false, 'default' => null],
                ['name' => 'body', 'type' => 'text', 'cast' => 'string', 'nullable' => false, 'default' => null],
                ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'cast' => 'int', 'nullable' => false, 'default' => null],
                ['name' => 'is_featured', 'type' => 'boolean', 'cast' => 'boolean', 'nullable' => false, 'default' => false],
                ['name' => 'published_at', 'type' => 'dateTime', 'cast' => 'datetime', 'nullable' => true, 'default' => null],
            ],
            'foreignKeys' => [['column' => 'user_id', 'references' => 'id', 'on' => 'users']],
            'hasTimestamps' => true,
            'hasSoftDeletes' => true,
        ];

        $builder = new SmartModelBuilder($parsed);
        $replacements = $builder->buildReplacements('App', 'Article', 'article');

        $template = FileSystem::get($templatePath);
        $output = str_replace(array_keys($replacements), array_values($replacements), $template);

        expect($output)->toContain('class Article extends BaseModel');
        expect($output)->toContain("protected string \$table = 'article'");
        expect($output)->toContain("'title'");
        expect($output)->toContain("'is_featured' => 'boolean'");
        expect($output)->toContain('use SoftDeletes;');
        expect($output)->toContain('function user()');
        expect($output)->toContain('scopeFeatured');
        expect($output)->toContain('@property int');
        expect($output)->toContain('@property string');
        expect($output)->toContain('@property ?DateTimeHelper');

        $tokens = token_get_all($output);
        $hasBadChar = false;

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_BAD_CHARACTER) {
                $hasBadChar = true;

                break;
            }
        }

        expect($hasBadChar)->toBeFalse();
    });
})->covers(SmartModelBuilder::class);
