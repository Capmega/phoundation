<?php

/**
 * Class Utils
 *
 * This is the standard Utils class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils;


use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Stringable;

class Utils
{
    /**
     * Match options
     */
    const int MATCH_NOT              = 1;

    const int MATCH_ANY              = 2;

    const int MATCH_ALL              = 4;
    const int MATCH_REQUIRE          = 8;
    const int MATCH_STRICT           = 16;
    const int MATCH_BEGINS_WITH      = 32;

    const int MATCH_ENDS_WITH        = 64;
    const int MATCH_CONTAINS         = 128;
    const int MATCH_FULL             = 256;
    const int MATCH_REGEX            = 512;

    const int MATCH_CASE_INSENSITIVE = 1024;

    const int MATCH_RECURSE          = 2048;
    const int MATCH_NULL             = 4096;
    const int MATCH_EMPTY            = 8192;
    const int MATCH_SINGLE           = 16384;
    const int MATCH_TRIM             = 32768;
    const int SKIP_NULL_NEEDLES      = 65536;
    const int SKIP_EMPTY_NEEDLES     = 131072;


    /**
     * Match actions
     */
    protected const int MATCH_ACTION_RETURN_VALUES          = 1;
    protected const int MATCH_ACTION_RETURN_FULL_VALUES     = 2;
    protected const int MATCH_ACTION_RETURN_KEYS            = 3;
    protected const int MATCH_ACTION_RETURN_NEEDLES         = 4;
    protected const int MATCH_ACTION_RETURN_NOT_VALUES      = 5;
    protected const int MATCH_ACTION_RETURN_NOT_FULL_VALUES = 6;
    protected const int MATCH_ACTION_RETURN_NOT_KEYS        = 7;
    protected const int MATCH_ACTION_RETURN_NOT_NEEDLES     = 8;
    protected const int MATCH_ACTION_DELETE                 = 9;


    /**
     * If specified, will filter NULL values
     */
    const int FILTER_NULL = 1;

    /**
     * If specified, will filter all empty values
     */
    const int FILTER_EMPTY = 2;

    /**
     * If specified, will quote all values
     */
    const int QUOTE_ALWAYS = 4;

    /**
     * If specified, will only display key, not value
     */
    const int HIDE_EMPTY_VALUES = 8;

    /**
     * If specified, will ensure all keys have the same size
     */
    const int EQUALIZE_KEY_SIZES = 16;


    /**
     * Process the given array and matches the specified needles with the source values and return the requested result
     *
     * @param int                                                $action
     * @param DataIteratorInterface|array                        $source
     * @param IteratorInterface|Stringable|array|string|int|null $needles
     * @param int                                                $flags
     * @param string|null                                        $column
     *
     * @return array
     */
    protected static function matchValues(int $action, DataIteratorInterface|array $source, IteratorInterface|Stringable|array|string|int|null $needles, int $flags, ?string $column = null): array
    {
        $flags   = Arrays::decodeMatchFlags($flags, true);
        $needles = Arrays::prepareNeedles($needles, $flags);

        if ($source instanceof DataIteratorInterface) {
            $source = $source->getSource();
        }

        // Execute matching
        return match ($flags['match_mode']) {
            'full'     => Arrays::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                return (($flags['strict'] and ($value === $needle)) or ($value == $needle));
            }),

            'regex'    => Arrays::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                return preg_match($needle, $value);
            }),

            'contains' => Arrays::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                return str_contains($value, $needle);
            }),

            'start'    => Arrays::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                return str_starts_with($value, $needle);
            }),

            'end'      => Arrays::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                return str_ends_with($value, $needle);
            }),

            default    => throw new OutOfBoundsException(tr('Unknown match mode ":mode" specified', [
                ':mode' => $flags['match_mode']
            ])),
        };
    }
}
