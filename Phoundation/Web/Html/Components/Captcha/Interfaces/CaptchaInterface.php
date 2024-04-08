<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Captcha\Interfaces;

use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;

/**
 * Interface Captcha
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
interface CaptchaInterface extends ElementsBlockInterface
{
    /**
     * Throws a ValidationFailedException if the captcha has failed
     *
     * @param string|null $response
     * @param string|null $remote_ip
     * @param string|null $secret
     *
     * @return void
     */
    function validateResponse(?string $response, string $remote_ip = null, string $secret = null): void;


    /**
     * Returns true if the token is valid for the specified action
     *
     * @param string|null $response
     * @param string|null $remote_ip
     * @param string|null $secret
     *
     * @return bool
     */
    function isValid(?string $response, string $remote_ip = null, string $secret = null): bool;


    /**
     * Renders and returns the HTML for the google ReCAPTCHA
     *
     * @return string|null
     */
    function render(): ?string;


    /**
     * Returns the script required for this ReCaptcha
     *
     * @return string
     */
    function getScript(): string;
}