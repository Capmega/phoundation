<?php

 namespace Phoundation\Accounts\Users\Locale\Language\Interfaces;

use Phoundation\Accounts\Users\Interfaces\UserInterface;

interface PhoLocaleInterface
{
    /**
     * Returns the user object for this PhoLocale object
     *
     * @return UserInterface
     */
    public function getUserObject(): UserInterface;

    /**
     *
     *
     * @return string
     */
    public function getLocale(): string;

    /**
     * Returns the default date/time format for this process used in PHP
     *
     * @return string
     */
    public function getDateTimeFormatPhp(): string;

    /**
     * Returns the default date/time format for this process used in PHP
     *
     * @return string
     */
    public function getDateFormatPhp(): string;

    /**
     * Returns the default date format for this process used in JavaScript
     *
     * @return string
     */
    public function getDateFormatJavaScript(): string;

    /**
     * Returns the default date/time format for this process used in JavaScript
     *
     * @return string
     */
    public function getDateTimeFormatJavaScript(): string;

    /**
     * Returns a formatted version of the specified phone number
     *
     * @param string|int|null $phone_number
     * @param bool            $international
     *
     * @return string|null
     */
    public function formatPhoneNumber(string|int|null $phone_number, bool $international = true): ?string;
}
