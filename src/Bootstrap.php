<?php

namespace Survelium\AgentAnalyticsOpenCart;

use Survelium\AgentAnalytics\AgentAnalytics;
use Survelium\AgentAnalytics\Config;

final class Bootstrap
{
    public const VERSION = '0.1.0';

    /**
     * @param  mixed  $settings  What config.php returns.
     */
    public static function start(mixed $settings, string $directory): ?AgentAnalytics
    {
        $config = self::config($settings, $directory);

        return $config === null ? null : AgentAnalytics::start($config);
    }

    /**
     * Null until config.php holds an ingest key.
     *
     * @param  mixed  $settings  What config.php returns.
     */
    public static function config(mixed $settings, string $directory): ?Config
    {
        if (! is_array($settings) || ! is_string($settings['api_key'] ?? null) || ! str_starts_with(trim($settings['api_key']), 'svk_')) {
            return null;
        }

        $denyPaths = $settings['deny_paths'] ?? ['/admin'];

        return new Config(
            apiKey: trim($settings['api_key']),
            endpoint: is_string($settings['endpoint'] ?? null) && $settings['endpoint'] !== '' ? $settings['endpoint'] : Config::DEFAULT_ENDPOINT,
            trackReferrals: (bool) ($settings['track_referrals'] ?? true),
            denyPaths: is_array($denyPaths) ? array_values(array_filter($denyPaths, 'is_string')) : ['/admin'],
            ipHeader: is_string($settings['ip_header'] ?? null) && $settings['ip_header'] !== '' ? $settings['ip_header'] : null,
            storagePath: is_string($settings['storage_path'] ?? null) && $settings['storage_path'] !== ''
                ? $settings['storage_path']
                : rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'storage',
        );
    }
}
