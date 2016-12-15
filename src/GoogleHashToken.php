<?php
// //////////////////////////////////////////////////////////////////////////////////
// Google Translate TTS Wrapper - produces a MP3 audio for a given text
// Author : Eugen Mihailescu
// Date : 2016-12-15
//
// See: http://stackoverflow.com/questions/32053442/google-translate-tts-api-blocked
// //////////////////////////////////////////////////////////////////////////////////

/**
 * Zero-fill right shift
 *
 * @param int $a
 *            The operand
 * @param int $n
 *            The number of bits to shift to right
 * @return int
 *
 * @see https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Operators/Bitwise_Operators#%3E%3E%3E_(Zero-fill_right_shift)
 */
function zrsh($a, $n)
{
    if ($n <= 0)
        return $a;
    
    $b = 0x80000000;
    
    return ($a >> $n) & ~ ($b >> ($n - 1));
}

/**
 *
 * @param int $num
 *            The number to shift
 * @param array $opArray
 *            An array of operations to use for shifting
 * @return int
 */
function shiftLeftOrRightThenSumOrXor($num, $opArray)
{
    return array_reduce($opArray, function ($acc, $opString) {
        $op1 = $opString[1]; // '+' | '-' ~ SUM | XOR
        $op2 = $opString[0]; // '+' | '^' ~ SLL | SRL
        $xd = $opString[2]; // [0-9a-f]
        
        $shiftAmount = hexdec($xd);
        $mask = ($op1 == '+') ? zrsh($acc, $shiftAmount) : $acc << $shiftAmount;
        return ($op2 == '+') ? ($acc + $mask & 0xffffffff) : ($acc ^ $mask);
    }, $num);
}

/**
 * Converts a multibyte string to array of UTF chars
 *
 * @param string $string
 *            The multibyte string to convert
 * @return array
 */
function mb_str_to_array($string)
{
    mb_internal_encoding("UTF-8"); // Important
    
    $chars = array();
    for ($i = 0; $i < mb_strlen($string); $i ++) {
        $chars[] = mb_substr($string, $i, 1);
    }
    return $chars;
}

function normalizeHash($encondindRound2)
{
    if ($encondindRound2 < 0) {
        $encondindRound2 = ($encondindRound2 & 0x7fffffff) + 0x80000000;
    }
    
    return $encondindRound2 % 1e6;
}

/**
 * Calculates the Google TTS hash for a given string
 *
 * @param string $query
 *            The input string
 * @param float $windowTkk
 *            The epoch as float
 * @return string Returns the hash string (xxxx.yyyy)
 */
function calcHash($query, $windowTkk)
{
    // STEP 1: spread the the query char codes on a byte-array, 1-3 bytes per char
    $bytesArray = mb_str_to_array($query);
    
    foreach ($bytesArray as $fff => $ccc)
        printf('[%s] => %s : %s' . PHP_EOL, $fff, $ccc, chr($ccc));
        
        // STEP 2: starting with TKK index, add the array from last step one-by-one, and do 2 rounds of shift+add/xor
    $d = explode('.', $windowTkk);
    
    $tkkIndex = intval($d[0]);
    $tkkIndex = $tkkIndex ? $tkkIndex : 0;
    
    $tkkKey = intval($d[1]);
    $tkkKey = $tkkKey ? $tkkKey : 0;
    
    $encondingRound1 = array_reduce($bytesArray, function ($acc, $current) {
        $acc += $current;
        return shiftLeftOrRightThenSumOrXor($acc, [
            '+-a',
            '^+6'
        ]);
    }, $tkkIndex);
    
    // STEP 3: apply 3 rounds of shift+add/xor and XOR with they TKK key
    $encondingRound2 = shiftLeftOrRightThenSumOrXor($encondingRound1, [
        '+-3',
        '^+b',
        '+-f'
    ]) ^ $tkkKey;
    
    // STEP 4: Normalize to 2s complement & format
    $normalizedResult = normalizeHash($encondingRound2);
    
    return strval($normalizedResult) . "." . strval(($normalizedResult ^ $tkkIndex));
}
?>