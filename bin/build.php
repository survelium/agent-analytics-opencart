<?php

/*
 * Builds the installable zip: the committed files (minus export-ignore) plus a
 * production vendor folder, all inside a "survelium" folder that goes next to
 * OpenCart's index.php. Usage: php bin/build.php
 */

$root = dirname(__DIR__);
$slug = 'survelium';

preg_match("/const VERSION = '([^']+)'/", (string) file_get_contents("{$root}/src/Bootstrap.php"), $match);
$version = $match[1] ?? exit("VERSION not found in src/Bootstrap.php.\n");

$build = "{$root}/build";
$staging = "{$build}/{$slug}";
$zipPath = "{$build}/survelium-agent-analytics-opencart-{$version}.zip";

function run(string $command, string $cwd): void
{
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $cwd);

    if (! is_resource($process) || proc_close($process) !== 0) {
        exit("Failed: {$command}\n");
    }
}

function removeTree(string $path): void
{
    if (! file_exists($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($path);
}

removeTree($build);
mkdir($staging, 0777, true);

run('git archive --format=zip -o '.escapeshellarg("{$build}/source.zip").' HEAD', $root);

$source = new ZipArchive;
($source->open("{$build}/source.zip") === true && $source->extractTo($staging)) || exit("Cannot extract the git archive\n");
$source->close();
unlink("{$build}/source.zip");

run('composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --quiet', $staging);

foreach (['composer.json', 'composer.lock'] as $file) {
    @unlink("{$staging}/{$file}");
}

$zip = new ZipArchive;
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) || exit("Cannot write {$zipPath}\n");

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($staging, FilesystemIterator::SKIP_DOTS));

foreach ($files as $file) {
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($staging) + 1));
    $zip->addFile($file->getPathname(), "{$slug}/{$relative}");
}

$zip->close();

echo "Built {$zipPath}\n";
