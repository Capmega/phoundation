<?php

namespace Phoundation\Web\Http\Html;

use DateTime;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\WebPage;
use Throwable;



/**
 * Class Csrf
 *
 * This class contains the HTTP CSRF checks
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Csrf
{
    /**
     * Generate a CSRF code and set it in the $_SESSION[csrf] array
     *
     * @param string|null $prefix
     * @return string|null
     */
    function set(?string $prefix = null): ?string
    {
        if (!Config::get('security.csrf.enabled', true)) {
            // CSRF check system has been disabled
            return null;
        }

        if (Core::readRegister('csrf')) {
            return Core::readRegister('csrf');
        }

        /*
         * Avoid people messing around
         */
        if (isset($_SESSION['csrf']) and (count($_SESSION['csrf']) >= Config::get('security.csrf.buffer-size', 25))) {
            // Too many csrf, so too many post requests open. Remove the oldest CSRF code and add a new one
            if (count($_SESSION['csrf']) >= (Config::get('security.csrf.buffer-size', 25) + 5)) {
                // WTF? How did we get WAY, WAY many?? Throw it all away and start over
                unset($_SESSION['csrf']);

            } else {
                array_shift($_SESSION['csrf']);
            }
        }

        $csrf = $prefix . Strings::unique('sha256');

        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = array();
        }

        $_SESSION['csrf'][$csrf] = new DateTime();
        $_SESSION['csrf'][$csrf] = $_SESSION['csrf'][$csrf]->getTimestamp();

        Core::readRegister('csrf', $csrf);
        return $csrf;
    }



    /**
     * Check that the CSRF was valid
     *
     * @return bool
     */
    function check(): bool
    {
        try {
            if (!Config::get('security.csrf.enabled', true)) {
                // CSRF check system has been disabled
                return false;
            }

            if (!Core::getCallType('http') and !Core::getCallType('admin')) {
                // CSRF only works for HTTP or ADMIN requests
                return false;
            }

            if (!empty($core->register['csrf_ok'])) {
                // CSRF check has already been executed for this post, all okay!
                return true;
            }

            if (empty($_POST)) {
                // There is no POST data
                return false;
            }

            if (empty($_POST['csrf'])) {
                throw OutOfBoundsException::new(tr('No CSRF field specified'))->makeWarning();
            }

            if (Core::getCallType('ajax')) {
                if (substr($_POST['csrf'], 0, 5) != 'ajax_') {
                    // Invalid CSRF code is sppokie, don't make this a warning
                    throw OutOfBoundsException::new(tr('Specified CSRF ":code" is invalid'))->makeWarning();
                }
            }

            if (empty($_SESSION['csrf'][$_POST['csrf']])) {
                throw OutOfBoundsException::new(tr('Specified CSRF ":code" does not exist', [
                    ':code' => $_POST['csrf']
                ]))->makeWarning();
            }

            // Get the code from $_SESSION and delete it so it won't be used twice
            $timestamp = $_SESSION['csrf'][$_POST['csrf']];
            $now = new DateTime();

            unset($_SESSION['csrf'][$_POST['csrf']]);

            // Code timed out?
            if (Config::get('security.csrf.timeout', 3600)) {
                if (($timestamp + Config::get('security.csrf.timeout')) < $now->getTimestamp()) {
                    throw OutOfBoundsException::new(tr('Specified CSRF ":code" timed out', [
                        ':code' => $_POST['csrf']
                    ]))->makeWarning();
                }
            }

            if (Core::getCallType('ajax')) {
                // Send new CSRF code with the AJAX return payload
                $core->register['ajax_csrf'] = set_csrf('ajax_');
            }

            return true;

        } catch (Throwable $e) {
            // CSRF check failed, drop $_POST
            foreach ($_POST as $key => $value) {
                if (substr($key, -6, 6) === 'submit') {
                    unset($_POST[$key]);
                }
            }

            Log::warning('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');
            Log::warning(Core::getCallType('http'));
            Log::warning($e);
            WebPage::flash()->add(tr('The form data was too old, please try again'), 'warning');
        }
    }
}