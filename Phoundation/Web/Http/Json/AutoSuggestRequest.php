<?php

/**
 * Class AutoSuggestRequest
 *
 * This class manages JSON auto suggest requests coming from the jQuery UI auto suggest component. It validates the
 * request data and makes the data available through getter methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Http\Json;

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;


class AutoSuggestRequest
{
    /**
     * The $_GET request data
     *
     * @var array $get
     */
    protected static array $get;


    /**
     * Will already execute the GET data validation so that subsequent GET data validations that might clear GET will
     * not cause the loss of auto suggest data
     *
     * @param bool $term_optional
     *
     * @return void
     */
    public static function init(bool $term_optional = false): void
    {
        static::ensureGet($term_optional);
    }


    /**
     * AutoSuggestRequest class constructor
     *
     * @param bool $term_optional
     */
    protected static function ensureGet(bool $term_optional = false): void
    {
        if (isset(static::$get)) {
            return;
        }

        // Validate request data
        if ($term_optional) {
            $validator = GetValidator::new()->select('term')->isOptional('')->sanitizeTrim()->hasMaxCharacters(255)->isPrintable();

        } else {
            $validator = GetValidator::new()->select('term')->sanitizeTrim()->hasMaxCharacters(255)->isPrintable();
        }

        static::$get = $validator->validate(false);
    }


    /**
     * Returns the search term
     *
     * @param int $max_size
     *
     * @return string
     */
    public static function getTerm(int $max_size = 255): string
    {
        static::ensureGet();

        if (strlen(static::$get['term']) > $max_size) {
            throw new ValidationFailedException(tr('The field term must have ":count" characters or less', [
                ':count' => $max_size,
            ]));
        }

        return static::$get['term'];
    }


    /**
     * Sets the search term
     *
     * @param string $term
     *
     * @return void
     */
    public static function setTerm(string $term): void
    {
        static::ensureGet();
        static::$get['term'] = $term;
    }
}
