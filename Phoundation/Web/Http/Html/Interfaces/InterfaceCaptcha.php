<?php

namespace Phoundation\Web\Http\Html\Interfaces;


/**
 * Interface Captcha
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface InterfaceCaptcha extends InterfaceElementsBlock
{
    /**
     * Returns true if the token is valid for the specified action
     *
     * @param string $token
     * @param string $action
     * @param float $min_score
     * @return bool
     */
    function tokenIsValid(string $token, string $action, float $min_score = 0.5): bool;

    /**
     * Renders and returns the HTML for the google ReCAPTCHA
     *
     * @return string
     */
    function render(): string;
}