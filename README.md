# Survelium agent analytics for OpenCart

See which AI crawlers read your shop (GPTBot, ClaudeBot, PerplexityBot, OAI-SearchBot…), which pages they fetch,
and how many people arrive from AI assistants — in [Survelium](https://survelium.com) → AI traffic.

A drop-in folder, no OpenCart extension: PHP loads it before OpenCart on every request through `auto_prepend_file`,
so it works the same on OpenCart 2, 3 and 4 and survives theme and extension changes. It is built on
[survelium/agent-analytics-php](https://github.com/survelium/agent-analytics-php).

## What leaves your server

| | Sent | Never sent |
|---|---|---|
| AI crawler hit | time, user agent, path, HTTP status, IP (to verify it is really that crawler) | — |
| Visitor from an AI assistant | time, assistant domain (e.g. `chatgpt.com`), path, user agent | IP, full referrer, cookies |
| Every page view by a person | a daily count, used for "share of traffic from AI" (other bots are not counted) | anything about the request |

Only page views count (`GET`/`HEAD`, not the cart's AJAX calls or form posts), and nothing under `/admin`.
Sending happens after the page has gone out, in batches; a visitor never waits on it.

## Requirements

- PHP 8.1 or newer for the shop. On older PHP the file loads and does nothing.
- The PHP `curl` extension, or `allow_url_fopen`.

## Install

1. **Get a key.** In Survelium: AI traffic → Integration → Create key. It starts with `svk_` and is shown once.
2. **Upload the folder.** Download `survelium-agent-analytics-opencart-x.y.z.zip` from
   [Releases](https://github.com/survelium/agent-analytics-opencart/releases) and extract it next to OpenCart's
   `index.php` (usually `public_html`), so you get `public_html/survelium/survelium.php`.
3. **Configure.** Copy `survelium/config.example.php` to `survelium/config.php` and paste the key into `api_key`.
   - Admin folder renamed? Put its path in `deny_paths`, e.g. `['/my-admin']`.
   - Behind Cloudflare? Set `'ip_header' => 'HTTP_CF_CONNECTING_IP'`, or crawler IPs cannot be verified.
4. **Load it before OpenCart**, with the absolute path to `survelium.php`. One of:
   - **`.user.ini`** next to `index.php` (PHP-FPM, most cPanel and LiteSpeed hosts; cPanel → MultiPHP INI Editor
     writes the same thing):
     ```ini
     auto_prepend_file = "/home/USER/public_html/survelium/survelium.php"
     ```
     PHP re-reads `.user.ini` every few minutes (`user_ini.cache_ttl`, 300 s by default).
   - **`.htaccess`** (Apache with mod_php, LiteSpeed). OpenCart ships it as `.htaccess.txt`; if you never
     renamed it, create `.htaccess` with just this:
     ```apache
     php_value auto_prepend_file "/home/USER/public_html/survelium/survelium.php"
     ```
     If the shop answers *500 Internal Server Error* right after, your host runs PHP-FPM: remove the line and use
     `.user.ini` instead.

   Not sure of the absolute path? It is the folder shown in your hosting file manager, or `pwd` over SSH.
5. **Check.** Open a few shop pages. Within about 15 minutes Survelium → AI traffic → Integration shows the key
   as used. With SSH:
   ```bash
   php survelium/status.php          # what is buffered, last send, last error
   php survelium/status.php --send   # send now
   ```

The `survelium` folder ships an `.htaccess` that blocks web access to it (your key, the buffer). On nginx, point
`storage_path` outside the web root, or deny `/survelium/` in the server block.

## Update

Extract the new zip over the old folder. `config.php` is not in the zip, so your settings stay.

## Uninstall

Remove the `auto_prepend_file` line first, then delete the `survelium` folder. The other way around, PHP fails
on every request until the line is gone.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). See [License File](LICENSE.md).
