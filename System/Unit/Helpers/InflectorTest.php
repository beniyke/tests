<?php

declare(strict_types=1);

use Helpers\String\Inflector;

describe('Inflector', function () {
    beforeEach(function () {
        Inflector::reset();
    });

    test('pluralize returns plural form of words', function () {
        expect(Inflector::pluralize('cat'))->toBe('cats');
        expect(Inflector::pluralize('dog'))->toBe('dogs');
        expect(Inflector::pluralize('bus'))->toBe('buses');
        expect(Inflector::pluralize('box'))->toBe('boxes');
        expect(Inflector::pluralize('buzz'))->toBe('buzzes');
        expect(Inflector::pluralize('wish'))->toBe('wishes');
        expect(Inflector::pluralize('church'))->toBe('churches');
        expect(Inflector::pluralize('baby'))->toBe('babies');
        expect(Inflector::pluralize('day'))->toBe('days');
        expect(Inflector::pluralize('knife'))->toBe('knives');
        expect(Inflector::pluralize('leaf'))->toBe('leaves');
        expect(Inflector::pluralize('analysis'))->toBe('analyses');
        expect(Inflector::pluralize('criterion'))->toBe('criteria');
        expect(Inflector::pluralize('person'))->toBe('people');
        expect(Inflector::pluralize('man'))->toBe('men');
        expect(Inflector::pluralize('child'))->toBe('children');
        expect(Inflector::pluralize('mouse'))->toBe('mice');
        expect(Inflector::pluralize('ox'))->toBe('oxen');
        expect(Inflector::pluralize('quiz'))->toBe('quizzes');
    });

    test('singularize returns singular form of words', function () {
        expect(Inflector::singularize('cats'))->toBe('cat');
        expect(Inflector::singularize('dogs'))->toBe('dog');
        expect(Inflector::singularize('buses'))->toBe('bus');
        expect(Inflector::singularize('boxes'))->toBe('box');
        expect(Inflector::singularize('buzzes'))->toBe('buzz');
        expect(Inflector::singularize('wishes'))->toBe('wish');
        expect(Inflector::singularize('churches'))->toBe('church');
        expect(Inflector::singularize('babies'))->toBe('baby');
        expect(Inflector::singularize('days'))->toBe('day');
        expect(Inflector::singularize('knives'))->toBe('knife');
        expect(Inflector::singularize('leaves'))->toBe('leaf');
        expect(Inflector::singularize('analyses'))->toBe('analysis');
        expect(Inflector::singularize('criteria'))->toBe('criterion');
        expect(Inflector::singularize('people'))->toBe('person');
        expect(Inflector::singularize('men'))->toBe('man');
        expect(Inflector::singularize('children'))->toBe('child');
        expect(Inflector::singularize('mice'))->toBe('mouse');
        expect(Inflector::singularize('oxen'))->toBe('ox');
        expect(Inflector::singularize('quizzes'))->toBe('quiz');
    });

    test('pluralize handles irregular words', function () {
        expect(Inflector::pluralize('atlas'))->toBe('atlases');
        expect(Inflector::pluralize('beef'))->toBe('beefs');
        expect(Inflector::pluralize('brother'))->toBe('brothers');
        expect(Inflector::pluralize('cafe'))->toBe('cafes');
        expect(Inflector::pluralize('child'))->toBe('children');
        expect(Inflector::pluralize('cookie'))->toBe('cookies');
        expect(Inflector::pluralize('corpus'))->toBe('corpuses');
        expect(Inflector::pluralize('cow'))->toBe('cows');
        expect(Inflector::pluralize('ganglion'))->toBe('ganglions');
        expect(Inflector::pluralize('genie'))->toBe('genies');
        expect(Inflector::pluralize('genus'))->toBe('genera');
        expect(Inflector::pluralize('graffito'))->toBe('graffiti');
        expect(Inflector::pluralize('hoof'))->toBe('hoofs');
        expect(Inflector::pluralize('loaf'))->toBe('loaves');
        expect(Inflector::pluralize('man'))->toBe('men');
        expect(Inflector::pluralize('money'))->toBe('monies');
        expect(Inflector::pluralize('mongoose'))->toBe('mongooses');
        expect(Inflector::pluralize('move'))->toBe('moves');
        expect(Inflector::pluralize('mythos'))->toBe('mythoi');
        expect(Inflector::pluralize('niche'))->toBe('niches');
        expect(Inflector::pluralize('numen'))->toBe('numina');
        expect(Inflector::pluralize('occiput'))->toBe('occiputs');
        expect(Inflector::pluralize('octopus'))->toBe('octopuses');
        expect(Inflector::pluralize('opus'))->toBe('opuses');
        expect(Inflector::pluralize('ox'))->toBe('oxen');
        expect(Inflector::pluralize('penis'))->toBe('penises');
        expect(Inflector::pluralize('person'))->toBe('people');
        expect(Inflector::pluralize('sex'))->toBe('sexes');
        expect(Inflector::pluralize('soliloquy'))->toBe('soliloquies');
        expect(Inflector::pluralize('testis'))->toBe('testes');
        expect(Inflector::pluralize('trilby'))->toBe('trilbys');
        expect(Inflector::pluralize('turf'))->toBe('turfs');
        expect(Inflector::pluralize('potato'))->toBe('potatoes');
        expect(Inflector::pluralize('hero'))->toBe('heroes');
        expect(Inflector::pluralize('tooth'))->toBe('teeth');
        expect(Inflector::pluralize('goose'))->toBe('geese');
        expect(Inflector::pluralize('foot'))->toBe('feet');
        expect(Inflector::pluralize('foe'))->toBe('foes');
        expect(Inflector::pluralize('sieve'))->toBe('sieves');
    });

    test('singularize handles irregular words', function () {
        expect(Inflector::singularize('atlases'))->toBe('atlas');
        expect(Inflector::singularize('beefs'))->toBe('beef');
        expect(Inflector::singularize('brothers'))->toBe('brother');
        expect(Inflector::singularize('cafes'))->toBe('cafe');
        expect(Inflector::singularize('children'))->toBe('child');
        expect(Inflector::singularize('cookies'))->toBe('cookie');
        expect(Inflector::singularize('corpuses'))->toBe('corpus');
        expect(Inflector::singularize('cows'))->toBe('cow');
        expect(Inflector::singularize('ganglions'))->toBe('ganglion');
        expect(Inflector::singularize('genies'))->toBe('genie');
        expect(Inflector::singularize('genera'))->toBe('genus');
        expect(Inflector::singularize('graffiti'))->toBe('graffito');
        expect(Inflector::singularize('hoofs'))->toBe('hoof');
        expect(Inflector::singularize('loaves'))->toBe('loaf');
        expect(Inflector::singularize('men'))->toBe('man');
        expect(Inflector::singularize('monies'))->toBe('money');
        expect(Inflector::singularize('mongooses'))->toBe('mongoose');
        expect(Inflector::singularize('moves'))->toBe('move');
        expect(Inflector::singularize('mythoi'))->toBe('mythos');
        expect(Inflector::singularize('niches'))->toBe('niche');
        expect(Inflector::singularize('numina'))->toBe('numen');
        expect(Inflector::singularize('occiputs'))->toBe('occiput');
        expect(Inflector::singularize('octopuses'))->toBe('octopus');
        expect(Inflector::singularize('opuses'))->toBe('opus');
        expect(Inflector::singularize('oxen'))->toBe('ox');
        expect(Inflector::singularize('penises'))->toBe('penis');
        expect(Inflector::singularize('people'))->toBe('person');
        expect(Inflector::singularize('sexes'))->toBe('sex');
        expect(Inflector::singularize('soliloquies'))->toBe('soliloquy');
        expect(Inflector::singularize('testes'))->toBe('testis');
        expect(Inflector::singularize('trilbys'))->toBe('trilby');
        expect(Inflector::singularize('turfs'))->toBe('turf');
        expect(Inflector::singularize('potatoes'))->toBe('potato');
        expect(Inflector::singularize('heroes'))->toBe('hero');
        expect(Inflector::singularize('teeth'))->toBe('tooth');
        expect(Inflector::singularize('geese'))->toBe('goose');
        expect(Inflector::singularize('feet'))->toBe('foot');
        expect(Inflector::singularize('foes'))->toBe('foe');
        expect(Inflector::singularize('sieves'))->toBe('sieve');
    });

    test('pluralize handles uninflected words', function () {
        expect(Inflector::pluralize('fish'))->toBe('fish');
        expect(Inflector::pluralize('sheep'))->toBe('sheep');
        expect(Inflector::pluralize('deer'))->toBe('deer');
        expect(Inflector::pluralize('series'))->toBe('series');
        expect(Inflector::pluralize('species'))->toBe('species');
        expect(Inflector::pluralize('money'))->toBe('monies'); // Wait, money is irregular in the class
        expect(Inflector::pluralize('rice'))->toBe('rice'); // Not in uninflected list explicitly but might be covered
        expect(Inflector::pluralize('information'))->toBe('information');
        expect(Inflector::pluralize('equipment'))->toBe('equipment');
    });

    test('singularize handles uninflected words', function () {
        expect(Inflector::singularize('fish'))->toBe('fish');
        expect(Inflector::singularize('sheep'))->toBe('sheep');
        expect(Inflector::singularize('deer'))->toBe('deer');
        expect(Inflector::singularize('series'))->toBe('series');
        expect(Inflector::singularize('species'))->toBe('species');
        expect(Inflector::singularize('information'))->toBe('information');
        expect(Inflector::singularize('equipment'))->toBe('equipment');
    });

    test('rules adds custom rules', function () {
        Inflector::rules('plural', ['/^(inflect)or$/i' => '\1ables']);
        expect(Inflector::pluralize('inflector'))->toBe('inflectables');

        Inflector::rules('irregular', ['red' => 'redlings']);
        expect(Inflector::pluralize('red'))->toBe('redlings');
        expect(Inflector::singularize('redlings'))->toBe('red');

        Inflector::rules('uninflected', ['dontinflectme']);
        expect(Inflector::pluralize('dontinflectme'))->toBe('dontinflectme');
        expect(Inflector::singularize('dontinflectme'))->toBe('dontinflectme');
    });
});
