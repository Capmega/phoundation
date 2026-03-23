<?php

/**
 * Class Csrf
 *
 * This class contains the HTTP CSRF handling methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html;

use DateTime;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\Exception\CsrfValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;


class Csrf
{
    /**
     * Adds a CSRF hidden input HTML element to the render string if CSRF is enabled
     *
     * @param RenderInterface|string|null $render
     *
     * @return string|null
     */
    public static function addHiddenElement(RenderInterface|string|null $render): ?string
    {
        return $render . static::getHiddenElement();
    }


    /**
     * Returns true if configured CSRF is enabled
     *
     * Returns the setting for configuration path "security.web.csrf.enabled"
     *
     * Defaults to true
     *
     * @return bool
     */
    public static function getConfigEnabled(): bool
    {
        return config()->getBoolean('security.web.csrf.enabled', true);
    }


    /**
     * Returns true if configured CSRF incident logging is enabled
     *
     * Returns the setting for configuration path "security.web.csrf.incident"
     *
     * Defaults to true
     *
     * @return bool
     */
    public static function getConfigIncidentEnabled(): bool
    {
        return config()->getBoolean('security.web.csrf.incident', true);
    }


    /**
     * Returns true if configured strict CSRF checking is enabled
     *
     * Returns the setting for configuration path "security.web.csrf.strict"
     *
     * Defaults to true
     *
     * @return bool
     */
    public static function getConfigStrict(): bool
    {
        return config()->getBoolean('security.web.csrf.strict', true);
    }


    /**
     * Returns the configured number of seconds for a CSRF protected request to timeout
     *
     * Returns the setting for configuration path "security.web.csrf.timeout"
     *
     * Defaults to 3600
     *
     * @return int
     */
    public static function getConfigTimeout(): int
    {
        return config()->getPositiveInteger('security.web.csrf.timeout', 3600);
    }


    /**
     * Returns the configured CSRF maximum buffer size
     *
     * Returns the setting for configuration path "security.web.csrf.buffer.size"
     *
     * Defaults to 50
     *
     * @return int
     */
    public static function getConfigBufferSize(): int
    {
        return config()->getPositiveInteger('security.web.csrf.buffer.size', 50);
    }


    /**
     * Returns a CSRF hidden input HTML element if CSRF is enabled
     *
     * @note Will only return the hidden __csrf variable if the specified method is POST (default)
     *
     * @param string $method
     *
     * @return string|null
     */
    public static function getHiddenElement(string $method = 'post'): ?string
    {
        if ($method === 'post') {
            if (Csrf::getConfigEnabled()) {
                return '<input type="hidden" name="__csrf" value="' . Csrf::get() . '">';
            }
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
        if (Csrf::getConfigStrict()) {
            static::validateBuffer();

            $csrf = $prefix . Strings::unique('sha256');

            $_SESSION['csrf'][$csrf] = new DateTime();
            $_SESSION['csrf'][$csrf] = $_SESSION['csrf'][$csrf]->getTimestamp();

            Log::warning(ts('Added CSRF code ":code" to session buffer', [':code' => $csrf]));

        } else {
            if (empty($_SESSION['csrf'])) {
                $csrf = static::setStaticCsrf($prefix);

            } elseif (!is_string($_SESSION['csrf'])) {
                // Static CSRF must be a string
                Log::warning(ts('Encountered invalid static CSRF buffer ":code" in session, clearing buffer', [
                    ':code' => $_SESSION['csrf']
                ]));

                $csrf = static::setStaticCsrf($prefix);

            } else {
                $_SESSION['csrf_static_test'] = $_SESSION['csrf'];
                $csrf                         = $_SESSION['csrf'];

                Log::action(ts('Re-using session CSRF code ":code"', [':code' => $csrf]), 4);
            }

        }

        return  $csrf;
    }


    /**
     * Returns a process wide cached CSRF code stored in the Session object
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
        $_SESSION['csrf']             = $prefix . Strings::unique('sha256');
        $_SESSION['csrf_static_test'] = $_SESSION['csrf'];

        Log::warning(ts('Set static CSRF code ":code" for session', [':code' => $_SESSION['csrf']]));

        return $_SESSION['csrf'];
    }


    /**
     * Check that the CSRF is valid
     *
     * @param string|null $csrf
     *
     * @return bool
     * @throws CsrfValidationFailedException
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
                    throw CsrfValidationFailedException::new(tr('Specified CSRF ":code" is invalid, should have started with "ajax_"', [
                        ':code' => $csrf,
                    ]))->makeWarning();
                }
            }

            // Execute the CSRF check
            if (Csrf::getConfigStrict()) {
                // Execute a strict CSRF check
                return static::checkStrict($csrf);
            }

            // Execute a static CSRF check
            return static::checkStatic($csrf);

        } catch (CsrfValidationFailedException $e) {
            // CSRF check failed, log $_POST data for analysis, drop $_POST data to ensure it will not be used
            $post = PostValidator::new();

            Log::warning(ts('CSRF check failed with: :e', [
                ':e' => $e->getMessage(),
            ]));

            Log::warning(ts('POST data logged below for security analysis'));
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
            throw CsrfValidationFailedException::new(tr('Session has no static CSRF code available'))->makeWarning();
        }

        if (!is_string($_SESSION['csrf_static_test'])) {
            // The session CSRF code must be a string. Maybe CSRF configuration went from strict to static?
            $code = $_SESSION['csrf_static_test'];
            unset($_SESSION['csrf_static_test']);

            throw CsrfValidationFailedException::new(tr('Session CSRF code ":code" is not valid because it must be a string, acting as if session has no CSRF code available', [
                ':code' => $code,
            ]))->makeWarning();
        }

        if ($csrf === $_SESSION['csrf_static_test']) {
            // Yay, all good to go!
            return true;
        }

        throw CsrfValidationFailedException::new(tr('Specified static CSRF ":code" does not match the current session CSRF ":session"', [
            ':code'    => $csrf,
            ':session' => $_SESSION['csrf_static_test'],
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
            throw CsrfValidationFailedException::new(tr('Specified CSRF ":code" does not exist', [
                ':code' => $csrf,
            ]))->makeWarning();
        }

        // Get the code from $_SESSION and delete it, so it will not be used twice
        $timestamp = $_SESSION['csrf'][$csrf];
        $now       = new DateTime();

        // Strict CSRF will reset CSRF key after each page submit
        unset($_SESSION['csrf'][$csrf]);

        // Code timed out?
        if (Csrf::getConfigTimeout()) {
            if (($timestamp + Csrf::getConfigTimeout()) < $now->getTimestamp()) {
                throw CsrfValidationFailedException::new(tr('Specified CSRF ":code" timed out, removed it from session buffer', [
                    ':code' => $csrf,
                ]))->makeWarning();
            }
        }

        Log::success(ts('Accepted POST CSRF code ":csrf" and removed it from session buffer', [
            ':csrf' => $csrf
        ]));

        return true;
    }


    /**
     * Validates the specified CSRF code. Will return false if no validation was required (Non POST requests, for
     * example), or an exception if the validation failed
     *
     * @param string|null $csrf
     * @return bool
     */
    protected static function validateCsrf(?string $csrf): bool
    {
        if (!Csrf::getConfigEnabled()) {
            // CSRF check system has been disabled
            return false;
        }

        if (!Request::isRequestType(EnumRequestTypes::html)) {
            // CSRF only works for HTML request types
            return false;
        }

        if (!Request::isPostRequestMethod()) {
            // CSRF only works for POST methods
            return false;
        }

        if (!$csrf) {
            throw CsrfValidationFailedException::new(tr('No CSRF code specified'))->makeWarning();
        }

        if (strlen($csrf) > 4096) {
            throw CsrfValidationFailedException::new(tr('Invalid CSRF code specified'))->makeWarning();
        }

        return true;
    }


    /**
     * Ensure that the CSRF buffer  is not running crazy large
     *
     * @return void
     */
    protected static function validateBuffer(): void
    {
        // Avoid people messing around
        $max_count = Csrf::getConfigBufferSize();

        if (isset($_SESSION['csrf'])) {
            if (!is_array($_SESSION['csrf'])) {
                $_SESSION['csrf'] = [];

                Log::warning(ts('Encountered invalid strict CSRF buffer ":code" in session, clearing buffer', [
                    ':code' => $_SESSION['csrf']
                ]));

            } else {
                // Too many csrf, so too many post requests open. Remove the oldest CSRF code and add a new one
                while (count($_SESSION['csrf']) > $max_count) {
                    $code = array_shift($_SESSION['csrf']);

                    Log::warning(ts('CSRF buffer size ":size" is too large, dropped CSRF code ":code"', [
                        ':size' => (count($_SESSION['csrf']) + 1),
                        ':code' => $code
                    ]));
                }
            }
        }
    }
}
