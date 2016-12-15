<?php
// //////////////////////////////////////////////////////
// CLI test application for Google TTS API
// //////////////////////////////////////////////////////
require_once 'GoogleTTS.php';

$lang = 'en';

if (count($argv) < 2 || in_array($argv[1], array(
    '-h',
    '-help',
    '--help'
))) {
    printf('Syntax: php -f %s %s [%s]' . PHP_EOL, $argv[0], 'your-text', 'lang');
    printf('where [lang] is an optional ISO 639-1 language code (default `%s`)' . PHP_EOL, $lang);
    exit(1);
}
$texts = tokenizeText($argv[1]);

count($argv) > 2 && $lang = $argv[2];

foreach ($texts as $text)
    echo tts($text, $lang);
?>