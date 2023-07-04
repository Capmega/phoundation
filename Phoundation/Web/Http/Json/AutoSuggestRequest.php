<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Json;

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;


/**
 * Class AutoSuggestRequest
 *
 * This class manages JSON auto suggest requests coming from the jQuery UI auto suggest component. It validates the
 * request data and makes the data available through getter methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class AutoSuggestRequest
{
    /**
     * The $_GET request data
     *
     * @var array $get
     */
    protected static array $get;


    /**
     * AutoSuggestRequest class constructor
     *
     * @param bool $term_optional
     */
    protected static function ensureGet(bool $term_optional = false): void
    {
        if (isset(self::$get)) {
            return;
        }

        // Validate request data
        $validator = GetValidator::new()
            ->select('callback')->hasMaxCharacters(48)->matchesRegex('/jQuery\d+_\d+/')
            ->select('_')->isNatural();

        if ($term_optional) {
            $validator->select('term')->isOptional('')->hasMaxCharacters(255)->isPrintable();

        } else {
            $validator->select('term')->hasMaxCharacters(255)->isPrintable();
        }


        self::$get = $validator->validate(false);
    }


    /**
     * Will already execute the GET data validation so that subsequent GET data validations that might clear GET will
     * not cause the loss of auto suggest data
     *
     * @param bool $term_optional
     * @return void
     */
    public static function init(bool $term_optional = false): void
    {
        self::ensureGet($term_optional);
    }


    /**
     * Returns the jQuery callback
     *
     * @return string
     */
    public static function getCallback(): string
    {
        self::ensureGet();
        return self::$get['callback'];
    }


    /**
     * Returns the search term
     *
     * @param int $max_size
     * @return string
     */
    public static function getTerm(int $max_size = 255): string
    {
        self::ensureGet();

        if (strlen(self::$get['term']) > $max_size) {
            throw new ValidationFailedException(tr('The field term must have ":count" characters or less', [
                ':count' => $max_size
            ]));
        }

        return self::$get['term'];
    }


    /**
     * Returns the _ value
     *
     * @return string
     */
    public static function get_(): string
    {
        self::ensureGet();
        return self::$get['_'];
    }
}