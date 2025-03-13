<?php

/**
 * Class Poad
 *
 * This is the Phoundation Object Array Data handler
 *
 * This method handles strings or arrays containing POAD data, or can generate them
 *
 * @see       https://www.adayinthelifeof.nl/2011/02/06/memcache-internals/
 * @see       https://www.php.net/manual/en/class.memcached.php
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Poad;

use PDOStatement;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Enums\EnumPoadTypes;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Interfaces\PoadInterface;
use Phoundation\Data\Traits\TraitDataMixedValue;
use Phoundation\Data\Traits\TraitDataSourceArray;
use Phoundation\Exception\ObjectDecodeException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Web\Requests\Enums\EnumHeaderFooterType;
use Phoundation\Web\Requests\Response;


class Poad
{
    use TraitDataSourceArray {
        setSource as protected __setSource;
    }
    use TraitDataMixedValue {
        getValue as protected __getValue;
        setValue as protected __setValue;
    }


    /**
     * Poad class constructor
     *
     * @param array|string|float|int|null $value
     */
    public function __construct(array|string|float|int|null $value)
    {
        $this->setValue($value);
    }


    /**
     * Returns a new static object
     *
     * @param array|string|float|int|null $source
     *
     * @return static
     */
    public static function new(array|string|float|int|null $source): static
    {
        return new static($source);
    }


    /**
     * Generates a POAD array for the specified data source
     *
     * @param mixed         $value
     * @param string|null   $class
     * @param EnumPoadTypes $type
     * @param array|null    $additional_fields
     *
     * @return array
     */
    public static function generateArray(mixed $value, ?string $class, EnumPoadTypes $type, ?array $additional_fields = null): array
    {
        $return = [
            'poad'     => 'PHOUNDATION',
            'version'  => Core::PHOUNDATION_VERSION,
            'datatype' => $type->value,
            'class'    => $class,
            'source'   => $value
        ];

        if ($additional_fields) {
            return array_replace($additional_fields, $return);
        }

        return $return;
    }


    /**
     * Generates a POAD string for the specified data source
     *
     * @param mixed         $value
     * @param string|null   $class
     * @param EnumPoadTypes $type
     * @param array|null    $additional_fields
     * @param bool          $force_pretty_print
     *
     * @return string
     */
    public static function generateString(mixed $value, ?string $class, EnumPoadTypes $type, ?array $additional_fields = null, bool $force_pretty_print = false): string
    {
        return 'POADJSON' . Json::encode(static::generateArray($value, $class, $type, $additional_fields), ($force_pretty_print ? JSON_PRETTY_PRINT : 0));
    }


    /**
     * Decodes the specified JSON source string to an array
     *
     * @param string $source
     *
     * @return array
     */
    public function decodeJson(string $source): array
    {
        // This is a Phoundation Object Array Data string with JSON encoding. Strip the header and decode it now.
        try {
            return Json::decode($source, JSON_OBJECT_AS_ARRAY);

        } catch (JsonException $e) {
            throw ObjectDecodeException::new(tr('Failed to decode specified POADJSON source string into object because source string is not valid JSON'), $e)
                                       ->setData([
                                           'source' => $source
                                       ]);
        }
    }


    /**
     * Returns true if the specified array or string is a valid POAD data structure
     *
     * @param $source
     *
     * @return bool
     */
    public static function isValidPoad($source): bool
    {
        if (is_array($source)) {
            // Source is an array now. Check for a valid PAO format.
            if (array_key_exists('poad', $source)) {
                // This seems to be a POAD array! Confirm and decode
                if (array_key_exists('version', $source)) {
                    if (array_key_exists('datatype', $source)) {
                        if (array_key_exists('class', $source)) {
                            if (array_key_exists('source', $source)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }


    /**
     * Sets the source for this POAD object
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     * @param array|null                                       $execute
     *
     * @return $this
     */
    public function setSource(IteratorInterface|array|string|PDOStatement|null $source = null, ?array $execute = null): static
    {
        return $this->__setValue(null)
                    ->__setSource($source, $execute);
    }


    /**
     * Returns the value for this POAD object
     *
     * @return array|string|float|int|null
     */
    public function getValue(): array|string|float|int|null
    {
        return $this->__getValue();
    }


    /**
     * Sets the value for this POAD object
     *
     * @param array|string|float|int|null $value
     *
     * @return $this
     */
    public function setValue(array|string|float|int|null $value): static
    {
        if (is_string($value)) {
            if (str_starts_with($value, 'POADJSON')) {
                // This is a POADJSON string, decode it and set the source
                $this->setSource(Poad::decodeJson(substr($value, 8)));
            }

        } elseif (is_array($value)) {
            if (Poad::isValidPoad($value)) {
                $this->setSource($value);
            }
        }

        return $this->__setValue($value);
    }


    /**
     * Returns an object from the given source data
     *
     * This requires the source data to have the POA (Phoundation Object Array) format
     * (See TraitDataSourceArray::__toArray() for more information). A JSON encoded array version is also accepted
     *
     * @param bool $process_headers_footers
     *
     * @return PoadInterface|array|string|float|int|null
     * @see    TraitDataSourceArray::__toArray()
     */
    public function getObject(bool $process_headers_footers = true): PoadInterface|array|string|float|int|null
    {
        if (empty($this->source)) {
            // Return the value as-is
            return $this->value;
        }

        // Yay, all required fields are there! Check datatype to see how to handle this
        switch ($this->source['datatype']) {
            case EnumPoadTypes::object->value:
                if (array_get_safe($this->source, 'auto_decode', true) === false) {
                    // POAD object contains instruction to NOT automatically decode
                    return Arrays::removeKeys($this->source['source'], 'auto_decode');
                }

                return $this->source['class']::newFromSource($this->source['source']);

            case EnumPoadTypes::compound->value:
                $return = null;

                if ($process_headers_footers) {
                    foreach ($this->source as $key => $value) {
                        switch ($key) {
                            case 'source':
                                $return = $value;
                                break;

                            case 'headers':
                                Response::addPageHeaders(EnumHeaderFooterType::autodetect, $value);
                                break;

                            case 'footers':
                                Response::addPageFooters(EnumHeaderFooterType::autodetect, $value);
                                break;

                            case 'poad':
                                // no break

                            case 'version':
                                // no break

                            case 'datatype':
                                // no break

                            case 'class':
                                // These are system fields, ignore them
                                break;

                            default:
                                Log::warning(tr('Ignoring unknown and unsupported POAD compound key ":key"', [
                                    ':key' => $key
                                ]));
                        }
                    }

                } else {
                    // Don't automatically add headers and footers, just return the data
                    $return = $this->source['source'];
                }

                return Poad::new($return)->getObject();

            default:
                throw OutOfBoundsException::new(tr('Unknown POAD datatype ":datatype" encountered in specified data source', [
                    ':datatype' => $this->source['datatype']
                ]))->addData([
                    'source' => $this->source
                ]);
        }
    }
}
