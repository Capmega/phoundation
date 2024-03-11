<?php

namespace Phoundation\Core;


use Phoundation\Core\Interfaces\RequestInterface;

/**
 * Class Request
 *
 *
 *
 * @author Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license This plugin is developed by, and may only exclusively be used by Medinet or customers with written authorization to do so
 * @copyright Copyright (c) 2024 Medinet <copyright@medinet.ca>
 * @package Phoundation\Web
 */
class Request implements RequestInterface
{
    /**
     * The file that is executed for this request
     *
     * @var string $executed_file
     */
    protected string $executed_file;

    /**
     * The data sent to this executed file
     *
     * @var array|null $data
     */
    protected ?array $data;


    /**
     * Returns the file executed for this request
     *
     * @return string
     */
    public function getExecutedFile(): string
    {
        return $this->executed_file;
    }


    /**
     * Returns the data sent to this executed file
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }


    /**
     * Returns the value for the specified data key, if exist. If not, the default value will be returned
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getDataKey(string $key, mixed $default = null): mixed
    {
        return isset_get($this->data, $default);
    }
}