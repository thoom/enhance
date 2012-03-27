<?php
/**
 * @author Z.d. Peacock <zdpeacock@thoomtech.com>
 * @copyright (c) 2011 Thoom Technologies LLC
 */

namespace Thoom\Generator;

class RandomString
{
    const ALPHA_LOWER = 1;
    const ALPHA_UPPER = 2;
    const ALPHA_MIXED = 3;

    const ALPHANUM_LOWER = 4;
    const ALPHANUM_UPPER = 5;
    const ALPHANUM_MIXED = 6;

    const NUM = 7;

    private static $lower = array(
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
        'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'
    );

    private static $upper = array(
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
        'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
    );

    private static $num = array(
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
    );

    /**
     * Alphanumeric string (mixed numbers and letters)
     * @param int $length Length of the generated string
     * @param int $case What case the alphabet should be returned in (ALPHA_LOWER, ALPHA_UPPER, ALPHA_MIXED)
     * @return string
     */
    public static function alnum($length, $case = self::ALPHA_MIXED)
    {
        return self::get_rand_str($length, array_merge(self::get_alpha_arr($case), self::$num));
    }

    /**
     * Letters only string (no numbers)
     * @param int $length Length of the generated string
     * @param int $case What case the alphabet should be returned in (ALPHA_LOWER, ALPHA_UPPER, ALPHA_MIXED)
     * @return string
     */
    public static function alpha($length, $case = self::ALPHA_MIXED)
    {
        return self::get_rand_str($length, self::get_alpha_arr($case));
    }

    /**
     * Numeric string (no letters)
     * @param int $length Length of the generated string
     * @return string
     */
    public static function num($length)
    {
        return self::get_rand_str($length, self::$num);
    }

    /**
     * Allows a user to define their own random values to build a string
     * @static
     * @param int $length Length of the generated string
     * @param array $array Array of values that will used in generating the string
     * @param int $base If not null, the user array will be merged with the base
     * @return string
     */
    public static function user($length, array $array, $base = null)
    {
        switch ($base) {
            case self::ALPHA_LOWER:
            case self::ALPHA_UPPER:
            case self::ALPHA_MIXED:
                $base_arr = self::get_alpha_arr($base);
                break;
            case self::ALPHANUM_LOWER:
            case self::ALPHANUM_UPPER:
            case self::ALPHANUM_MIXED:
                $base_arr = array_merge(self::get_alpha_arr($base), self::$num);
                break;
            case self::NUM:
                $base_arr = self::$num;
                break;
            default:
                $base_arr = array();
        }

        return self::get_rand_str($length, array_merge($base_arr, $array));
    }

    /**
     * Returns an array with the alpha values requested
     * @static
     * @param $case
     * @return array
     */
    private static function get_alpha_arr($case)
    {
        switch ($case) {
            case self::ALPHA_UPPER:
            case self::ALPHANUM_UPPER:
                $array = self::$upper;
                break;
            case self::ALPHA_LOWER:
            case self::ALPHANUM_LOWER:
                $array = self::$lower;
                break;
            default:
                $array = array_merge(self::$lower, self::$upper);
        }
        return $array;
    }

    /**
     * Builds the random string
     * @static
     * @param int $length Length of the string to be returned
     * @param array $values Array of random values to concat into the string
     * @return string The randomly generated string
     * @throws \Exception
     */
    private static function get_rand_str($length, array $values)
    {
        if ($length < 1)
            throw new \RangeException('Length cannot be zero');

        $rand_str = '';
        $end = count($values) - 1;

        for ($i = 1; $i <= $length; $i++) {
            mt_srand((double)microtime() * 1000000);
            $num = mt_rand(0, $end);
            $rand_str .= $values[$num];
        }
        return $rand_str;
    }
}
