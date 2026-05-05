<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model;

use Branch8\MaskInformation\Api\MaskSensitiveDataEInterface;

class MaskSensitiveData
{
    /**
     * @param $string
     * @param $index
     * @param $length
     * @param $maskCharacter
     * @param $encoding
     * @return mixed|string
     */
    public static function mask($string, $index, $length, $maskCharacter = '*', $encoding = 'UTF-8')
    {

        if ($maskCharacter === '') {
            return $string;
        }

        $segment = mb_substr($string, $index, $length, $encoding);

        if ($segment === '') {
            return $string;
        }

        $strlen = mb_strlen($string, $encoding);
        $startIndex = $index;

        if ($index < 0) {
            $startIndex = $index < -$strlen ? 0 : $strlen + $index;
        }

        $start = mb_substr($string, 0, $startIndex, $encoding);
        $segmentLen = mb_strlen($segment, $encoding);
        $end = mb_substr($string, $startIndex + $segmentLen);

        return $start . str_repeat(mb_substr($maskCharacter, 0, 1, $encoding), $segmentLen) . $end;
    }

    /**
     * @param $inputText
     * @return string
     */
    public static function filter($inputText)
    {
        // Remove duplicate spaces
        $inputText = preg_replace('/\s+/', ' ', $inputText);

        // Remove duplicate commas
        $inputText = preg_replace('/,{2,}/', ',', $inputText);

        // Remove space before commas
        $inputText = preg_replace('/\s+,/', ',', $inputText);

        // Remove space after commas
        $inputText = preg_replace('/,\s+/', ',', $inputText);

        return trim($inputText); // Trim any leading or trailing spaces
    }

    /**
     * @param $inputString
     * @return array
     */
    public static function specialCharIndex($inputString, $input = null)
    {
        if ($input) {
            $input = strpos($inputString, $input);
            return [$input];
        }
        $spaceIndex = strpos($inputString, ' ');
        $commaIndex = strpos($inputString, ',');
        $slashIndex = strpos($inputString, '/');
        $mailIndex = strpos($inputString, '@');
        $hyphen = strpos($inputString, '-');
        return [$spaceIndex, $commaIndex, $slashIndex, $mailIndex, $hyphen];
    }

    /**
     * @param $inputString
     * @return int
     */
    public static function getSecondOccurPositionOfChar($inputString)
    {
        $indices = self::specialCharIndex($inputString);
        $validIndices = array_filter($indices, function ($index) {
            return $index !== false;
        });
        if (count($validIndices) < 2) {
            return -1;
        }
        sort($validIndices);
        return $validIndices[1];
    }

    /**
     * @param $inputString
     * @return int|mixed
     */
    public static function getFirstOccurPositionOfChar($inputString, $input = null)
    {
        // Find the position of the first space, comma, slash, and @
        $indices = self::specialCharIndex($inputString, $input);
        $validIndices = array_filter($indices, function ($index) {
            return $index !== false;
        });
        if (empty($validIndices)) {
            return -1;
        }
        return min($validIndices);
    }

    /**
     * @param $originalString
     * @param $maskedString
     * @param $char
     * @return string
     */
    public static function unmaskString($originalString, $maskedString, $char = "*")
    {
        $unmaskedString = '';
        $maskedLength = strlen($maskedString);
        for ($i = 0; $i < $maskedLength; $i++) {
            if ($maskedString[$i] != $char) {
                $unmaskedString .= $originalString[$i];
            } else {
                $unmaskedString .= $char;
            }
        }
        return $unmaskedString;
    }

}
