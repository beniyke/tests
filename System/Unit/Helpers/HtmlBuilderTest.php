<?php

declare(strict_types=1);

use Helpers\Html\HtmlBuilder;

beforeEach(function () {
    $this->html = new HtmlBuilder();
});

describe('HtmlBuilder', function () {

    describe('Basic Elements', function () {
        test('creates div element', function () {
            $result = $this->html->div('Hello World', ['class' => 'container'])->render();
            expect($result)->toBe('<div class="container">Hello World</div>');
        });

        test('creates span element', function () {
            $result = $this->html->span('Text', ['id' => 'my-span'])->render();
            expect($result)->toBe('<span id="my-span">Text</span>');
        });

        test('creates paragraph element', function () {
            $result = $this->html->paragraph('Content', ['class' => 'text'])->render();
            expect($result)->toBe('<p class="text">Content</p>');
        });

        test('creates link element', function () {
            $result = $this->html->link('Click here', '/page', ['class' => 'btn'])->render();
            expect($result)->toBe('<a class="btn" href="/page">Click here</a>');
        });
    });

    describe('Attribute Handling', function () {
        test('escapes attribute values', function () {
            $result = $this->html->div('Content', ['data-value' => '<script>alert("xss")</script>'])->render();
            expect($result)->toContain('data-value="&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;"');
        });

        test('handles boolean attributes', function () {
            $result = $this->html->input(['type' => 'checkbox', 'checked' => true])->render();
            expect($result)->toContain('checked');
            expect($result)->not->toContain('checked="');
        });

        test('skips false boolean attributes', function () {
            $result = $this->html->input(['type' => 'checkbox', 'checked' => false])->render();
            expect($result)->not->toContain('checked');
        });

        test('escapes attribute keys', function () {
            $result = $this->html->div('Content', ['data-<test>' => 'value'])->render();
            expect($result)->toContain('data-&lt;test&gt;');
        });
    });

    describe('Self-Closing Tags', function () {
        test('creates input element', function () {
            $result = $this->html->input(['type' => 'text', 'name' => 'username'])->render();
            expect($result)->toBe('<input type="text" name="username">');
        });

        test('creates image element', function () {
            $result = $this->html->image('/logo.png', 'Logo', ['class' => 'logo'])->render();
            expect($result)->toBe('<img class="logo" src="/logo.png" alt="Logo">');
        });

        test('creates meta element', function () {
            $result = $this->html->meta(['name' => 'description', 'content' => 'Test'])->render();
            expect($result)->toBe('<meta name="description" content="Test">');
        });

        test('creates link tag element', function () {
            $result = $this->html->linkTag(['rel' => 'stylesheet', 'href' => '/style.css'])->render();
            expect($result)->toBe('<link rel="stylesheet" href="/style.css">');
        });
    });

    describe('Form Elements', function () {
        test('creates form with startForm and closeForm', function () {
            $result = $this->html
                ->startForm(['method' => 'POST', 'action' => '/submit'])
                ->closeForm()
                ->render();
            expect($result)->toBe('<form method="POST" action="/submit"></form>');
        });

        test('creates textarea element', function () {
            $result = $this->html->textArea('Default text', ['name' => 'message', 'rows' => '5'])->render();
            expect($result)->toBe('<textarea name="message" rows="5">Default text</textarea>');
        });

        test('escapes textarea content', function () {
            $result = $this->html->textArea('<script>alert("xss")</script>', ['name' => 'content'])->render();
            expect($result)->toContain('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
        });

        test('creates button element', function () {
            $result = $this->html->button('Submit', ['type' => 'submit', 'class' => 'btn'])->render();
            expect($result)->toBe('<button type="submit" class="btn">Submit</button>');
        });

        test('escapes button content', function () {
            $result = $this->html->button('<b>Bold</b>', ['type' => 'button'])->render();
            expect($result)->toContain('&lt;b&gt;Bold&lt;/b&gt;');
        });

        test('creates label element', function () {
            $result = $this->html->label('Username', ['for' => 'username'])->render();
            expect($result)->toBe('<label for="username">Username</label>');
        });

        test('creates select element', function () {
            $options = $this->html->option('Option 1', ['value' => '1'])->render();
            $result = $this->html->select($options, ['name' => 'choice'])->render();
            expect($result)->toContain('<select name="choice">');
            expect($result)->toContain('</select>');
        });

        test('creates options with selected value', function () {
            $this->html->options([
                'data' => ['1' => 'One', '2' => 'Two', '3' => 'Three'],
                'selected' => ['2'],
            ]);
            $result = $this->html->render();
            expect($result)->toContain('<option value="1">One</option>');
            expect($result)->toContain('<option value="2" selected>Two</option>');
            expect($result)->toContain('<option value="3">Three</option>');
        });

        test('creates options with multiple selected values', function () {
            $this->html->options([
                'data' => ['1' => 'One', '2' => 'Two', '3' => 'Three'],
                'selected' => ['1', '3'],
            ]);
            $result = $this->html->render();
            expect($result)->toContain('<option value="1" selected>One</option>');
            expect($result)->toContain('<option value="2">Two</option>');
            expect($result)->toContain('<option value="3" selected>Three</option>');
        });

        test('creates optgroup element', function () {
            $result = $this->html->optgroup('Options content', ['label' => 'Group 1'])->render();
            expect($result)->toBe('<optgroup label="Group 1">Options content</optgroup>');
        });

        test('creates fieldset element', function () {
            $result = $this->html->fieldset('Content', ['class' => 'group'])->render();
            expect($result)->toBe('<fieldset class="group">Content</fieldset>');
        });

        test('creates legend element', function () {
            $result = $this->html->legend('Personal Information')->render();
            expect($result)->toBe('<legend>Personal Information</legend>');
        });
    });

    describe('Table Elements', function () {
        test('creates table with startTable and closeTable', function () {
            $result = $this->html
                ->startTable(['class' => 'data-table'])
                ->closeTable()
                ->render();
            expect($result)->toBe('<table class="data-table"></table>');
        });

        test('creates table row with startRow and closeRow', function () {
            $result = $this->html
                ->startRow(['class' => 'row'])
                ->closeRow()
                ->render();
            expect($result)->toBe('<tr class="row"></tr>');
        });

        test('creates header cell', function () {
            $result = $this->html->headerCell('Name', ['class' => 'header'])->render();
            expect($result)->toBe('<th class="header">Name</th>');
        });

        test('creates data cell', function () {
            $result = $this->html->dataCell('Value', ['class' => 'data'])->render();
            expect($result)->toBe('<td class="data">Value</td>');
        });

        test('creates complete table structure', function () {
            $result = $this->html
                ->startTable(['class' => 'table'])
                ->startRow()
                ->headerCell('Name')
                ->headerCell('Age')
                ->closeRow()
                ->startRow()
                ->dataCell('John')
                ->dataCell('30')
                ->closeRow()
                ->closeTable()
                ->render();

            expect($result)->toContain('<table class="table">');
            expect($result)->toContain('<tr>');
            expect($result)->toContain('<th>Name</th>');
            expect($result)->toContain('<th>Age</th>');
            expect($result)->toContain('</tr>');
            expect($result)->toContain('<td>John</td>');
            expect($result)->toContain('<td>30</td>');
            expect($result)->toContain('</table>');
        });
    });

    describe('Semantic HTML5 Elements', function () {
        test('creates section element', function () {
            $result = $this->html->section('Content', ['class' => 'main'])->render();
            expect($result)->toBe('<section class="main">Content</section>');
        });

        test('creates article element', function () {
            $result = $this->html->article('Article content', ['id' => 'post-1'])->render();
            expect($result)->toBe('<article id="post-1">Article content</article>');
        });

        test('creates header element', function () {
            $result = $this->html->header('Header content', ['class' => 'page-header'])->render();
            expect($result)->toBe('<header class="page-header">Header content</header>');
        });

        test('creates footer element', function () {
            $result = $this->html->footer('Footer content', ['class' => 'page-footer'])->render();
            expect($result)->toBe('<footer class="page-footer">Footer content</footer>');
        });

        test('creates nav element', function () {
            $result = $this->html->nav('Navigation', ['role' => 'navigation'])->render();
            expect($result)->toBe('<nav role="navigation">Navigation</nav>');
        });

        test('creates aside element', function () {
            $result = $this->html->aside('Sidebar content', ['class' => 'sidebar'])->render();
            expect($result)->toBe('<aside class="sidebar">Sidebar content</aside>');
        });
    });

    describe('Media Elements', function () {
        test('creates video element', function () {
            $result = $this->html->video('/video.mp4', 'video/mp4', ['controls' => true])->render();
            expect($result)->toBe('<video controls src="/video.mp4" type="video/mp4"></video>');
        });

        test('creates audio element', function () {
            $result = $this->html->audio('/audio.mp3', ['controls' => true])->render();
            expect($result)->toBe('<audio controls src="/audio.mp3"></audio>');
        });
    });

    describe('Script and Style Elements', function () {
        test('creates script element', function () {
            $result = $this->html->script('console.log("test");', ['type' => 'text/javascript'])->render();
            expect($result)->toBe('<script type="text/javascript">console.log("test");</script>');
        });

        test('creates style element', function () {
            $result = $this->html->style('.class { color: red; }')->render();
            expect($result)->toBe('<style>.class { color: red; }</style>');
        });
    });

    describe('Chaining and Rendering', function () {
        test('chains multiple elements', function () {
            $result = $this->html
                ->div('First', ['class' => 'first'])
                ->div('Second', ['class' => 'second'])
                ->div('Third', ['class' => 'third'])
                ->render();

            expect($result)->toBe(
                '<div class="first">First</div>'.
                    '<div class="second">Second</div>'.
                    '<div class="third">Third</div>'
            );
        });

        test('reset clears accumulated HTML', function () {
            $this->html->div('Content')->render();
            $this->html->reset();
            $result = $this->html->render();
            expect($result)->toBe('');
        });

        test('reset returns self for chaining', function () {
            $result = $this->html
                ->div('First')
                ->reset()
                ->div('Second')
                ->render();
            expect($result)->toBe('<div>Second</div>');
        });
    });

    describe('Raw HTML', function () {
        test('adds raw HTML', function () {
            $result = $this->html
                ->div('Before')
                ->addRawHtml('<custom>Raw HTML</custom>')
                ->div('After')
                ->render();

            expect($result)->toBe('<div>Before</div><custom>Raw HTML</custom><div>After</div>');
        });
    });

    describe('Generic Element Method', function () {
        test('creates custom element', function () {
            $result = $this->html->element('custom-tag', 'Content', ['data-id' => '123'])->render();
            expect($result)->toBe('<custom-tag data-id="123">Content</custom-tag>');
        });

        test('creates self-closing custom element', function () {
            $result = $this->html->element('custom-tag', '', ['data-id' => '123'], true)->render();
            expect($result)->toBe('<custom-tag data-id="123">');
        });
    });
});
