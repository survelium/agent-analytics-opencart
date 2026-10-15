<?php

use Survelium\AgentAnalyticsOpenCart\Bootstrap;

/*
 * Survelium agent analytics for OpenCart (and any plain PHP site).
 *
 * Loaded before OpenCart through auto_prepend_file (see README.md). It must never
 * break the shop, so this file only uses syntax every PHP 5.x/7.x parser accepts,
 * and does nothing at all on PHP older than 8.1 or without a config.php.
 */

if (PHP_SAPI !== 'cli' && PHP_VERSION_ID >= 80100 && is_file(__DIR__.'/config.php')) {
    try {
        require_once __DIR__.'/vendor/autoload.php';

        Bootstrap::start(require __DIR__.'/config.php', __DIR__);
    } catch (Throwable $exception) {
        // Analytics must never break the shop.
    }
}
