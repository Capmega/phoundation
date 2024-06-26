<?php

namespace Phoundation\Web\Json\Interfaces;

/**
 * Interface JsonInterface
 *
 * This class contains methods to assist in building web JSON interfaces
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
interface JsonInterface
{
    /**
     * ExecuteExecuteInterface the specified JSON page
     *
     * @return string|null
     */
    public function execute(): ?string;


    /**
     * Build and send JSON specific HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    public function renderHttpHeaders(string $output): void;
}