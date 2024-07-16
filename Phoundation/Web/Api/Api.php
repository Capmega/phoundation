<?php

/**
 * Class Api
 *
 * This class contains methods to assist in building web pages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Api;

use Phoundation\Web\Api\Interfaces\ApiInterface;
use Phoundation\Web\Json\Json;
use Phoundation\Web\Requests\Response;

class Api extends Json implements ApiInterface
{
    /**
     * Returns a new ApiInterface object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Execute the specified API page
     *
     * @return string|null
     */
    public function execute(): ?string
    {
        return execute();
    }


    /**
     * Build and send API specific HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    public function renderHttpHeaders(string $output): void
    {
        Response::setContentType('application/json');
    }
}
