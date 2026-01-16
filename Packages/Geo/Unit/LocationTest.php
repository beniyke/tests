<?php

declare(strict_types=1);

use Geo\Models\Location;

describe('Location DTO', function () {

    test('creates location from array', function () {
        $location = Location::fromArray([
            'latitude' => 37.4223,
            'longitude' => -122.0840,
            'city' => 'Mountain View',
            'state' => 'California',
            'country' => 'United States',
            'country_code' => 'US',
            'postal_code' => '94043',
            'formatted_address' => '1600 Amphitheatre Parkway, Mountain View, CA',
        ]);

        expect($location)->toBeInstanceOf(Location::class)
            ->and($location->latitude)->toBe(37.4223)
            ->and($location->longitude)->toBe(-122.0840)
            ->and($location->city)->toBe('Mountain View')
            ->and($location->country)->toBe('United States')
            ->and($location->countryCode)->toBe('US');
    });

    test('toArray returns all properties', function () {
        $location = Location::fromArray([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'city' => 'New York',
            'country' => 'United States',
        ]);

        $array = $location->toArray();

        expect($array)->toBeArray()
            ->and($array['latitude'])->toBe(40.7128)
            ->and($array['longitude'])->toBe(-74.0060)
            ->and($array['city'])->toBe('New York');
    });

    test('handles missing optional fields', function () {
        $location = Location::fromArray([
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);

        expect($location->latitude)->toBe(51.5074)
            ->and($location->city)->toBeNull()
            ->and($location->country)->toBeNull();
    });
});
