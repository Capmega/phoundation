<?php

namespace Phoundation\Api;



use Phoundation\Web\WebPage;

/**
 * Class ApiInterface
 *
 * This class contains methods to assist in building web pages
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class ApiInterface
{
    /**
     * Returns a new ApiInterface object
     *
     * @return ApiInterface
     */
    public static function new(): ApiInterface
    {
        return new ApiInterface();
    }



    /**
     * Execute the specified API page
     *
     * @param string $target
     * @return string|null
     */
    public function execute(string $target): ?string
    {
        include($target);

        $output = '';

        // Get all output buffers and restart buffer
        while(ob_get_level()) {
            $output .= ob_get_contents();
            ob_end_clean();
        }

        ob_start(chunk_size: 4096);

        // Build Template specific HTTP headers
        $this->buildHttpHeaders($output);

        return $output;
    }



    /**
     * Build and send API specific HTTP headers
     *
     * @param string $output
     * @return void
     */
    public function buildHttpHeaders(string $output): void
    {
        WebPage::setContentType('application/json');
    }
}