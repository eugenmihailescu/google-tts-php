<?php
require_once 'GoogleHashToken.php';

/**
 * Return the MP3 audio for a given text
 *
 * @param string $text
 *            The input text to convert to audio
 * @param string $lang
 *            The ISO 639-1 language code (default `en`) that affects the pronounciation
 * @return string Returns the MP3 content as string
 */
function tts($text, $lang = 'en')
{
    $epoch = strval(time() / 3600);
    
    $tk = calcHash($text, $epoch);
    
    $query = http_build_query(array(
        'client' => 'tw-ob',
        'idx' => 0,
        'ie' => 'UTF-8',
        'prev' => 'input',
        'q' => $text,
        'textlen' => strlen($text),
        'tk' => $tk,
        'tl' => $lang,
        'total' => 1
    ));
    
    $url = 'http://translate.google.com/translate_tts?' . $query;
    
    $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
    $agent = 'stagefright/1.2 (Linux;Android 5.0)';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_REFERER, 'http://translate.google.com/');
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $media = curl_exec($ch);
    curl_close($ch);
    
    return $media;
}

/**
 * Returns the last separator in the string
 *
 * @param string $string
 *            The input string
 * @param string $separators
 *            A list of valid separators
 * @return boolean|number Return the last position of a separator, false if none found
 */
function findLastSeparator($string, $separators = '.,;!()[]{}-|–')
{
    $separators = '.,;!()[]{}-|–';
    
    $pos = false;
    
    for ($i = mb_strlen($string); $i > 0; $i --) {
        if (false !== mb_strpos($separators, mb_substr($string, $i - 1, 1)))
            $pos = max($pos, $i - 1);
    }
    
    return $pos;
}

/**
 * Tokenizes a string where it would naturally pause.
 *
 * @param string $text
 *            The long text to split
 * @param number $max_len
 *            The largest length of a sentence (a sentence should have no more than 15-20 words, ie. 25-33 syllables and 75-100 characters)
 * @return array
 */
function tokenizeText($text, $max_len = 200)
{
    $text = trim($text);
    
    $result = array();
    
    if (mb_strlen($text) <= $max_len) {
        $result[] = $text;
    } else
        while (mb_strlen($text) > 0) {
            
            $candidate = mb_substr($text, 0, $max_len);
            
            $found = findLastSeparator($candidate);
            
            if (! $found) {
                $found = $max_len;
            }
            
            $result[] = mb_substr($candidate, 0, $found);
            $text = trim(mb_substr($text, 1 + $found));
        }
    
    return $result;
}
?>