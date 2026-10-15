<?php

/*
 * Copy this file to config.php and paste your ingest key.
 * config.php is never part of an update, so your settings survive upgrades.
 */

return [
    // Survelium → AI traffic → Integration → Create key. Starts with "svk_".
    'api_key' => '',

    // Path prefixes never reported or counted. Change "/admin" if your admin folder was renamed.
    'deny_paths' => ['/admin'],

    // Behind Cloudflare, set 'HTTP_CF_CONNECTING_IP' so crawler IPs can be verified. Only when the proxy always sets it.
    'ip_header' => null,

    // Also report people who arrive from an AI assistant (chatgpt.com, perplexity.ai, …).
    'track_referrals' => true,

    // Buffer, counters and cached bot list. Defaults to the "storage" folder next to this file.
    'storage_path' => null,
];
