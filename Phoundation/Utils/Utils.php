<?php

declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Exception\OutOfBoundsException;

/**
 * Class Utils
 *
 * This is the standard Utils class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */
class Utils
{
    /**
     * Match options
     */
    const MATCH_NOT              = 1;

    const MATCH_ANY              = 2;

    const MATCH_ALL              = 4;
    const MATCH_REQUIRE          = 8;
    const MATCH_STRICT           = 16;
    const MATCH_STARTS_WITH      = 32;

    const MATCH_ENDS_WITH        = 64;
    const MATCH_CONTAINS         = 128;
    const MATCH_FULL             = 256;
    const MATCH_REGEX            = 512;

    const MATCH_CASE_INSENSITIVE = 1024;

    const MATCH_RECURSE          = 2048;
    const MATCH_NULL             = 4096;
    const MATCH_EMPTY            = 8192;
    const MATCH_SINGLE           = 16384;
    const MATCH_TRIM             = 32768;


    /**
     * Match actions
     */
    protected const MATCH_ACTION_RETURN_VALUES      = 1;
    protected const MATCH_ACTION_RETURN_KEYS        = 2;
    protected const MATCH_ACTION_RETURN_NEEDLES     = 3;
    protected const MATCH_ACTION_RETURN_NOT_VALUES  = 4;
    protected const MATCH_ACTION_RETURN_NOT_KEYS    = 5;
    protected const MATCH_ACTION_RETURN_NOT_NEEDLES = 6;
    protected const MATCH_ACTION_DELETE             = 7;


    /**
     * If set, will filter NULL values
     */
    const FILTER_NULL = 1;

    /**
     * If set, will filter all empty values
     */
    const FILTER_EMPTY = 2;

    /**
     * If set, will quote all values
     */
    const QUOTE_ALWAYS = 4;

    /**
     * If set, will only display key, not value
     */
    const HIDE_EMPTY_VALUES = 8;


