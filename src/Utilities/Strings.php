<?php

namespace App\Utilities;

class Strings
{
    /**
     * Truncate text (adding "..." if needed)
     *
     * @param string $text
     * @param int $limit
     * @param string $pad
     */
    public static function truncateText(string $text, $limit = 80, $pad = '...'): string
    {
        mb_internal_encoding('UTF-8');

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $wrapped_text = self::mbWordwrap($text, $limit, '{N}', true);
        $shortened_text = mb_substr($wrapped_text, 0, strpos($wrapped_text, '{N}'));

        // Prevent the padding string from bumping up against punctuation.
        $punctuation = ['.', ',', ';', '?', '!'];
        if (in_array(mb_substr($shortened_text, -1), $punctuation, true)) {
            $shortened_text = mb_substr($shortened_text, 0, -1);
        }

        return $shortened_text . $pad;
    }

    /**
     * Generate a randomized password of specified length.
     *
     * @param int $char_length
     */
    public static function generatePassword($char_length = 8): string
    {
        // String of all possible characters. Avoids using certain letters and numbers that closely resemble others.
        $numeric_chars = str_split('234679');
        $uppercase_chars = str_split('ACDEFGHJKLMNPQRTWXYZ');
        $lowercase_chars = str_split('acdefghjkmnpqrtwxyz');

        $chars = [$numeric_chars, $uppercase_chars, $lowercase_chars];

        $password = '';
        for ($i = 1; $i <= $char_length; $i++) {
            $char_array = $chars[$i % 3];
            $password .= $char_array[random_int(0, count($char_array) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * UTF-8 capable replacement for wordwrap function.
     *
     * @param string $str
     * @param int $width
     * @param string $break
     * @param bool $cut
     */
    public static function mbWordwrap(string $str, $width = 75, $break = "\n", $cut = false): string
    {
        $lines = explode($break, $str);
        foreach ($lines as &$line) {
            $line = rtrim($line);
            if (mb_strlen($line) <= $width) {
                continue;
            }
            $words = explode(' ', $line);
            $line = '';
            $actual = '';
            foreach ($words as $word) {
                if (mb_strlen($actual . $word) <= $width) {
                    $actual .= $word . ' ';
                } else {
                    if ($actual != '') {
                        $line .= rtrim($actual) . $break;
                    }
                    $actual = $word;
                    if ($cut) {
                        while (mb_strlen($actual) > $width) {
                            $line .= mb_substr($actual, 0, $width) . $break;
                            $actual = mb_substr($actual, $width);
                        }
                    }
                    $actual .= ' ';
                }
            }
            $line .= trim($actual);
        }

        return implode($break, $lines);
    }

    /**
     * Truncate URL in text-presentable format (i.e. "http://www.example.com" becomes "example.com")
     *
     * @param string $url
     * @param int $length
     */
    public static function truncateUrl(string $url, $length = 40): string
    {
        $url = str_replace(['http://', 'https://', 'www.'], '', $url);

        return self::truncateText(rtrim($url, '/'), $length);
    }
}
