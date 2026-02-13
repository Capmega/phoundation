<?php

/**
 * Class AutoSuggestRequest
 *
 * This class manages JSON auto suggest requests coming from the jQuery UI auto suggest component. It validates the
 * request data and makes the data available through getter methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Http\Json;

use Phoundation\Core\Log\Log;
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
     * @param bool $require_clean_source
     *
     * @return void
     */
    public static function init(bool $term_optional = false, bool $require_clean_source = true): void
    {
        static::ensureGet($term_optional, $require_clean_source);
    }


    /**
     * AutoSuggestRequest class constructor
     *
     * @param bool $term_optional
     * @param bool $require_clean_source
     */
    protected static function ensureGet(bool $term_optional = false, bool $require_clean_source = true): void
    {
        if (isset(static::$get)) {
            return;
        }

        // Validate request data
        if ($term_optional) {
            $_validator = GetValidator::new()->select('term')->isOptional('')
                                                            ->sanitizeTrim()
                                                            ->hasMaxCharacters(255)
                                                            ->containsNoHtml()
                                                            ->matchesRegex('/[0-9a-z-,\'"() ]+/i', message: tr('is not a valid character'));

        } else {
            $_validator = GetValidator::new()->select('term')->sanitizeTrim()
                                                            ->hasMaxCharacters(255)
                                                            ->matchesRegex('/[0-9a-z-,\'"() ]+/i', message: tr('is not a valid character'));
        }

        static::$get = $_validator->validate($require_clean_source);
    }


    /**
     * Returns the search term
     *
     * @param bool $require_clean_source
     * @param int  $max_size
     *
     * @return string
     */
    public static function getTerm(bool $require_clean_source = true, int $max_size = 255): string
    {
        static::ensureGet($require_clean_source);

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
        static::$get['term'] = trim($term);
    }
}
