<?php

/*
 * php survelium/status.php          shows what is buffered and the last send
 * php survelium/status.php --send   sends now, ignoring any back-off
 */

use Survelium\AgentAnalytics\AgentAnalytics;
use Survelium\AgentAnalyticsOpenCart\Bootstrap;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (PHP_VERSION_ID < 80100) {
    fwrite(STDERR, 'Survelium needs PHP 8.1 or newer, this is PHP '.PHP_VERSION.".\n");
    exit(1);
}

require __DIR__.'/vendor/autoload.php';

$config = Bootstrap::config(is_file(__DIR__.'/config.php') ? require __DIR__.'/config.php' : null, __DIR__);

if ($config === null) {
    fwrite(STDERR, "Not configured: copy config.example.php to config.php and paste your ingest key.\n");
    exit(1);
}

$analytics = AgentAnalytics::make($config);

if (in_array('--send', $argv, true)) {
    $state = $analytics->storage->state();
    unset($state['next_attempt_at'], $state['paused_until'], $state['totals_sent_at']);
    $analytics->storage->putState($state);

    $code = $analytics->flush();
    $error = $analytics->storage->state()['last_error'] ?? null;

    echo match (true) {
        $code === null => "Nothing to send, or another send is running.\n",
        $code === 202 => "Sent. Survelium answered 202.\n",
        default => "Survelium answered {$code}".($error ? " ({$error})" : '').". Data is kept and retried.\n",
    };
}

$status = $analytics->status();
$state = $status['state'];
$time = fn (?int $timestamp): string => $timestamp ? date('Y-m-d H:i:s', $timestamp) : '-';

$rows = [
    'Ingest key' => substr($config->apiKey, 0, 12).'...',
    'Endpoint' => $config->endpoint,
    'Storage' => $config->storagePath(),
    'Buffered items' => (string) $status['buffered']['count'],
    'Last attempt' => $time($state['last_attempt_at'] ?? null).(isset($state['last_status']) ? " (HTTP {$state['last_status']})" : ''),
    'Last success' => $time($state['last_success_at'] ?? null),
    'Last error' => (string) ($state['last_error'] ?? '-'),
    'Bot list' => $status['bots'].' bots, version '.($status['signatures_version'] ? substr($status['signatures_version'], 0, 8) : 'bundled'),
    'Versions' => 'opencart '.Bootstrap::VERSION.', core '.$status['version'],
];

foreach ($rows as $label => $value) {
    echo str_pad($label, 16).$value."\n";
}
