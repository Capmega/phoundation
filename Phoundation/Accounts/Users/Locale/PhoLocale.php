<?php

/**
 * Class PhoLocale
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Locale;

use Locale;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Locale\Language\Interfaces\PhoLocaleInterface;
use Phoundation\Date\PhoDateTimeFormats;


class PhoLocale extends Locale implements PhoLocaleInterface
{
    /**
     * Tracks the user for this PhoLocale object
     *
     * @var UserInterface
     */
    protected UserInterface $_user;


    /**
     * PhoLocale class constructor
     *
     * @param UserInterface $_user
     */
    public function __construct(UserInterface $_user)
    {
        $this->_user = $_user;
    }


    /**
     * Returns the user object for this PhoLocale object
     *
     * @return UserInterface
     */
    public function getUserObject(): UserInterface
    {
        return $this->_user;
    }


    /**
     *
     *
     * @return string
     */
    public function getLocale(): string
    {
        return 'en-CA';
    }


    /**
     * Returns the default date/time format for this process used in PHP
     *
     * @return string
     */
    public function getDateTimeFormatPhp(): string
    {
        return PhoDateTimeFormats::getDefaultDateTimeFormatPhp();
    }


    /**
     * Returns the default date/time format for this process used in PHP
     *
     * @return string
     */
    public function getDateFormatPhp(): string
    {
        return PhoDateTimeFormats::getDefaultDateFormatPhp();
    }


    /**
     * Returns the default date/time format for this process used in PHP
     *
     * @return string
     */
    public function getTimeFormatPhp(): string
    {
        return PhoDateTimeFormats::getDefaultTimeFormatPhp();
    }


    /**
     * Returns the default date format for this process used in JavaScript
     *
     * @param bool $lowercase
     *
     * @return string
     */
    public function getDateFormatJavaScript(bool $lowercase = false): string
    {
        return PhoDateTimeFormats::getDefaultDateFormatJavaScript($lowercase);
    }


    /**
     * Returns the default date/time format for this process used in JavaScript
     *
     * @param bool $lowercase
     *
     * @return string
     */
    public function getDateTimeFormatJavaScript(bool $lowercase = false): string
    {
        return PhoDateTimeFormats::getDefaultDateTimeFormatJavaScript($lowercase);
    }


    /**
     * Returns the default date/time format for this process used in JavaScript
     *
     * @param bool $lowercase
     *
     * @return string
     */
    public function getTimeFormatJavaScript(bool $lowercase = false): string
    {
        return PhoDateTimeFormats::getDefaultTimeFormatJavaScript($lowercase);
    }


    /**
     * Returns a formatted version of the specified phone number
     *
     * @param string|int|null $phone_number
     * @param bool            $international
     *
     * @return string|null
     */
    public function formatPhoneNumber(string|int|null $phone_number, bool $international = true): ?string
    {
        if (!$phone_number) {
            return null;
        }

        return preg_replace('~(.*)(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', ($international ? '$1 ' : '') . '($2) $3-$4', $phone_number);
    }
}
