<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator\Interfaces;

use DateTime;
use PDOStatement;
use Phoundation\Filesystem\Restrictions;


/**
 * Validator class
 *
 * This class validates data from untrusted arrays
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface DataValidator extends ValidatorBasics
{
    /**
     * Returns if all validations are disabled or not
     *
     * @return bool
     */
    static function disabled(): bool;
    
    /**
     * Disable all validations
     *
     * @return void
     */
    static function disable(): void;

    /**
     * Enable all validations
     *
     * @return void
     */
    static function enable(): void;

    /**
     * Returns if all validations are disabled or not
     *
     * @return bool
     */
    static function passwordsDisabled(): bool;

    /**
     * Disable password validations
     *
     * @return void
     */
    static function disablePasswords(): void;

    /**
     * Enable password validations
     *
     * @return void
     */
    static function enablePasswords(): void;

    /**
     * Allow the validator to check each element in a list of values.
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @return static
     *@see DataValidator::select()
     * @see DataValidator::self()
     */
    function each(): static;

    /**
     * Will let the validator treat the value as a single variable
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @return static
     *@see DataValidator::select()
     * @see DataValidator::each()
     */
    function single(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a boolean
     *
     * @return static
     */
    function isBoolean(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an integer
     *
     * @return static
     */
    function isInteger(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an float
     *
     * @return static
     */
    function isFloat(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is numeric
     *
     * @return static
     */
    function isNumeric(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param bool $allow_zero
     * @return static
     */
    function isPositive(bool $allow_zero = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid natural number (integer, 1 and above)
     *
     * @param bool $allow_zero
     * @return static
     */
    function isNatural(bool $allow_zero = true): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid latitude coordinate
     *
     * @return static
     */
    function isLatitude(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid longitude coordinate
     *
     * @return static
     */
    function isLongitude(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid database id (integer, 1 and above)
     *
     * @param bool $allow_zero
     * @return static
     */
    function isId(bool $allow_zero = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid code
     *
     * @param bool $allow_zero
     * @return static
     */
    function isCode(bool $allow_zero = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @param bool $equal        If true, it is more than or equal to, instead of only more than
     * @return static
     */
    function isMoreThan(int|float $amount, bool $equal = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @param bool $equal        If true, it is less than or equal to, instead of only less than
     * @return static
     */
    function isLessThan(int|float $amount, bool $equal = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is between the two specified amounts
     *
     * @param int|float $minimum
     * @param int|float $maximum
     * @return static
     */
    function isBetween(int|float $minimum, int|float $maximum): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is negative
     *
     * @param bool $allow_zero
     * @return static
     */
    function isNegative(bool $allow_zero = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key contains a currency value
     *
     * @return static
     */
    function isCurrency(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @return static
     */
    function isScalar(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @param array $array
     * @return static
     */
    function isInArray(array $array): static;

    /**
     * Ensures that the value has the specified string
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @param bool $regex
     * @return static
     */
    function contains(string $string, bool $regex = false): static;

    /**
     * Ensures that the value has the specified string
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @param bool $regex
     * @return static
     */
    function containsNot(string $string, bool $regex = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key is the same as the column value in the specified query
     *
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @param bool $ignore_case
     * @return static
     */
    function isQueryColumn(PDOStatement|string $query, ?array $execute = null, bool $ignore_case = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key value contains the column value in the specified query
     *
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @return static
     */
    function containsQueryColumn(PDOStatement|string $query, ?array $execute = null): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @return static
     */
    function inQueryColumns(PDOStatement|string $query, ?array $execute = null): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a string
     *
     * @return static
     */
    function isString(): static;

    /**
     * Validates that the selected field is equal or larger than the specified amount of characters
     *
     * @param int $characters
     * @return static
     */
    function hasCharacters(int $characters): static;

    /**
     * Validates that the selected field is equal or larger than the specified amount of characters
     *
     * @param int $characters
     * @return static
     */
    function hasMinCharacters(int $characters): static;

    /**
     * Validates that the selected field is equal or shorter than the specified amount of characters
     *
     * @param int|null $characters
     * @return static
     */
    function hasMaxCharacters(?int $characters = null): static;

    /**
     * Validates that the selected field matches the specified regex
     *
     * @param string $regex
     * @return static
     */
    function matchesRegex(string $regex): static;

    /**
     * Validates that the selected field NOT matches the specified regex
     *
     * @param string $regex
     * @return static
     */
    function matchesNotRegex(string $regex): static;

    /**
     * Validates that the selected field contains only alphabet characters
     *
     * @return static
     */
    function isAlpha(): static;

    /**
     * Validates that the selected field contains only alphanumeric characters
     *
     * @return static
     */
    function isAlphaNumeric(): static;

    /**
     * Validates that the selected field contains only lowercase letters
     *
     * @return static
     */
    function isLowercase(): static;

    /**
     * Validates that the selected field contains only uppercase letters
     *
     * @return static
     */
    function isUppercase(): static;

    /**
     * Validates that the selected field contains only characters that are printable, but neither letter, digit nor
     * blank
     *
     * @return static
     */
    function isPunct(): static;

    /**
     * Validates that the selected field contains only printable characters (including blanks)
     *
     * @return static
     */
    function isPrintable(): static;

    /**
     * Validates that the selected field contains only printable characters (NO blanks)
     *
     * @return static
     */
    function isGraph(): static;

    /**
     * Validates that the selected field contains only whitespace characters
     *
     * @return static
     */
    function isWhitespace(): static;

    /**
     * Validates that the selected field contains only hexadecimal characters
     *
     * @return static
     */
    function isHexadecimal(): static;

    /**
     * Validates that the selected field contains only octal numbers
     *
     * @return static
     */
    function isOctal(): static;

    /**
     * Validates that the selected field is the specified value
     *
     * @param mixed $validate_value
     * @param bool $strict If true, will perform a strict check
     * @param bool $secret If specified the $validate_value will not be shown
     * @param bool $ignore_case
     * @return static
     * @todo Change these individual flag parameters to one bit flag parameter
     */
    function isValue(mixed $validate_value, bool $strict = false, bool $secret = false, bool $ignore_case = true): static;

    /**
     * Validates that the selected field is a date
     *
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @return static
     */
    function isDate(): static;

    /**
     * Validates that the selected field is a date
     *
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @return static
     */
    function isTime(): static;

    /**
     * Validates that the selected field is a credit card
     *
     *
     * @note Card regexes taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @note From the site: A huge disclaimer: Never depend your code on card regex. The reason behind is simple. Card
     *       issuers carry on adding new card number patterns or removing old ones. You are likely to end up with
     *       maintaining/debugging the regular expressions that way. It’s still fine to use them for visual effects,
     *       like for identifying the card type on the screen.
     * @return static
     */
    function isCreditCard(): static;

    /**
     * Validates that the selected field is a valid mode
     *
     * @return static
     */
    function isMode(): static;

    /**
     * Validates that the selected field is a timezone
     *
     * @return static
     */
    function isTimezone(): static;

    /**
     * Validates that the selected date field is older than the specified date
     *
     * @param DateTime $date_time
     * @return static
     */
    function isOlderThan(DateTime $date_time): static;

    /**
     * Validates that the selected date field is younger than the specified date
     *
     * @param DateTime $date_time
     * @return static
     */
    function isYoungerThan(DateTime $date_time): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an array
     *
     * @return static
     */
    function isArray(): static;

    /**
     * Validates that the selected field array has a minimal amount of elements
     *
     * @param int $count
     * @return static
     */
    function hasElements(int $count): static;

    /**
     * Validates that the selected field array has a minimal amount of elements
     *
     * @param int $count
     * @return static
     */
    function hasMinimumElements(int $count): static;

    /**
     * Validates that the selected field array has a maximum amount of elements
     *
     * @param int $count
     * @return static
     */
    function hasMaximumElements(int $count): static;

    /**
     * Validates if the selected field is a valid email address
     *
     * @return static
     */
    function isHttpMethod(): static;

    /**
     * Validates if the selected field is a valid phone number
     *
     * @return static
     */
    function isPhoneNumber(): static;

    /**
     * Validates if the selected field is a valid multiple phones field
     *
     * @return static
     */
    function isPhoneNumbers(): static;

    /**
     * Validates if the selected field is a valid gender
     *
     * @return static
     */
    function isGender(): static;

    /**
     * Validates if the selected field is a valid name
     *
     * @param int $characters
     * @return static
     */
    function isName(int $characters = 64): static;

    /**
     * Validates if the selected field is a valid word
     *
     * @return static
     */
    function isWord(): static;

    /**
     * Validates if the selected field is a valid variable
     *
     * @return static
     */
    function isVariable(): static;

    /**
     * Validates if the selected field is a valid directory
     *
     * @param string|null $exists_in_path
     * @param Restrictions|array|string|null $restrictions
     * @return static
     */
    function isPath(?string $exists_in_path = null, Restrictions|array|string|null $restrictions = null): static;

    /**
     * Validates if the selected field is a valid directory
     *
     * @param string|bool|null $exists_in_path
     * @param Restrictions|array|string|null $restrictions
     * @return static
     */
    function isDirectory(string|bool $exists_in_path = null, Restrictions|array|string|null $restrictions = null): static;

    /**
     * Validates if the selected field is a valid file
     *
     * @param string|bool $exists_in_path
     * @param Restrictions|array|string|null $restrictions
     * @return static
     */
    function isFile(string|bool $exists_in_path = null, Restrictions|array|string|null $restrictions = null): static;

    /**
     * Validates if the selected field is a valid description
     *
     * @return static
     */
    function isDescription(): static;

    /**
     * Validates if the selected field is a valid password
     *
     * @return static
     */
    function isPassword(): static;

    /**
     * Validates if the selected field is a valid and strong enough password
     *
     * @return static
     */
    function isStrongPassword(): static;

    /**
     * Validates if the selected field is a valid email address
     *
     * @param int $characters
     * @return static
     */
    function isEmail(int $characters = 2048): static;

    /**
     * Validates if the selected field is a valid email address
     *
     * @param int $max_size
     * @return static
     */
    function isUrl(int $max_size = 2048): static;

    /**
     * Validates if the selected field is a valid domain name
     *
     * @return static
     */
    function isDomain(): static;

    /**
     * Validates if the selected field is a valid IP address
     *
     * @return static
     */
    function isIp(): static;

    /**
     * Validates if the selected field is a valid JSON string
     *
     * @copyright The used JSON regex validation taken from a twitter post by @Fish_CTO
     * @return static
     * @see static::isCsv()
     * @see static::isBase58()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeJson()
     */
    function isJson(): static;

    /**
     * Validates if the selected field is a valid CSV string
     *
     * @param string $separator The separation character, defaults to comma
     * @param string $enclosure
     * @param string $escape
     * @return static
     * @see static::isBase58()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeCsv()
     */
    function isCsv(string $separator = ',', string $enclosure = "\"", string $escape = "\\"): static;

    /**
     * Validates if the selected field is a serialized string
     *
     * @return static
     * @see static::isCsv()
     * @see static::isBase58()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeSerialized()
     */
    function isSerialized(): static;

    /**
     * Validates if the selected field is a base58 string
     *
     * @return static
     * @see static::isCsv()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeBase58()
     */
    function isBase58(): static;

    /**
     * Validates if the selected field is a base64 string
     *
     * @return static
     * @see static::isCsv()
     * @see static::isBase58()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeBase64()
     */
    function isBase64(): static;

    /**
     * Sanitize the selected value by trimming whitespace
     *
     * @todo CURRENTLY NOT WORKING, FIX!
     * @param string $characters
     * @return static
     * @see trim()
     */
    function sanitizeTrim(string $characters = "\t\n\r\0\x0B"): static;

    /**
     * Sanitize the selected value by making the entire string uppercase
     *
     * @return static
     * @see static::sanitizeTrim()
     * @see static::sanitizeLowercase()
     */
    function sanitizeUppercase(): static;

    /**
     * Sanitize the selected value by making the entire string lowercase
     *
     * @return static
     * @see static::sanitizeTrim()
     * @see static::sanitizeUppercase()
     */
    function sanitizeLowercase(): static;

    /**
     * Sanitize the selected value with a search / replace
     *
     * @param array $replace A key => value map of all items that should be searched / replaced
     * @param bool $regex If true, all keys in the $replace array will be treated as a regex instead of a normal string
     *                    This is slower and more memory intensive, but more flexible as well.
     * @return static
     * @see trim()
     */
    function sanitizeSearchReplace(array $replace, bool $regex = false): static;

    /**
     * Sanitize the selected value by decoding the JSON
     *
     * @param bool $array If true, will return the data in associative arrays instead of generic objects
     * @return static
     * @see static::isJson()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeForceString()
     */
    function sanitizeDecodeJson(bool $array = true): static;

    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @param string $separator The separation character, defaults to comma
     * @param string $enclosure
     * @param string $escape
     * @return static
     * @see static::isCsv()
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    function sanitizeDecodeCsv(string $separator = ',', string $enclosure = "\"", string $escape = "\\"): static;

    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @return static
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    function sanitizeDecodeSerialized(): static;

    /**
     * Sanitize the selected value by converting it to an array
     *
     * @param string $characters
     * @return static
     * @see trim()
     * @see static::sanitizeForceString()
     */
    function sanitizeForceArray(string $characters = ','): static;

    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @return static
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    function sanitizeDecodeBase58(): static;

    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @return static
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceString()
     */
    function sanitizeDecodeBase64(): static;

    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @return static
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeForceString()
     */
    function sanitizeDecodeUrl(): static;

    /**
     * Sanitize the selected value by making it a string
     *
     * @todo KNOWN BUG: THIS DOESNT WORK
     * @param string $characters
     * @return static
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceArray()
     */
    function sanitizeForceString(string $characters = ','): static;

    /**
     * Selects the specified key within the array that we are validating
     *
     * @param int|string $field The array key (or HTML form field) that needs to be validated / sanitized
     * @return static
     */
    function standardSelect(int|string $field): static;

    /**
     * Force a return of all POST data without check
     *
     * @return array|null
     */
    static function extract(): ?array;

    /**
     * Force a return of a single POST key value
     *
     * @return array
     */
    static function extractKey(string $key): mixed;
}