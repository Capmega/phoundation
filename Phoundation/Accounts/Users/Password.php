<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Interfaces\PasswordInterface;
use Phoundation\Cli\Script;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Enums\InputType;


/**
 * Class Passwords
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Password extends DataEntry implements PasswordInterface
{
    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_users';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Password');
    }


    /**
     * DataEntry class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null)
    {
        if (!$identifier) {
            throw new OutOfBoundsException(tr('Cannot instantiate Password object, a valid user ID is required'));
        }

        if (User::notExists('id', $identifier)) {
            throw new OutOfBoundsException(tr('Cannot instantiate Password object, the specified user ID ":id" does not exist', [
                ':id' => $identifier
            ]));
        }

        parent::__construct();
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return null;
    }


    /**
     * Returns true if the password is considered secure enough
     *
     * @param string $password
     * @param string|null $email
     * @param int|null $id
     * @return void
     */
    public static function testSecurity(string $password, ?string $email = null, ?int $id = null): void
    {
        try {
            if (static::isWeak($password, $email)) {
                throw new ValidationFailedException(tr('This password is not secure enough'));
            }

            // In setup mode we won't have database access yet, so these 2 tests may be skipped in that case.
            if (!Core::stateIs('setup')) {
                if (static::isCompromised($password)) {
                    throw new ValidationFailedException(tr('This password has been compromised'));
                }

                if ($email) {
                    if (static::isUsedPreviously($password, $id)) {
                        throw new ValidationFailedException(tr('This password has been used before'));
                    }
                }
            }
        } catch (ValidationFailedException $e) {
            if (!Validator::disabled()) {
                throw $e;
            }
        }
    }


    /**
     * Returns true if the password is considered secure enough
     *
     * @param string $password
     * @param string|null $email
     * @return bool
     */
    protected static function isWeak(string $password, ?string $email): bool
    {
        $strength = static::getStrength($password, $email);
        $weak     = ($strength < Config::getInteger('security.password.strength', 50));

        if ($weak and Validator::disabled()) {
            Log::warning(tr('Ignoring weak password because validation is disabled'));
            return false;
        }

        return $weak;
    }


    /**
     * Returns true if the password is considered secure enough
     *
     * @param string $password
     * @param string|null $email
     * @return int
     */
    protected static function getStrength(string $password, ?string $email): int
    {
        // Get the length of the password
        $strength = 10;
        $length   = strlen($password);

        if($length < 10) {
            if(!$length) {
                Log::warning(tr('No password specified'));
                return -1;
            }

            Log::warning(tr('Specified password has only ":length" characters, 10 are the required minimum', [
                ':length' => $length
            ]));

            return -1;
        }

        // Test for email parts
        if ($email) {
            $tests = [
                'user'    => Strings::from($email, '@'),
                'domain'  => Strings::until($email, '@'),
                'ruser'   => strrev(Strings::from($email, '@')),
                'rdomain' => strrev(Strings::until($email, '@'))
            ];

            foreach ($tests as $test) {
                if (str_contains($password, $test)) {
                    // password contains email parts, either straight or in reverse. Both are not allowed
                    return -1;
                }
            }
        }

        // Check if password is not all lower case
        if(strtolower($password) === $password){
            $strength -= 15;
        }

        // Check if password is not all upper case
        if(strtoupper($password) === $password){
            $strength -= 15;
        }

        // Bonus for long passwords
        $strength += ($length * 2);

        // Get the amount of upper case letters in the password
        preg_match_all('/[A-Z]/', $password, $matches);
        $a = (count($matches[0]) / strlen($password) * 100);

        // Get the amount of lower case letters in the password
        preg_match_all('/[a-z]/', $password, $matches);
        $b = (count($matches[0]) / strlen($password) * 100);

        // Get the numbers in the password
        preg_match_all('/[0-9]/', $password, $matches);
        $c = (count($matches[0]) / strlen($password) * 100);

        // Check for special chars
        preg_match_all('/[<>\[\](){}|!@#$%&*\/=?,;.:\-_+~^\\\]/', $password, $matches);
        $d = (count($matches[0]) / strlen($password) * 100);

        $strength += (((100 / abs($a - $b - $c - $d))) * 2.5);

        // Get the number of unique chars
        $chars            = str_split($password);
        $num_unique_chars = count(array_unique($chars));

        $strength += $num_unique_chars * 4;

        // Test for same character repeats
        $repeats = Strings::countCharacters($password);
        $count   = (array_pop($repeats) + array_pop($repeats) + array_pop($repeats));

        if ((($count / ($length + 3)) * 10) >= 5) {
            // Too many same characters repeated, this counts against the strength
            $strength = $strength - ($strength * ($count / $length));

        } else {
            // Few same characters repeated, this counts for the strength
            $strength = $strength + ($strength * ($count / $length) * 2);
        }

// TODO Improve this
//        // Test for character series
//        $series     = Strings::countAlphaNumericSeries($password);
//        $percentage = ($series / strlen($password)) * 100;
//        $strength  -= ((100 - $percentage) / 2);

        // Strength is a number 1 - 100;
        $strength = (int) floor(($strength > 99) ? 99 : floor(($strength < 0) ? 0 : $strength));

        if (VERBOSE) {
            Log::notice(tr('Password strength is ":strength"', [':strength' => $strength]));
        }

        return $strength;
    }


    /**
     * Returns true if the password is considered secure enough
     *
     * @param string $password
     * @return bool
     */
    protected static function isCompromised(string $password): bool
    {
        return (bool) sql()->get('SELECT `id` FROM `accounts_compromised_passwords` WHERE `password` = :password', [
            ':password' => $password
        ]);
    }


    /**
     * Returns true if the password is considered secure enough
     *
     * @param int $id
     * @param string $password
     * @return bool
     * @todo add limiting to 6-12 months, then passwords should be dumped
     */
    protected static function isUsedPreviously(string $password, int $id): bool
    {
        $hash_passwords = sql()->list('SELECT `id` FROM `accounts_old_passwords` WHERE `created_by` = :created_by', [
            ':created_by' => $id
        ]);

        foreach ($hash_passwords as $hash_password) {
            if (static::compare($id, $password, $hash_password)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns the hashed version of the password
     *
     * @param int $id
     * @param string $password
     * @return string
     */
    public static function hash(string $password, int $id): string
    {
        if (!$password) {
            throw new OutOfBoundsException(tr('No password specified'));
        }

        return password_hash(static::seed($id, $password), PASSWORD_BCRYPT, [
            'cost' => Config::get('security.passwords.cost', 10)
        ]);
    }


    /**
     * Returns the password with a seed
     *
     * @param int $id
     * @param string $password
     * @return string
     */
    protected static function seed(int $id, string $password): string
    {
        return Config::get('security.seed', 'phoundation') . $id . $password;
    }


    /**
     * Returns the best encryption cost available on this machine
     *
     * This code will benchmark your server to determine how high of a cost you can afford. You want to set the highest
     * cost that you can without slowing down you server too much. 8-10 is a good baseline, and more is good if your
     * servers are fast enough. The code below aims for ≤ 50 milliseconds stretching time, which is a good baseline for
     * systems handling interactive logins.
     *
     * @note Taken from https://www.php.net/manual/en/function.password-hash.php and modified by Sven Olaf Oostenbrink
     * @param int $tries
     * @return int
     */
    public static function findBestEncryptionCost(int $tries = 20): int
    {
        $time  = Config::get('security.password.time', 50) / 1000;
        $costs = [];
        $try   = 0;

        while (++$try < $tries) {
            $cost = 3;

            do {
                $cost++;
                $start = microtime(true);
                password_hash('test', PASSWORD_BCRYPT, ['cost' => $cost]);
                $end = microtime(true);
                Script::dot();
            } while (($end - $start) < $time);

            $costs[] = $cost;
        }

        Log::cli();

        return (int) Arrays::average($costs);
    }


    /**
     * Returns true if the specified password matches the users password
     *
     * @param int $id
     * @param string $password_test
     * @param string $password_database
     * @return bool
     */
    public static function match(int $id, string $password_test, string $password_database): bool
    {
        return static::compare($id, $password_test, $password_database);
    }


    /**
     * Returns true if the specified password and password hash match
     *
     * @param int $id
     * @param string $password
     * @param string $hashed_password
     * @return bool
     */
    protected static function compare(int $id, string $password, string $hashed_password): bool
    {
        return password_verify(static::seed($id, $password), $hashed_password);
    }


    /**
     * Sets and returns the field definitions for the data fields in this DataEntry object
     *
     * Format:
     *
     * [
     *   field => [key => value],
     *   field => [key => value],
     *   field => [key => value],
     * ]
     *
     * "field" should be the database table column name
     *
     * Field keys:
     *
     * FIELD          DATATYPE           DEFAULT VALUE  DESCRIPTION
     * value          mixed              null           The value for this entry
     * visible        boolean            true           If false, this key will not be shown on web, and be readonly
     * virtual        boolean            false          If true, this key will be visible and can be modified but it
     *                                                  won't exist in database. It instead will be used to generate
     *                                                  a different field
     * element        string|null        "input"        Type of element, input, select, or text or callable function
     * type           string|null        "text"         Type of input element, if element is "input"
     * readonly       boolean            false          If true, will make the input element readonly
     * disabled       boolean            false          If true, the field will be displayed as disabled
     * label          string|null        null           If specified, will show a description label in HTML
     * size           int [1-12]         12             The HTML boilerplate column size, 1 - 12 (12 being the whole
     *                                                  row)
     * source         array|string|null  null           Array or query source to get contents for select, or single
     *                                                  value for text inputs
     * execute        array|null         null           Bound execution variables if specified "source" is a query
     *                                                  string
     * complete       array|bool|null    null           If defined must be bool or contain array with key "noword"
     *                                                  and "word". each key must contain a callable function that
     *                                                  returns an array with possible words for shell auto
     *                                                  completion. If bool, the system will generate this array
     *                                                  automatically from the rows for this field
     * cli            string|null        null           If set, defines the alternative column name definitions for
     *                                                  use with CLI. For example, the column may be name, whilst
     *                                                  the cli column name may be "-n,--name"
     * optional       boolean            false          If true, the field is optional and may be left empty
     * title          string|null        null           The title attribute which may be used for tooltips
     * placeholder    string|null        null           The placeholder attribute which typically shows an example
     * maxlength      string|null        null           The maxlength attribute which typically shows an example
     * pattern        string|null        null           The pattern the value content should match in browser client
     * min            string|null        null           The minimum amount for numeric inputs
     * max            string|null        null           The maximum amount for numeric inputs
     * step           string|null        null           The up / down step for numeric inputs
     * default        mixed              null           If "value" for entry is null, then default will be used
     * null_disabled  boolean            false          If "value" for entry is null, then use this for "disabled"
     * null_readonly  boolean            false          If "value" for entry is null, then use this for "readonly"
     * null_type      boolean            false          If "value" for entry is null, then use this for "type"
     *
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new($this, 'current')
                ->setVisible(true)
                ->setVirtual(true)
                ->setInputType(InputType::password)
                ->setMaxlength(128)
                ->setHelpText(tr('Your current password'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isStrongPassword();
                }))
            ->addDefinition(Definition::new($this, 'password')
                ->setVisible(true)
                ->setVirtual(true)
                ->setInputType(InputType::password)
                ->setMaxlength(128)
                ->setHelpText(tr('The password for this user'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isStrongPassword();
                }))
            ->addDefinition(Definition::new($this, 'passwordv')
                ->setVisible(true)
                ->setVirtual(true)
                ->setInputType(InputType::password)
                ->setMaxlength(128)
                ->setHelpText(tr('Validate the password for this user'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isEqualTo('password');
                }));
    }
}