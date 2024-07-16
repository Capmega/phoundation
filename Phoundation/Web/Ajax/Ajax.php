<?php

/**
 * Class Ajax
 *
 * This class contains methods to assist in building web AJAX APIs
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Ajax;

use Phoundation\Web\Ajax\Interfaces\AjaxInterface;
use Phoundation\Web\Json\Json;
use Phoundation\Web\Requests\Response;

class Ajax extends Json implements AjaxInterface
{
    /**
     * Returns a new AjaxInterface object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Execute the specified AJAX API page
     *
     * @return string|null
     */
    public function execute(): ?string
    {
        return execute();
    }


    /**
     * Build and send AJAX API specific HTTP headers
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
