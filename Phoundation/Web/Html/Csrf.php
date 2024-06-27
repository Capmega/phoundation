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
     * @param string $render
     *
     * @return string
     */
    public static function addHiddenElement(string $render): string
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
        $csrf = Csrf::get();

        if ($csrf) {
            return '<input type="hidden" name="__csrf" value="' . $csrf . '">';
        }

        return null;
    }


    /**
     * Generates a single CSRF code per page load, stores it in the Session object, and returns it
     *
     * @param string|null $prefix
     *
     * @return string|null
     */
    public static function get(?string $prefix = null): ?string
    {
        static $csrf;

        if (!isset($csrf)) {
            // Generate CSRF code and cache it
            if (!Config::get('security.web.csrf.enabled', true)) {
                // CSRF check system has been disabled
                Log::warning('Not generating requested CSRF, CSRF disabled in configuration setting "security.web.csrf.enabled"');
                $csrf = '';
                return null;
            }

            static::validateBuffer();

            $csrf = $prefix . Strings::unique('sha256');

            if (empty($_SESSION['csrf'])) {
                $_SESSION['csrf'] = [];
            }

            $_SESSION['csrf'][$csrf] = new DateTime();
            $_SESSION['csrf'][$csrf] = $_SESSION['csrf'][$csrf]->getTimestamp();

            Log::warning(tr('Added CSRF code ":code" to session buffer', [':code' => $csrf]));
        }

        return $csrf;
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

            if (Request::isRequestType(EnumRequestTypes::ajax)) {
                if (!str_starts_with($csrf, 'ajax_')) {
                    // Invalid CSRF code is sppokie, don't make this a warning
                    throw CsrfFailedException::new(tr('Specified CSRF ":code" is invalid', [
                        ':code' => $csrf,
                    ]))->makeWarning();
                }
            }

            static::validateBuffer();

            if (!array_key_exists($csrf, $_SESSION['csrf'])) {
                throw CsrfFailedException::new(tr('Specified CSRF ":code" does not exist', [
                    ':code' => $csrf,
                ]))->makeWarning();
            }

            // Get the code from $_SESSION and delete it, so it won't be used twice
            $timestamp = $_SESSION['csrf'][$csrf];
            $now       = new DateTime();

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

        } catch (CsrfFailedException $e) {
            // CSRF check failed, drop $_POST data to ensure it won't be used
            PostValidator::new()->clear();

            throw $e;
        }
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