    /**
     * Decodes and checks the match flags and returns array with all match options
     *
     * @param int  $options
     * @param bool $allow_recurse
     *
     * Supported match flags are:
     *
     * Utils::MATCH_CASE_INSENSITIVE  Will match needles for entries in case-insensitive mode.
     * Utils::MATCH_ALL               Will match needles for entries that contain all the specified needles.
     * Utils::MATCH_ANY               Will match needles for entries that contain any of the specified needles.
     * Utils::MATCH_STARTS_WITH       Will match needles for entries that start with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_ENDS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_ENDS_WITH         Will match needles for entries that end with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_CONTAINS          Will match needles for entries that contain the specified needles anywhere.
     *                                Mutually exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_RECURSE           Will recurse into arrays, if encountered.
     * Utils::MATCH_NOT               Will match needles for entries that do NOT match the needle.
     * Utils::MATCH_STRICT            Will match needles for entries that match the needle strict (so 0 does NOT match
     *                                "0", "" does NOT match 0, etc.).
     * Utils::MATCH_FULL              Will match needles for entries that fully match the needle. Mutually
     *                                exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_CONTAINS, and Utils::MATCH_REGEX.
     * Utils::MATCH_REGEX             Will match needles for entries that match the specified regular expression.
     *                                Mutually exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_CONTAINS, and Utils::MATCH_FULL.
     * Utils::MATCH_EMPTY             Will match empty values instead of ignoring them. NOTE: Empty values may be
     *                                ignored while NULL values are still matched using the MATCH_NULL flag
     * Utils::MATCH_NULL              Will match NULL values instead of ignoring them. NOTE: NULL values may be
     *                                ignored while non-NULL empty values are still matched using the MATCH_EMPTY flag
     * Utils::MATCH_REQUIRE           Requires at least one result
     * Utils::MATCH_SINGLE            Will match only a single entry for the executed action (return, remove, etc.)
     *
     * @return array
     */
    protected static function decodeMatchFlags(int $options, bool $allow_recurse): array
    {
        // Decode options
        $return             = [];
        $return['no_case']  = (bool) ($options & Utils::MATCH_CASE_INSENSITIVE);
        $return['all']      = (bool) ($options & Utils::MATCH_ALL);
        $return['any']      = (bool) ($options & Utils::MATCH_ANY);
        $return['require']  = (bool) ($options & Utils::MATCH_REQUIRE);
        $return['start']    = (bool) ($options & Utils::MATCH_STARTS_WITH);
        $return['end']      = (bool) ($options & Utils::MATCH_ENDS_WITH);
        $return['contains'] = (bool) ($options & Utils::MATCH_CONTAINS);
        $return['recurse']  = (bool) ($options & Utils::MATCH_RECURSE);
        $return['not']      = (bool) ($options & Utils::MATCH_NOT);
        $return['strict']   = (bool) ($options & Utils::MATCH_STRICT);
        $return['full']     = (bool) ($options & Utils::MATCH_FULL);
        $return['regex']    = (bool) ($options & Utils::MATCH_REGEX);
        $return['empty']    = (bool) ($options & Utils::MATCH_EMPTY);
        $return['null']     = (bool) ($options & Utils::MATCH_NULL);
        $return['single']   = (bool) ($options & Utils::MATCH_SINGLE);
        $return['trim']     = (bool) ($options & Utils::MATCH_TRIM);

        // Validate options
        if ($return['full']) {
            $return['match_mode'] = 'full';

            if ($return['start'] or $return['end'] or $return['contains'] or $return['regex']) {
                $mutually = true;
            }

        } else {
            if ($return['strict']) {
                throw new OutOfBoundsException(tr('The MATCH_STRICT option can only be used with MATCH_FULL'));
            }

            if ($return['start']) {
                $return['match_mode'] = 'start';

                if ($return['end'] or $return['contains'] or $return['regex']) {
                    $mutually = true;
                }

            } elseif ($return['end']) {
                $return['match_mode'] = 'end';

                if ($return['contains'] or $return['regex']) {
                    $mutually = true;
                }

            } elseif ($return['contains']) {
                $return['match_mode'] = 'contains';

                if ($return['regex']) {
                    $mutually = true;
                }

            } elseif ($return['regex']){
                $return['match_mode'] = 'regex';

            } else {
                // Default to full matching
                $return['full']       = true;
                $return['match_mode'] = 'full';
            }
        }

        if (isset($mutually)) {
            throw new OutOfBoundsException(tr('Cannot mix location flags MATCH_STARTS_WITH, MATCH_ENDS_WITH, MATCH_CONTAINS, MATCH_FULL, or MATCH_REGEX, they are mutually exclusive'));
        }

        if ($return['all']) {
            if ($return['any']) {
                throw new OutOfBoundsException(tr('Cannot mix combination flags MATCH_ALL with MATCH_ANY, they are mutually exclusive'));
            }

        } elseif ($return['not']) {
            if (!$return['any']) {
                // MATCH_NOT defaults to MATCH_ALL
                $return['all'] = true;
            }

        } else {
            // Default to MATCH_ANY
            $return['any'] = true;
        }

        if ($return['recurse'] and !$allow_recurse) {
            throw new OutOfBoundsException(tr('Recursion matching not allowed'));
        }

        return $return;
    }


    /**
     * Checks specified needles that they have content and will ensure they are specified as an array
     *
     * @param DataIteratorInterface|array|string|null $needles
     * @param array                                   $flags
     *
     * @return array
     */
    protected static function prepareNeedles(DataIteratorInterface|array|string|null $needles, array $flags): array
    {
        if (!$needles) {
            throw new OutOfBoundsException(tr('No needles specified'));
        }
        $needles = Arrays::force($needles);
        if ($flags['no_case'] or $flags['trim']) {
            // Trim and or make all needles lowercase strings?
            foreach ($needles as &$needle) {
                if ($flags['trim']) {
                    $needle = trim((string) $needle);
                }
                if ($flags['no_case']) {
                    $needle = strtolower((string) $needle);
                }
            }
            unset($needle);
        }

        return $needles;
    }


