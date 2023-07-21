<?php

namespace Phoundation\Data\Validator\Interfaces;


use DateTime;
use PDOStatement;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use UnitEnum;

/**
 * Validator class
 *
 * This class validates data from untrusted arrays
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface ValidatorInterface extends ValidatorBasicsInterface
{
    /**
     * Returns the integer id for this object or null
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Sets the integer id for this object or null
     *
     * @param int|null $id
     * @return static
     */
    public function setId(?int $id): static;

    /**
     * Returns if failed fields will be cleared on validation
     *
     * @return bool
     */
    public function getClearFailedFields(): bool;

    /**
     * Sets if failed fields will be cleared on validation
     *
     * @param bool $clear_failed_fields
     * @return void
     */
    public function setClearFailedFields(bool $clear_failed_fields): static;

    /**
     * Returns the field prefix value
     *
     * @return string|null
     */
    public function getFieldPrefix(): ?string;


    /**
     * Sets the field prefix value
     *
     * @param string|null $field_prefix
     * @return $this
     */
    public function setFieldPrefix(?string $field_prefix): static;


    /**
     * Returns the table value
     *
     * @return string|null
     */
    public function getTable(): ?string;

    /**
     * Sets the table value
     *
     * @param string|null $table
     * @return $this
     */
    public function setTable(?string $table): static;


    /**
     * Forcibly set the specified key of this validator source to the specified value
     *
     * @param string|float|int $key
     * @param mixed $value
     * @return static
     */
    public function setSourceKey(string|float|int $key, mixed $value): static;

    /**
     * Allow the validator to check each element in a list of values.
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @return static
     * @see DataValidator::select()
     * @see DataValidator::self()
     */
    public function each(): static;

    /**
     * Will let the validator treat the value as a single variable
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @return static
     * @see DataValidator::select()
     * @see DataValidator::each()
     */
    public function single(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a boolean
     *
     * @return static
     */
    public function isBoolean(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an integer
     *
     * @return static
     */
    public function isInteger(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an float
     *
     * @return static
     */
    public function isFloat(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is numeric
     *
     * @return static
     */
    public function isNumeric(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isPositive(bool $allow_zero = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid natural number (integer, 1 and above)
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isNatural(bool $allow_zero = true): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid latitude coordinate
     *
     * @return static
     */
    public function isLatitude(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid longitude coordinate
     *
     * @return static
     */
    public function isLongitude(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid database id (integer, 1 and above)
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isDbId(bool $allow_zero = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a valid code
     *
     * @param string|null $until
     * @return static
     */
    public function isCode(?string $until = null): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @param bool $equal If true, it is more than or equal to, instead of only more than
     * @return static
     */
    public function isMoreThan(int|float $amount, bool $equal = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is positive
     *
     * @param int|float $amount
     * @param bool $equal If true, it is less than or equal to, instead of only less than
     * @return static
     */
    public function isLessThan(int|float $amount, bool $equal = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is between the two specified amounts
     *
     * @param int|float $minimum
     * @param int|float $maximum
     * @return static
     */
    public function isBetween(int|float $minimum, int|float $maximum): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is negative
     *
     * @param bool $allow_zero
     * @return static
     */
    public function isNegative(bool $allow_zero = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key contains a currency value
     *
     * @return static
     */
    public function isCurrency(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @return static
     */
    public function isScalar(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @param array $array
     * @return static
     */
    public function isInArray(array $array): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @param UnitEnum $enum
     * @return static
     */
    public function isInEnum(UnitEnum $enum): static;

    /**
     * Ensures that the value has the specified string
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @param bool $regex
     * @return static
     */
    public function contains(string $string, bool $regex = false): static;

    /**
     * Ensures that the value has the specified string
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @param bool $regex
     * @return static
     */
    public function containsNot(string $string, bool $regex = false): static;

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
    public function isQueryResult(PDOStatement|string $query, ?array $execute = null, bool $ignore_case = false): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key is the same as the column value in the specified query
     *
     * @param string $column
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @param bool $ignore_case
     * @param bool $fail_on_null
     * @return static
     */
    public function setColumnFromQuery(string $column, PDOStatement|string $query, ?array $execute = null, bool $ignore_case = false, bool $fail_on_null = true): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified key value contains the column value in the specified query
     *
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @return static
     */
    public function containsQueryColumn(PDOStatement|string $query, ?array $execute = null): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a scalar value
     *
     * @param PDOStatement|string $query
     * @param array|null $execute
     * @return static
     */
    public function inQueryResultArray(PDOStatement|string $query, ?array $execute = null): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is a string
     *
     * @return static
     */
    public function isString(): static;

    /**
     * Validates that the selected field is equal or larger than the specified amount of characters
     *
     * @param int $characters
     * @return static
     */
    public function hasCharacters(int $characters): static;

    /**
     * Validates that the selected field is equal or larger than the specified amount of characters
     *
     * @param int $characters
     * @return static
     */
    public function hasMinCharacters(int $characters): static;

    /**
     * Validates that the selected field is equal or shorter than the specified amount of characters
     *
     * @param int|null $characters
     * @return static
     */
    public function hasMaxCharacters(?int $characters = null): static;

    /**
     * Validates that the selected field matches the specified regex
     *
     * @param string $regex
     * @return static
     */
    public function matchesRegex(string $regex): static;

    /**
     * Validates that the selected field NOT matches the specified regex
     *
     * @param string $regex
     * @return static
     */
    public function matchesNotRegex(string $regex): static;

    /**
     * Validates that the selected field contains only alphabet characters
     *
     * @return static
     */
    public function isAlpha(): static;

    /**
     * Validates that the selected field contains only alphanumeric characters
     *
     * @return static
     */
    public function isAlphaNumeric(): static;

    /**
     * Validates that the selected field is not a number
     *
     * @return static
     */
    public function isNotNumeric(): static;

    /**
     * Validates that the selected field contains only lowercase letters
     *
     * @return static
     */
    public function isLowercase(): static;

    /**
     * Validates that the selected field contains only uppercase letters
     *
     * @return static
     */
    public function isUppercase(): static;

    /**
     * Validates that the selected field contains only characters that are printable, but neither letter, digit nor
     * blank
     *
     * @return static
     */
    public function isPunct(): static;

    /**
     * Validates that the selected field contains only printable characters (including blanks)
     *
     * @return static
     */
    public function isPrintable(): static;

    /**
     * Validates that the selected field contains only printable characters (NO blanks)
     *
     * @return static
     */
    public function isGraph(): static;

    /**
     * Validates that the selected field contains only whitespace characters
     *
     * @return static
     */
    public function isWhitespace(): static;

    /**
     * Validates that the selected field contains only hexadecimal characters
     *
     * @return static
     */
    public function isHexadecimal(): static;

    /**
     * Validates that the selected field contains only octal numbers
     *
     * @return static
     */
    public function isOctal(): static;

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
    public function isValue(mixed $validate_value, bool $strict = false, bool $secret = false, bool $ignore_case = true): static;

    /**
     * Validates that the selected field is a date
     *
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @return static
     */
    public function isDate(): static;

    /**
     * Validates that the selected field is a date
     *
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @return static
     */
    public function isTime(): static;

    /**
     * Validates that the selected field is a date time field
     *
     * @note Regex taken from https://code.oursky.com/regex-date-currency-and-time-accurate-data-extraction/
     * @return static
     */
    public function isDateTime(): static;

    /**
     * Validates that the selected field is in the past
     *
     * @param DateTime|null $before
     * @return static
     */
    public function isBefore(?DateTime $before = null): static;

    /**
     * Validates that the selected field is in the past
     *
     * @param DateTime|null $after
     * @return static
     */
    public function isAfter(?DateTime $after = null): static;

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
    public function isCreditCard(): static;

    /**
     * Validates that the selected field is a valid mode
     *
     * @return static
     */
    public function isDisplayMode(): static;

    /**
     * Validates that the selected field is a timezone
     *
     * @return static
     */
    public function isTimezone(): static;

    /**
     * Validates the datatype for the selected field
     *
     * This method ensures that the specified array key is an array
     *
     * @return static
     */
    public function isArray(): static;

    /**
     * Validates that the selected field array has a minimal amount of elements
     *
     * @param int $count
     * @return static
     */
    public function hasElements(int $count): static;

    /**
     * Validates that the selected field array has a minimal amount of elements
     *
     * @param int $count
     * @return static
     */
    public function hasMinimumElements(int $count): static;

    /**
     * Validates that the selected field array has a maximum amount of elements
     *
     * @param int $count
     * @return static
     */
    public function hasMaximumElements(int $count): static;

    /**
     * Validates if the selected field is a valid email address
     *
     * @return static
     */
    public function isHttpMethod(): static;

    /**
     * Validates if the selected field is a valid phone number
     *
     * @return static
     */
    public function isPhoneNumber(): static;

    /**
     * Validates if the selected field is a valid multiple phones field
     *
     * @param string $separator
     * @return static
     */
    public function isPhoneNumbers(string $separator = ','): static;

    /**
     * Validates if the selected field is a valid gender
     *
     * @return static
     */
    public function isGender(): static;

    /**
     * Validates if the selected field is a valid name
     *
     * @param int $characters
     * @return static
     */
    public function isName(int $characters = 64): static;

    /**
     * Validates if the selected field is a valid name
     *
     * @param int $characters
     * @return static
     */
    public function isUsername(int $characters = 64): static;

    /**
     * Validates if the selected field is a valid word
     *
     * @return static
     */
    public function isWord(): static;

    /**
     * Validates if the selected field is a valid variable
     *
     * @return static
     */
    public function isVariable(): static;

    /**
     * Validates if the selected field is a valid directory
     *
     * @param string|null $exists_in_path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public function isPath(?string $exists_in_path = null, RestrictionsInterface|array|string|null $restrictions = null): static;

    /**
     * Validates if the selected field is a valid directory
     *
     * @param string|bool|null $exists_in_path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public function isDirectory(string|bool $exists_in_path = null, RestrictionsInterface|array|string|null $restrictions = null): static;

    /**
     * Validates if the selected field is a valid file
     *
     * @param string|bool $exists_in_path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public function isFile(string|bool $exists_in_path = null, RestrictionsInterface|array|string|null $restrictions = null): static;

    /**
     * Validates if the selected field is a valid description
     *
     * @param int $characters
     * @return static
     */
    public function isDescription(int $characters = 16_777_200): static;

    /**
     * Validates if the selected field is a valid password
     *
     * @return static
     */
    public function isPassword(): static;

    /**
     * Validates if the selected field is a valid and strong enough password
     *
     * @return static
     */
    public function isStrongPassword(): static;

    /**
     * Validates if the selected field is a valid email address
     *
     * @param int $characters
     * @return static
     */
    public function isColor(): static;

    /**
     * Validates if the selected field is a valid email address
     *
     * @param int $characters
     * @return static
     */
    public function isEmail(int $characters = 2048): static;

    /**
     * Validates if the selected field is a valid email address
     *
     * @param int $max_size
     * @return static
     */
    public function isUrl(int $max_size = 2048): static;

    /**
     * Validates if the selected field is a valid domain name
     *
     * @return static
     */
    public function isDomain(): static;

    /**
     * Validates if the selected field is a valid IP address
     *
     * @return static
     */
    public function isIp(): static;

    /**
     * Validates if the selected field is a valid JSON string
     *
     * @return static
     * @copyright The used JSON regex validation taken from a twitter post by @Fish_CTO
     * @see static::isCsv()
     * @see static::isBase58()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeJson()
     */
    public function isJson(): static;

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
    public function isCsv(string $separator = ',', string $enclosure = "\"", string $escape = "\\"): static;

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
    public function isSerialized(): static;

    /**
     * Validates if the selected field is a base58 string
     *
     * @return static
     * @see static::isCsv()
     * @see static::isBase64()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeBase58()
     */
    public function isBase58(): static;

    /**
     * Validates if the selected field is a base64 string
     *
     * @return static
     * @see static::isCsv()
     * @see static::isBase58()
     * @see static::isSerialized()
     * @see static::sanitizeDecodeBase64()
     */
    public function isBase64(): static;

    /**
     * Validates if the specified function returns TRUE for this value
     *
     * @param callable $function
     * @param string $failure
     * @return static
     */
    public function isTrue(callable $function, string $failure): static;

    /**
     * Validates if the specified function returns FALSE for this value
     *
     * @param callable $function
     * @param string $failure
     * @return static
     */
    public function isFalse(callable $function, string $failure): static;

    /**
     * Sanitize the selected value by trimming whitespace
     *
     * @param string $characters
     * @return static
     * @see trim()
     */
    public function sanitizeTrim(string $characters = "\t\n\r\0\x0B"): static;

    /**
     * Sanitize the selected value by applying the specified transformation callback
     *
     * @param callable $callback
     * @return static
     */
    public function sanitizeTransform(callable $callback): static;

    /**
     * Sanitize the selected value by starting the value from the specified needle
     *
     * @param string $needle
     * @return static
     * @see String::from()
     * @see Validator::sanitizeUntil()
     * @see Validator::sanitizeFromReverse()
     */
    public function sanitizeFrom(string $needle): static;

    /**
     * Sanitize the selected value by ending the value at the specified needle
     *
     * @param string $needle
     * @return static
     * @see String::until()
     * @see Validator::sanitizeFrom()
     * @see Validator::sanitizeUntilReverse()
     */
    public function sanitizeUntil(string $needle): static;

    /**
     * Sanitize the selected value by starting the value from the specified needle, but starting search from the end of
     * the string
     *
     * @param string $needle
     * @return static
     * @see String::fromReverse()
     * @see Validator::sanitizeFrom()
     * @see Validator::sanitizeUntilReverse()
     */
    public function sanitizeFromReverse(string $needle): static;

    /**
     * Sanitize the selected value by ending the value at the specified needle, but starting search from the end of the
     * string
     *
     * @param string $needle
     * @return static
     * @see String::untilReverse()
     * @see Validator::sanitizeUntil()
     * @see Validator::sanitizeFromReverse()
     */
    public function sanitizeUntilReverse(string $needle): static;

    /**
     * Sanitize the selected value by making the entire string uppercase
     *
     * @return static
     * @see static::sanitizeTrim()
     * @see static::sanitizeLowercase()
     */
    public function sanitizeUppercase(): static;

    /**
     * Sanitize the selected value by making the entire string lowercase
     *
     * @return static
     * @see static::sanitizeTrim()
     * @see static::sanitizeUppercase()
     */
    public function sanitizeLowercase(): static;

    /**
     * Sanitize the selected value with a search / replace
     *
     * @param array $replace A key => value map of all items that should be searched / replaced
     * @param bool $regex If true, all keys in the $replace array will be treated as a regex instead of a normal string
     *                    This is slower and more memory intensive, but more flexible as well.
     * @return static
     * @see trim()
     */
    public function sanitizeSearchReplace(array $replace, bool $regex = false): static;

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
    public function sanitizeDecodeJson(bool $array = true): static;

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
    public function sanitizeDecodeCsv(string $separator = ',', string $enclosure = "\"", string $escape = "\\"): static;

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
    public function sanitizeDecodeSerialized(): static;

    /**
     * Sanitize the selected value by converting it to an array
     *
     * @param string $characters
     * @return static
     * @see trim()
     * @see static::sanitizeForceString()
     */
    public function sanitizeForceArray(string $characters = ','): static;

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
    public function sanitizeDecodeBase58(): static;

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
    public function sanitizeDecodeBase64(): static;

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
    public function sanitizeDecodeUrl(): static;

    /**
     * Sanitize the selected value by making it a string
     *
     * @param string $characters
     * @return static
     * @todo KNOWN BUG: THIS DOESNT WORK
     * @see static::sanitizeDecodeBase58()
     * @see static::sanitizeDecodeBase64()
     * @see static::sanitizeDecodeCsv()
     * @see static::sanitizeDecodeJson()
     * @see static::sanitizeDecodeSerialized()
     * @see static::sanitizeDecodeUrl()
     * @see static::sanitizeForceArray()
     */
    public function sanitizeForceString(string $characters = ','): static;

    /**
     * Sanitize the selected value by decoding the specified CSV
     *
     * @param string|null $pre
     * @param string|null $post
     * @return static
     */
    public function sanitizePrePost(?string $pre, ?string $post): static;

    /**
     * Selects the specified key within the array that we are validating
     *
     * @param int|string $field The array key (or HTML form field) that needs to be validated / sanitized
     * @return static
     */
    public function standardSelect(int|string $field): static;

    /**
     * Returns true if the specified field has failed
     *
     * @param string $field
     * @return bool
     */
    public function fieldHasFailed(string $field): bool;

    /**
     * Returns if the currently selected field failed or not
     *
     * @return bool
     */
    public function getSelectedFieldHasFailed(): bool;

    /**
     * Returns the entire source for this validator object
     *
     * @return array|null
     */
    public function getSource(): ?array;

    /**
     * Returns the value for the specified key, or null if not
     *
     * @return array
     */
    public function getSourceKey(string $key): mixed;

    /**
     * Returns true if the specified key exists
     *
     * @param string $key
     * @return bool
     */
    public function sourceKeyExists(string $key): bool;
}
