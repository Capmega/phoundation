<?php

namespace Phoundation\Web\Interfaces;

use Phoundation\Core\Interfaces\ResponseInterface;


/**
 * Interface WebResponseInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license This plugin is developed by, and may only exclusively be used by Medinet or customers with written authorization to do so
 * @copyright Copyright (c) 2024 Medinet <copyright@medinet.ca>
 * @package Phoundation\Web
 */
interface WebResponseInterface extends ResponseInterface
{
    /**
     * Returns the HTTP code that will be returned to the client
     *
     * @return int
     */
    public function getHttpCode(): int;

    /**
     * Sets the HTTP code that will be returned to the client
     *
     * @param int $code
     * @return $this
     */
    public function setHttpCode(int $code): static;
}