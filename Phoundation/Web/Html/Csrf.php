<?php

/**
 * Class Csrf
 *
 * This class contains the HTTP CSRF handling methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html;

use DateTime;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\CsrfFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;
use Throwable;

class Csrf
{
    /**
     * Adds a CSRF hidden input HTML element to the render string if CSRF is enabled
     *
     * @param string|null $render
     *
     * @return string|null
     */
    public static function addHiddenElement(?string $render): ?string
    {
        return $render . static::getHiddenElement();
    }


    /**
     * Returns a CSRF hidden input HTML element if CSRF is enabled
     *
     * @return string|null
     */
    public static function getHiddenElement(): ?string
    {
        if (Config::getBoolean('security.web.csrf.enabled', true)) {
            return '<input type="hidden" name="__csrf" value="' . Csrf::get() . '">';
        }

        return null;
    }


    /**
     * Initializes a new uncached CSRF code
     *
     * @param string|null $prefix
     * @return string
     */
    public static function init(?string $prefix = null): string
    {
        // Generate CSRF code and cache it
        if (Config::getBoolean('security.web.csrf.strict', true)) {
            static::validateBuffer();

            $csrf = $prefix . Strings::unique('sha256');

            $_SESSION['csrf'][$csrf] = new DateTime();
            $_SESSION['csrf'][$csrf] = $_SESSION['csrf'][$csrf]->getTimestamp();

            Log::warning(tr('Added CSRF code ":code" to session buffer', [':code' => $csrf]));

        } else {
            if (empty($_SESSION['csrf'])) {
                $csrf = static::setStaticCsrf($prefix);

            } elseif (!is_string($_SESSION['csrf'])) {
                // Static CSRF must be a string
                Log::warning(tr('Encountered invalid static CSRF buffer ":code" in session, clearing buffer', [
                    ':code' => $_SESSION['csrf']
                ]));

                $csrf = static::setStaticCsrf($prefix);

            } else {
                $_SESSION['csrf_static_test'] = $_SESSION['csrf'];
                $csrf                         = $_SESSION['csrf'];

                Log::warning(tr('Re-using session CSRF code ":code"', [':code' => $csrf]));
            }

        }

        return  $csrf;
    }


    /**
     * Returns a process wide cached CSRF code that is stored in the Session object
     *
     * @param string|null $prefix
     *
     * @return string|null
     */
    public static function get(?string $prefix = null): ?string
    {
        static $csrf;

        if (!isset($csrf)) {
            $csrf = static::init($prefix);
        }

        return $csrf;
    }


    /**
     * @param string|null $prefix
     *
     * @return string
     */
    protected static function setStaticCsrf(?string $prefix): string
    {
        $_SESSION['csrf'] = $prefix . Strings::unique('sha256');

        Log::warning(tr('Set static CSRF code ":code" for session', [':code' => $_SESSION['csrf']]));

        return $_SESSION['csrf'];
    }


    /**
     * Check that the CSRF is valid
     *
     * @param string|null $csrf
     *
     * @return bool
     * @throws CsrfFailedException
     */
    public static function check(?string $csrf): bool
    {
        try {
            if (!static::validateCsrf($csrf)) {
                // CSRF check not required
                return false;
            }

            if (Request::isRequestType(EnumRequestTypes::ajax)) {
                if (!str_starts_with($csrf, 'ajax_')) {
                    throw CsrfFailedException::new(tr('Specified CSRF ":code" is invalid, should have started with "ajax_"', [
                        ':code' => $csrf,
                    ]))->makeWarning();
                }
            }

            // Execute the CSRF check
            if (Config::getBoolean('security.web.csrf.strict', true)) {
                // Execute a strict CSRF check
                return static::checkStrict($csrf);
            }

            // Execute a static CSRF check
            return static::checkStatic($csrf);

        } catch (CsrfFailedException $e) {
            // CSRF check failed, log $_POST data for analysis, drop $_POST data to ensure it won't be used
            $post = PostValidator::new();

            Log::warning(tr('CSRF check failed with: :e', [
                ':e' => $e->getMessage(),
            ]));

            Log::warning(tr('POST data logged below for security analysis'));
            Log::printr(Arrays::hideSensitive($post->getSource()));

            $post->clear();

            throw $e;
        }
    }


    /**
     * Performs a static CSRF check. Either returns true or throws an exception on failure
     *
     * @param string $csrf
     * @return bool
     */
    protected static function checkStatic(string $csrf): bool
    {
        // Do static CSRF checking
        if (!array_key_exists('csrf_static_test', $_SESSION)) {
            throw CsrfFailedException::new(tr('Session has no static CSRF code available'))->makeWarning();
        }

        if (!is_string($_SESSION['csrf_static_test'])) {
            // The session CSRF code must be a string. Maybe CSRF configuration went from strict to static?
            $code = $_SESSION['csrf_static_test'];
            unset($_SESSION['csrf_static_test']);

            throw CsrfFailedException::new(tr('Session CSRF code ":code" is not valid because it must be a string, acting as if session has no CSRF code available', [
                ':code' => $code,
            ]))->makeWarning();
        }

        if ($csrf === $_SESSION['csrf_static_test']) {
            // Yay, all good to go!
            return true;
        }

        throw CsrfFailedException::new(tr('Specified static CSRF ":code" does not exist', [
            ':code' => $csrf,
        ]))->makeWarning();
    }


    /**
     * Performs a strict CSRF check. Either returns true or throws an exception on failure
     *
     * @param string $csrf
     * @return true
     */
    protected static function checkStrict(string $csrf): true
    {
        // Do strict CSRF checking
        static::validateBuffer();

        if (!array_key_exists($csrf, $_SESSION['csrf'])) {
            throw CsrfFailedException::new(tr('Specified CSRF ":code" does not exist', [
                ':code' => $csrf,
            ]))->makeWarning();
        }

        // Get the code from $_SESSION and delete it, so it won't be used twice
        $timestamp = $_SESSION['csrf'][$csrf];
        $now       = new DateTime();

        // Strict CSRF will reset CSRF key after each page submit
        unset($_SESSION['csrf'][$csrf]);

        // Code timed out?
        if (Config::get('security.web.csrf.timeout', 3600)) {
            if (($timestamp + Config::get('security.web.csrf.timeout')) < $now->getTimestamp()) {
                throw CsrfFailedException::new(tr('Specified CSRF ":code" timed out, removed it from session buffer', [
                    ':code' => $csrf,
                ]))->makeWarning();
            }
        }

        Log::success(tr('Accepted POST CSRF code ":csrf" and removed it from session buffer', [
            ':csrf' => $csrf
        ]));

        return true;
    }


    /**
     * Validates the specified CSRF code. Will return false if no validation was required (Non POST requests, for
     * example), or an exception if the validation failed
     *
     * @param string $csrf
     * @return bool
     */
    protected static function validateCsrf(string $csrf): bool
    {
        if (!Config::get('security.web.csrf.enabled', true)) {
            // CSRF check system has been disabled
            return false;
        }

        if (!Request::isRequestType(EnumRequestTypes::html) and !Request::isRequestType(EnumRequestTypes::admin)) {
            // CSRF only works for HTTP or ADMIN requests
            return false;
        }

        if (!Request::isPostRequestMethod()) {
            // CSRF only works for POST methods
            return false;
        }

        if (!$csrf) {
            throw CsrfFailedException::new(tr('No CSRF code specified'))
                ->makeWarning();
        }

        if (strlen($csrf) > 4096) {
            throw CsrfFailedException::new(tr('Invalid CSRF code specified'))
                ->makeWarning();
        }

        return true;
    }


    /**
     * Ensure that the CSRF buffer isn't running crazy large
     *
     * @return void
     */
    protected static function validateBuffer(): void
    {
        // Avoid people messing around
        $max_count = Config::get('security.web.csrf.buffer.size', 50);

        if (isset($_SESSION['csrf'])) {
            if (!is_array($_SESSION['csrf'])) {
                $_SESSION['csrf'] = [];

                Log::warning(tr('Encountered invalid strict CSRF buffer ":code" in session, clearing buffer', [
                    ':code' => $_SESSION['csrf']
                ]));

            } else {
                // Too many csrf, so too many post requests open. Remove the oldest CSRF code and add a new one
                while (count($_SESSION['csrf']) > $max_count) {
                    $code = array_shift($_SESSION['csrf']);

                    Log::warning(tr('CSRF buffer size ":size" is too large, dropped CSRF code ":code"', [
                        ':size' => (count($_SESSION['csrf']) + 1),
                        ':code' => $code
                    ]));
                }
            }
        }
    }
}
