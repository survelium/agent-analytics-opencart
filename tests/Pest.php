<?php

const GPTBOT_UA = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.2; +https://openai.com/gptbot';

function temporaryDirectory(): string
{
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'survelium-opencart-test-'.bin2hex(random_bytes(6));
    mkdir($path);

    return $path;
}

function removeDirectory(string $path): void
{
    foreach (glob($path.DIRECTORY_SEPARATOR.'{,.}[!.,!..]*', GLOB_BRACE) ?: [] as $file) {
        is_dir($file) ? removeDirectory($file) : unlink($file);
    }

    @rmdir($path);
}
