<?php

namespace Phoundation\Accounts;

use Phoundation\Cli\Script;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Class Passwords
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Passwords
{
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
            if (self::isWeak($password, $email)) {
                throw new ValidationFailedException(tr('This password is not secure enough'));
            }

            // In setup mode we won't have database access yet, so these 2 tests may be skipped in that case.
            if (!Core::stateIs('setup')) {
                if (self::isCompromised($password)) {
                    throw new ValidationFailedException(tr('This password has been compromised'));
                }

                if ($email) {
                    if (self::isUsedPreviously($password, $id)) {
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
        $strength = self::getStrength($password, $email);
        $weak     = ($strength < Config::get('security.password.strength', 50));

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

        if($length < 8) {
            if(!$length) {
                Log::warning(tr('No password specified'));
                return -1;
            }

            Log::warning(tr('Specified password has length ":length" which is too short and cannot be accepted', [
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
        $strength += (count($matches[0]) * 2);

        // Get the amount of lower case letters in the password
        preg_match_all('/[a-z]/', $password, $matches);
        $strength += (count($matches[0]) * 2);

        // Get the numbers in the password
        preg_match_all('/[0-9]/', $password, $matches);
        $strength += (count($matches[0]) * 2);

        // Check for special chars
        preg_match_all('/[|!@#$%&*\/=?,;.:\-_+~^\\\]/', $password, $matches);
        $strength += (count($matches[0]) * 2);

        // Get the number of unique chars
        $chars            = str_split($password);
        $num_unique_chars = count(array_unique($chars));

        $strength += $num_unique_chars * 4;

        // Test for same character repeats
        $repeats = Strings::countCharacters($password);
        $count   = (array_pop($repeats) + array_pop($repeats) + array_pop($repeats));

        if (($count / ($length + 3) * 10) >= 3) {
            $strength = $strength - ($strength * ($count / $length));
        } else {
            $strength = $strength + ($strength * ($count / $length));
        }

        // Test for character series
        $series     = Strings::countAlphaNumericSeries($password);
        $percentage = ($series / strlen($password)) * 100;
        $strength  += ((100 - $percentage) / 2);

        // Strength is a number 1 - 100;
        $strength = floor(($strength > 99) ? 99 : $strength);

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
            if (self::compare($id, $password, $hash_password)) {
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

        return password_hash(self::seed($id, $password), PASSWORD_BCRYPT, [
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
        return self::compare($id, $password_test, $password_database);
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
        return password_verify(self::seed($id, $password), $hashed_password);
    }
}