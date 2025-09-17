<?php

/**
 * Class Passwords
 *
 *
 * @see       https://www.the-art-of-web.com/php/password-strength/
 * @see       https://www.comparitech.com/net-admin/passwords-in-organizations-guide/
 * @see       https://catswhocode.com/password-strength-checker/
 * @see       https://www.openwall.com/john/
 * @todo      Implement support for CrackLib and libpwquality,commands "cracklib-check" and "pwscore"
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Interfaces\PasswordInterface;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Security\Passwords\Exception\NoPasswordSpecifiedException;
use Phoundation\Security\Passwords\Exception\PasswordTooShortException;
use Phoundation\Security\Passwords\Exception\PasswordWeakException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumInputType;


class Password extends DataEntry implements PasswordInterface
{
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
    public static function getEntryName(): string
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
     * Returns true if the definitions of this DataEntry have their own methods
     *
     * @return bool
     */
    public static function requireDefinitionsMethods(): bool
    {
        return false;
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
            // In setup mode, we won't have database access yet, so these 2 Tests may be skipped in that case.
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
                ':count' => config()->getInteger('security.password.min-length', 10),
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
        $strong   = ($strength > config()->getInteger('security.password.strength', 50));

        if (!$strong and Validator::disabled()) {
            Log::warning(ts('Ignoring weak password because validation is disabled'));

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
        $weak     = ($strength < config()->getInteger('security.password.strength', 50));

        if ($weak and Validator::disabled()) {
            Log::warning(ts('Ignoring weak password because validation is disabled'));

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

        if ($length < config()->getInteger('security.password.min-length', 10)) {
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
        $d         = (count($matches[0]) / strlen($password) * 100);
        $strength += (((100 / abs($a - $b - $c - $d))) * 2.5);

        // Get the number of unique chars
        $chars            = str_split($password);
        $num_unique_chars = count(array_unique($chars));
        $strength        += $num_unique_chars * 4;

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

        Log::notice(ts('Password strength is ":strength"', [':strength' => $strength]));
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
        return (bool) sql()->getRow('SELECT `id` FROM `accounts_compromised_passwords` WHERE `password` = :password', [
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
        return config()->get('security.seed', 'phoundation') . $id . $password;
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
            'cost' => config()->get('security.passwords.cost', 10),
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
        $time  = config()->get('security.password.time', 50) / 1000;
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
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(Definition::new('current')
                                      ->setRender(true)
                                      ->setVirtual(true)
                                      ->setInputType(EnumInputType::password)
                                      ->setMaxLength(128)
                                      ->setLabel(tr('Current password'))
                                      ->setHelpText(tr('Your current password'))
                                      ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        $o_validator->isStrongPassword();
                                    }))

                    ->add(DefinitionFactory::newDivider())

                    ->add(Definition::new('password')
                                    ->setRender(true)
                                    ->setVirtual(true)
                                    ->setInputType(EnumInputType::password)
                                    ->setMaxLength(128)
                                    ->setLabel(tr('New password'))
                                    ->setHelpText(tr('The new password for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        $o_validator->isStrongPassword();
                                    }))

                    ->add(Definition::new('passwordv')
                                    ->setRender(true)
                                    ->setVirtual(true)
                                    ->setInputType(EnumInputType::password)
                                    ->setMaxLength(128)
                                    ->setLabel(tr('Validate password'))
                                    ->setHelpText(tr('Validate the new password for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        $o_validator->isEqualTo('password');
                                    }));

        return $this;
    }


    /**
     * Test the password with cracklib
     *
     * @note sudo apt install cracklib-runtime
     *
     * @return static
     */
    protected function testCrackLib(): static
    {
        //            $CRACKLIB = '/path/to/cracklib-check';
        //            $PWSCORE = '/path/to/pwscore';
        //
        //            // prevent UTF-8 characters being stripped by escapeshellarg
        //            setlocale(LC_ALL, 'en_US.utf-8');
        //
        //            $out = [];
        //            $ret = null;
        //
        //            $command = 'echo ' . escapeshellarg($pw) . " | {$CRACKLIB}";
        //
        //            exec($command, $out, $ret);
        //
        //            if ((0 == $ret) && preg_match('/: ([^:]+)$/', $out[0], $regs)) {
        //
        //                [, $msg] = $regs;
        //
        //                switch ($msg) {
        //                    case 'OK':
        //                        if ($score) {
        //                            $command = 'echo ' . escapeshellarg($pw) . " | {$PWSCORE}";
        //                            exec($command, $out, $ret);
        //                            if ((0 == $ret) && is_numeric($out[1])) {
        //                                return (int)$out[1]; // return score
        //                            }
        //                            else {
        //                                return false; // probably OK, but may be too short, or a palindrome
        //                            }
        //                        }
        //                        else {
        //                            return false; // OK
        //                        }
        //                        break;
        //
        //                    default:
        //                        $msg = str_replace('dictionary word', 'common word, name or pattern', $msg);
        //                        return $msg; // not OK - return cracklib message
        //
        //                }
        //
        //            }
        //
        //            return false; // possibly OK

        return $this;
    }


    /**
     * Test the password with pwscore
     *
     * @note sudo apt install libpwquality-tools
     *
     * @return static
     */
    protected function testPwScore(): static
    {
        //            $CRACKLIB = '/path/to/cracklib-check';
        //            $PWSCORE = '/path/to/pwscore';
        //
        //            // prevent UTF-8 characters being stripped by escapeshellarg
        //            setlocale(LC_ALL, 'en_US.utf-8');
        //
        //            $out = [];
        //            $ret = null;
        //
        //            $command = 'echo ' . escapeshellarg($pw) . " | {$CRACKLIB}";
        //
        //            exec($command, $out, $ret);
        //
        //            if ((0 == $ret) && preg_match('/: ([^:]+)$/', $out[0], $regs)) {
        //
        //                [, $msg] = $regs;
        //
        //                switch ($msg) {
        //                    case 'OK':
        //                        if ($score) {
        //                            $command = 'echo ' . escapeshellarg($pw) . " | {$PWSCORE}";
        //                            exec($command, $out, $ret);
        //                            if ((0 == $ret) && is_numeric($out[1])) {
        //                                return (int)$out[1]; // return score
        //                            }
        //                            else {
        //                                return false; // probably OK, but may be too short, or a palindrome
        //                            }
        //                        }
        //                        else {
        //                            return false; // OK
        //                        }
        //                        break;
        //
        //                    default:
        //                        $msg = str_replace('dictionary word', 'common word, name or pattern', $msg);
        //                        return $msg; // not OK - return cracklib message
        //
        //                }
        //
        //            }
        //
        //            return false; // possibly OK

        return $this;
    }
}
