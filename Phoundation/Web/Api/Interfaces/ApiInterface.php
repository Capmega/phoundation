<?php

namespace Phoundation\Web\Api\Interfaces;

use Phoundation\Web\Json\Interfaces\JsonInterface;

/**
 * Interface ApiInterface
 *
 * This class contains methods to assist in building web API's
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
interface ApiInterface extends JsonInterface
{
    /**
     * Execute the specified API page
     *
     * @return string|null
     */
    public function execute(): ?string;


    /**
     * Build and send API specific HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    public function renderHttpHeaders(string $output): void;
}