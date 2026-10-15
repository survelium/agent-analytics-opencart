<?php

use Survelium\AgentAnalytics\AgentAnalytics;
use Survelium\AgentAnalytics\Config;
use Survelium\AgentAnalytics\Hit;
use Survelium\AgentAnalytics\Storage\FileStorage;
use Survelium\AgentAnalytics\Transport\FakeTransport;
use Survelium\AgentAnalyticsOpenCart\Bootstrap;

beforeEach(function () {
    $this->directory = temporaryDirectory();
});

afterEach(function () {
    removeDirectory($this->directory);
});

it('stays off until config.php holds an ingest key', function (mixed $settings) {
    expect(Bootstrap::config($settings, $this->directory))->toBeNull();
})->with([
    'no config.php' => [null],
    'not an array' => [1],
    'empty key' => [['api_key' => '']],
    'not an ingest key' => [['api_key' => 'paste-here']],
]);

it('keeps the admin out and the buffer next to the drop-in by default', function () {
    $config = Bootstrap::config(['api_key' => ' svk_abc '], $this->directory);

    expect($config->apiKey)->toBe('svk_abc')
        ->and($config->endpoint)->toBe(Config::DEFAULT_ENDPOINT)
        ->and($config->denyPaths)->toBe(['/admin'])
        ->and($config->trackReferrals)->toBeTrue()
        ->and($config->ipHeader)->toBeNull()
        ->and($config->countAllHits)->toBeTrue()
        ->and($config->storagePath())->toBe($this->directory.DIRECTORY_SEPARATOR.'storage');
});

it('takes the settings from config.php', function () {
    $config = Bootstrap::config([
        'api_key' => 'svk_abc',
        'endpoint' => 'https://survelium.test',
        'deny_paths' => ['/shop-admin', 7],
        'ip_header' => 'HTTP_CF_CONNECTING_IP',
        'track_referrals' => false,
        'storage_path' => $this->directory.'/elsewhere',
    ], $this->directory);

    expect($config->endpoint)->toBe('https://survelium.test')
        ->and($config->denyPaths)->toBe(['/shop-admin'])
        ->and($config->ipHeader)->toBe('HTTP_CF_CONNECTING_IP')
        ->and($config->trackReferrals)->toBeFalse()
        ->and($config->storagePath())->toBe($this->directory.'/elsewhere');
});

it('records a crawler on a shop page but nothing under the admin', function () {
    $config = Bootstrap::config(['api_key' => 'svk_abc'], $this->directory);
    $analytics = AgentAnalytics::make($config, new FileStorage($config->storagePath()), new FakeTransport);

    $analytics->record(new Hit('/index.php?route=product/product&product_id=42', GPTBOT_UA, null, '20.171.206.10', 200));
    $analytics->record(new Hit('/admin/index.php?route=common/dashboard', GPTBOT_UA));

    expect($analytics->storage->stats()['count'])->toBe(1)
        ->and(array_values($analytics->storage->pending(10))[0]['path'])->toBe('/index.php?route=product/product&product_id=42');
});

it('loads as a prepend file without doing anything from the command line', function () {
    expect(fn () => require dirname(__DIR__).'/survelium.php')->not->toThrow(Throwable::class);
});

it('ships a config example that turns nothing on until edited', function () {
    $settings = require dirname(__DIR__).'/config.example.php';

    expect($settings)->toHaveKeys(['api_key', 'deny_paths', 'ip_header', 'track_referrals', 'storage_path'])
        ->and(Bootstrap::config($settings, $this->directory))->toBeNull();
});
