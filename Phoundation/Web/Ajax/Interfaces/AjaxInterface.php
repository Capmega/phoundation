<?php

namespace Phoundation\Web\Ajax\Interfaces;

use Phoundation\Web\Json\Interfaces\JsonInterface;


/**
 * Interface AjaxInterface
 *
 * This class contains methods to assist in building web AJAX APIs
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface AjaxInterface extends JsonInterface
{
    /**
     * Execute the specified AJAX API page
     *
     * @return string|null
     */
    public function execute(): ?string;

    /**
     * Build and send AJAX API specific HTTP headers
     *
     * @param string $output
     * @return void
     */
    public function renderHttpHeaders(string $output): void;
}