    /**
     * Process the given array and matches the specified needles with the source key and return the requested result
     *
     * @param int                                     $action
     * @param DataIteratorInterface|array             $source
     * @param DataIteratorInterface|array|string|null $needles
     * @param int                                     $flags
     *
     * @return array
     */
    protected static function matchKeys(int $action, DataIteratorInterface|array $source, DataIteratorInterface|array|string|null $needles, int $flags): array
    {
        $flags   = static::decodeMatchFlags($flags, true);
        $needles = static::prepareNeedles($needles, $flags);

        if ($source instanceof DataIteratorInterface) {
            $source = $source->getSource();
        }

        // Execute matching
        switch ($flags['match_mode']) {
            case 'full':
                return static::matchKeysFunction($action, $source, $needles, $flags, function (mixed $key, mixed $needle, array $flags) {
                    return (($flags['strict'] and ($key === $needle)) or ($key == $needle));
                });

            case 'regex':
                return static::matchKeysFunction($action, $source, $needles, $flags, function (mixed $key, mixed $needle, array $flags) {
                    return preg_match($needle, $key);
                });

            case 'contains':
                return static::matchKeysFunction($action, $source, $needles, $flags, function (mixed $key, mixed $needle, array $flags) {
                    return str_contains($key, $needle);
                });

            case 'start':
                return static::matchKeysFunction($action, $source, $needles, $flags, function (mixed $key, mixed $needle, array $flags) {
                    return str_starts_with($key, $needle);
                });

            case 'end':
                return static::matchKeysFunction($action, $source, $needles, $flags, function (mixed $key, mixed $needle, array $flags) {
                    return str_ends_with($key, $needle);
                });
        }

        throw new OutOfBoundsException(tr('Unknown match mode ":mode" specified', [
            ':mode' => $flags['match_mode']
        ]));
    }


    /**
     * Process the given array and matches the specified needles with the source values and return the requested result
     *
     * @param int                                     $action
     * @param DataIteratorInterface|array             $source
     * @param DataIteratorInterface|array|string|null $needles
     * @param int                                     $flags
     * @param string|null                             $column
     *
     * @return array
     */
    protected static function matchValues(int $action, DataIteratorInterface|array $source, DataIteratorInterface|array|string|null $needles, int $flags, ?string $column = null): array
    {
        $flags   = static::decodeMatchFlags($flags, true);
        $needles = static::prepareNeedles($needles, $flags);

        if ($source instanceof DataIteratorInterface) {
            $source = $source->getSource();
        }

        // Execute matching
        switch ($flags['match_mode']) {
            case 'full':
                return static::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                    return (($flags['strict'] and ($value === $needle)) or ($value == $needle));
                });

