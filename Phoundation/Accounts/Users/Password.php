<?php

/**
 * Class Passwords
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Interfaces\PasswordInterface;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Security\Passwords\Exception\NoPasswordSpecifiedException;
use Phoundation\Security\Passwords\Exception\PasswordTooShortException;
use Phoundation\Security\Passwords\Exception\PasswordWeakException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumInputType;


class Password extends DataEntry implements PasswordInterface
{
    /**
     * DataEntry class constructor
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|DataEntryInterface|string|int|null $identifier = null, ?bool $meta_enabled = null, bool $init = true)
    {
        if (!$identifier) {
            throw new OutOfBoundsException(tr('Cannot instantiate Password object, a valid user ID is required'));
        }

        // TODO Should this constructor not pass all variables to the parent:: call?
        if (User::notExists($identifier)) {
            throw new OutOfBoundsException(tr('Cannot instantiate Password object, the specified user ID ":id" does not exist', [
                ':id' => $identifier,
            ]));
        }

        parent::__construct($identifier, $meta_enabled, $init);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
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
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Returns true if the password is considered secure enough
     *
     * @param string      $password
     * @param string|null $email
     * @param int|null    $id
     *
     * @return string
     */
    public static function testSecurity(string $password, ?string $email = null, ?int $id = null): string
    {
        try {
            $password = trim($password);
            if (static::isWeak($password, $email)) {
                throw new ValidationFailedException(tr('This password is not secure enough'));
            }
            // In setup mode, we won't have database access yet, so these 2 tests may be skipped in that case.
            if (!Core::isState('setup')) {
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

        return $password;
    }


    /**
     * Checks if the specified password is strong enough, throws a PasswordWeakException exception if it is not
     *
     * @param string $password
     *
     * @return void
     */
    public static function checkStrong(string $password): void
    {
        if (!static::isStrong($password)) {
            throw new PasswordWeakException(tr('The specified password is not strong enough. Please specify a password with at least ":count" characters, and contains uppercase, lowercase, numeric, and special characters', [
                ':count' => Config::getInteger('security.password.min-length', 10),
            ]));
        }
    }


    /**
     * Returns true if the password is considered secure enough
     *
     * @param string      $password
     *
     * @return bool
     */
    public static function isStrong(string $password): bool
    {
        $strength = static::getStrength($password, null);
        $strong   = ($strength > Config::getInteger('security.password.strength', 50));

        if (!$strong and Validator::disabled()) {
            Log::warning(tr('Ignoring weak password because validation is disabled'));

            return true;
        }

        return $strong;
    }


    /**
     * Returns true if the password is considered secure enough
     *
     * @param string      $password
     * @param string|null $email
     *
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
     * @param string      $password
     * @param string|null $email
     *
     * @return int
     * @throws NoPasswordSpecifiedException|PasswordTooShortException
     */
    protected static function getStrength(string $password, ?string $email): int
    {
        // Get the length of the password
        $strength = 10;
        $length   = strlen($password);
        if ($length < Config::getInteger('security.password.min-length', 10)) {
            if (!$length) {
                throw new NoPasswordSpecifiedException(tr('No password specified'));
            }
            throw new PasswordTooShortException(tr('Specified password has only ":length" characters, 10 are the required minimum', [
                ':length' => $length,
            ]));
        }
        // Test for email parts
        if ($email) {
            $tests = [
                'user'    => Strings::from($email, '@'),
                'domain'  => Strings::until($email, '@'),
                'ruser'   => strrev(Strings::from($email, '@')),
                'rdomain' => strrev(Strings::until($email, '@')),
            ];
            foreach ($tests as $test) {
                if (str_contains($password, $test)) {
                    // The password contains email parts, either straight or in reverse. Both are not allowed
                    return -1;
                }
            }
        }
        // Check if password is not all a lower case
        if (strtolower($password) === $password) {
            $strength -= 15;
        }
        // Check if password is not all an upper case
        if (strtoupper($password) === $password) {
            $strength -= 15;
        }
        // Bonus for long passwords
        $strength += ($length * 2);
        // Get the number of upper case letters in the password
        preg_match_all('/[A-Z]/', $password, $matches);
        $a = (count($matches[0]) / strlen($password) * 100);
        // Get the number of lower case letters in the password
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
     *
     * @return bool
     */
    protected static function isCompromised(string $password): bool
    {
        return (bool) sql()->get('SELECT `id` FROM `accounts_compromised_passwords` WHERE `password` = :password', [
            ':password' => $password,
        ]);
    }


    /**
     * Returns true if the password is considered secure enough
     *
     * @param int    $id
     * @param string $password
     *
     * @return bool
     * @todo add limiting to 6-12 months, then passwords should be dumped
     */
    protected static function isUsedPreviously(string $password, int $id): bool
    {
        $hash_passwords = sql()->list('SELECT `id` FROM `accounts_old_passwords` WHERE `created_by` = :created_by', [
            ':created_by' => $id,
        ]);
        foreach ($hash_passwords as $hash_password) {
            if (static::compare($id, $password, $hash_password)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns true if the specified password and password hash match
     *
     * @param int    $id
     * @param string $password
     * @param string $hashed_password
     *
     * @return bool
     */
    protected static function compare(int $id, string $password, string $hashed_password): bool
    {
        return password_verify(static::seed($id, $password), $hashed_password);
    }


    /**
     * Returns the password with a seed
     *
     * @param int    $id
     * @param string $password
     *
     * @return string
     */
    protected static function seed(int $id, string $password): string
    {
        return Config::get('security.seed', 'phoundation') . $id . $password;
    }


    /**
     * Returns the hashed version of the password
     *
     * @param int    $id
     * @param string $password
     *
     * @return string
     */
    public static function hash(string $password, int $id): string
    {
        if (!$password) {
            throw new OutOfBoundsException(tr('No password specified'));
        }

        return password_hash(static::seed($id, $password), PASSWORD_BCRYPT, [
            'cost' => Config::get('security.passwords.cost', 10),
        ]);
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
     *
     * @param int $tries
     *
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
                Log::dot();
            } while (($end - $start) < $time);
            $costs[] = $cost;
        }
        Log::cli();

        return (int) Arrays::average($costs);
    }


    /**
     * Returns true if the specified password matches the users password
     *
     * @param int    $id
     * @param string $password_test
     * @param string $password_database
     *
     * @return bool
     */
    public static function match(int $id, string $password_test, string $password_database): bool
    {
        return static::compare($id, $password_test, $password_database);
    }


    /**
     * Sets and returns the field definitions for the data fields in this DataEntry object
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(Definition::new($this, 'current')
                                    ->setRender(true)
                                    ->setVirtual(true)
                                    ->setInputType(EnumInputType::password)
                                    ->setMaxlength(128)
                                    ->setLabel(tr('Current password'))
                                    ->setHelpText(tr('Your current password'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isStrongPassword();
                                    }))
                    ->add(Definition::new($this, 'password')
                                    ->setRender(true)
                                    ->setVirtual(true)
                                    ->setInputType(EnumInputType::password)
                                    ->setMaxlength(128)
                                    ->setLabel(tr('New password'))
                                    ->setHelpText(tr('The new password for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isStrongPassword();
                                    }))
                    ->add(Definition::new($this, 'passwordv')
                                    ->setRender(true)
                                    ->setVirtual(true)
                                    ->setInputType(EnumInputType::password)
                                    ->setMaxlength(128)
                                    ->setLabel(tr('Validate password'))
                                    ->setHelpText(tr('Validate the new password for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isEqualTo('password');
                                    }));
    }
}
