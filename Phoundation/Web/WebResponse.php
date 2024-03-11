<?php

namespace Phoundation\Web;

use Phoundation\Core\Response;
use Phoundation\Web\Interfaces\WebResponseInterface;


/**
 * Class WebResponse
 *
 *
 *
 * @author Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license This plugin is developed by, and may only exclusively be used by Medinet or customers with written authorization to do so
 * @copyright Copyright (c) 2024 Medinet <copyright@medinet.ca>
 * @package Phoundation\Web
 */
class WebResponse extends Response implements WebResponseInterface
{
    /**
     * Tracks the HTTP code sent to the client
     *
     * @var int $http_code
     */
    protected int $http_code;


    /**
     * Returns the HTTP code that will be returned to the client
     *
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->http_code;
    }


    /**
     * Sets the HTTP code that will be returned to the client
     *
     * @param int $code
     * @return $this
     */
    public function setHttpCode(int $code): static
    {
        $this->http_code = $code;
        return $this;
    }
}