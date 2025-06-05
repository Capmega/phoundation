<?php

/**
 * Class DataFaker
 *
 * This class is a wrapper around fzaninotto's "Faker" library
 * This class will be used to generate fake information such as names, addresses, text, and more
 *
 * @see https://github.com/fzaninotto/Faker
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataFaker;

use Faker\Factory;
use Faker\Generator;
use Phoundation\Accounts\Users\Locale\PhoLocale;
use Phoundation\Accounts\Users\Sessions\Session;

class DataFaker
{
    /**
     * The Faker Factory object
     *
     * @var Generator
     */
    protected Generator $faker;

    /**
     * The locale for this DataFaker
     *
     * @var string
     */
    protected string $locale;


    /**
     * DataFaker class constructor
     *
     * @param string|null $locale The locale to be used for data generation
     */
    public function __construct(?string $locale = null)
    {
        $this->setLocale($locale);
    }


    /**
     * Returns a new DataFaker object
     *
     * @param string|null $locale
     *
     * @return static
     */
    public static function new(?string $locale = null): static
    {
        return new static($locale);
    }


    /**
     * Returns the locale property for this DataFaker
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }


    /**
     * Sets the locale and creates a new internal Faker object to use with this new locale
     *
     * @param string|null $locale
     *
     * @return static
     */
    public function setLocale(?string $locale): static
    {
        if (empty($locale) or empty(trim($locale))) {
            $locale = Session::getLocaleObject()->getDefault();
        }

        $this->locale = $locale;
        $this->faker  = Factory::create($locale);
        return $this;
    }
}