            case 'regex':
                return static::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                    return preg_match($needle, $value);
                });

            case 'contains':
                return static::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                    return str_contains($value, $needle);
                });

            case 'start':
                return static::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                    return str_starts_with($value, $needle);
                });

            case 'end':
                return static::matchValuesFunction($action, $source, $needles, $flags, $column, function (mixed $value, mixed $needle, array $flags) {
                    return str_ends_with($value, $needle);
                });
        }

        throw new OutOfBoundsException(tr('Unknown match mode ":mode" specified', [
            ':mode' => $flags['match_mode']
        ]));
    }


    /**
     * Cleans the specified value and returns true if the value should be used
     *
     * @param mixed $value
     * @param array $flags
     *
     * @return bool
     */
    protected static function useCleanedHaystackValue(mixed &$value, array $flags): bool
    {
        if ($flags['trim']) {
            $value = trim($value);
        }

        if ($flags['no_case']) {
            $value = strtolower($value);
        }

        if (!$value) {
            if (!$flags['empty']) {
                if (($value !== null) or !$flags['null']) {
                    // Ignore empty or null lines
                    return false;
                }
            } elseif (($value === null) and !$flags['null']) {
                // Ignore NULL lines
                return false;
            }
        }

        return true;
    }


    /**
     * Checks if there is a required match and throws an exception if not
     *
     * @param array $needles
     * @param array $flags
     * @param array $return
     *
     * @return array
     */
    protected static function checkMatch(array $needles, array $flags, array $return): array
    {
        if (empty($return) and $flags['require']) {
            if ($flags['all']) {
                throw new OutOfBoundsException(tr('The source contained no keys matching all of the required needles ":needles"', [
                    ':needles' => $needles,
                ]));
            }

            throw new OutOfBoundsException(tr('The source contained no keys matching any of the required needles ":needles"', [
                ':needles' => $needles,
            ]));
        }

        return $return;
    }


    /**
     * Process the match
     *
     * @param bool  $matched
     * @param int   $action
     * @param array $return
     * @param mixed $needle
     * @param mixed $key
     * @param mixed $value
     * @param array $flags
     *
     * @return void
     */
    protected static function processMatch(bool $matched, int $action, array &$return, mixed &$needle, mixed &$key, mixed &$value, array $flags): void
    {
        if ($matched) {
            switch ($action) {
                case Utils::MATCH_ACTION_RETURN_VALUES:
                    $return[$key] = $value;
                    return;

                case Utils::MATCH_ACTION_RETURN_KEYS:
                    $return[$key] = $key;
                    return;

                case Utils::MATCH_ACTION_RETURN_NEEDLES:
                    $return[$key] = $needle;
                    return;
            }

        } else {
            switch ($action) {
                case Utils::MATCH_ACTION_RETURN_NOT_VALUES:
                    $return[$key] = $value;
                    return;

                case Utils::MATCH_ACTION_RETURN_NOT_KEYS:
                    $return[$key] = $key;
                    return;

                case Utils::MATCH_ACTION_RETURN_NOT_NEEDLES:
                    $return[$key] = $needle;
                    return;

                case Utils::MATCH_ACTION_DELETE:
                    break;
            }
        }
    }


    /**
     * Process the given array with the specified needles for full matching and return the requested result
     *
     * @param int      $action
     * @param array    $source
     * @param array    $needles
     * @param array    $flags
     * @param callable $function
     *
     * @return array
     */
    protected static function matchKeysFunction(int $action, array $source, array $needles, array $flags, callable $function): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            $needles_match = false;

            foreach ($needles as $needle) {
                if (!static::useCleanedHaystackValue($key, $flags)) {
                    continue;
                }

                $match = $function($key, $needle, $flags);

                if ($flags['not']) {
                    // Invert the match result
                    $match = !$match;
                }

                if ($match) {
                    // This needle matched, yay!
                    $needles_match = true;

                    if ($flags['any']) {
                        // We're in "any" mode, and a single needle matched, so don't consider any other needles.
                        break;
                    }

                    // Check the next needle
                    continue;
                }

                // This needle did not match

                if ($flags['all']) {
                    // We're in "all" mode, and a single needle failed, so don't consider any other needles.
                    $needles_match = false;
                    break;
                }
            }

            static::processMatch($needles_match, $action, $return, $needle, $key, $value, $flags);
        }

        return static::checkMatch($needles, $flags, $return);
    }


    /**
     * Process the given array with the specified needles for full matching and return the requested result
     *
     * @param int         $action
     * @param array       $source
     * @param array       $needles
     * @param string|null $column
     * @param array $flags
     * @param callable    $function
     *
     * @return array
     */
    protected static function matchValuesFunction(int $action, array $source, array $needles, array $flags, ?string $column, callable $function): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            $needles_match = false;

            foreach ($needles as $needle) {
                $value = static::getStringValue($value, $column);

                if (!static::useCleanedHaystackValue($value, $flags)) {
                    continue;
                }

                $match = $function($value, $needle, $flags);

                if ($flags['not']) {
                    // Invert the match result
                    $match = !$match;
                }

                if ($match) {
                    // This needle matched, yay!
                    $needles_match = true;

                    if ($flags['any']) {
                        // We're in "any" mode, and a single needle matched, so don't consider any other needles.
                        break;
                    }

                    // Check the next needle
                    continue;
                }

                // This needle did not match

                if ($flags['all']) {
                    // We're in "all" mode, and a single needle failed, so don't consider any other needles.
                    $needles_match = false;
                    break;
                }
            }

            static::processMatch($needles_match, $action, $return, $needle, $key, $value, $flags);
        }

        return static::checkMatch($needles, $flags, $return);
    }


    /**
     * Returns the value if it's a scalar, the key value if it's an array, or the object value if it's a
     * DataEntryInterface object
     *
     * @param mixed       $value
     * @param string|null $column
     *
     * @return string
     */
    protected static function getStringValue(mixed $value, ?string $column): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return Strings::force($value);
        }

        if (!$column) {
            throw new OutOfBoundsException(tr('Cannot extract string value from array or DataEntryInterface object, no column specified', [
                ':value' => $value,
            ]));
        }

        if (is_array($value)) {
            return $value[$column];
        }

        if ($value instanceof EntryInterface) {
            return $value->get($column);
        }

        throw new OutOfBoundsException(tr('Specified value ":value" must be either scalar, array, or a ":class" type object', [
            ':value' => $value,
            ':class' => EntryInterface::class
        ]));
    }
}
