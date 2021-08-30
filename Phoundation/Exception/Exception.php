<?php

namespace Phoundation\Exception;

use RuntimeException;
use Throwable;


/**
 * Class CoreException
 *
 * This is the most basic Exception class
 *
 * @author Sven Olaf Oostenbrink
 * @copyright Sven Olaf Oostenbrink <sven@capmega.com>
 * @package Phoundation\Exception
 */
class Exception extends RuntimeException
{
    /**
     * Exception data, if available
     *
     * @var array
     */
    protected array $data = [];


    /**
     * CoreException constructor.
     *
     * @param string $message
     * @param array $data
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', array $data = [], Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->data = $data;
    }


    /**
     * Return the exception data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * Set the exception data
     *
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
