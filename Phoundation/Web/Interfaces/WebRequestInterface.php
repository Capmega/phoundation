<?php

namespace Phoundation\Web\Interfaces;

use Phoundation\Core\Interfaces\RequestInterface;


/**
 * Interface WebRequestInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license This plugin is developed by, and may only exclusively be used by Medinet or customers with written authorization to do so
 * @copyright Copyright (c) 2024 Medinet <copyright@medinet.ca>
 * @package Phoundation\Web
 */
interface WebRequestInterface extends RequestInterface
{
    /**
     * Returns the file executed for this request
     *
     * @return string
     */
    public function getExecutedFile(): string;

    /**
     * Returns the file executed for this request
     *
     * @return bool
     */
    public function getMainContentsOnly(): bool;

    /**
     * Returns the data sent to this executed file
     *
     * @return array|null
     */
    public function getData(): ?array;

    /**
     * Returns the value for the specified data key, if exist. If not, the default value will be returned
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getDataKey(string $key, mixed $default = null): mixed;
}