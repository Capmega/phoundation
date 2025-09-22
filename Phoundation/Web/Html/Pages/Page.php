<?php

/**
 * Class Page
 *
 * This is the basic Page class from which other pages can be created. It contains functionality to receive GET and
 * POST data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Pages;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Stringable;

class Page extends Template
{
    /**
     * The GET data available to this page
     *
     * @var array $get
     */
    protected array $get;

    /**
     * The POST data available to this page
     *
     * @var array $post
     */
    protected array $post;


    /**
     * Returns the available GET data for this page
     *
     * @return array
     */
    public function getGetData(): array
    {
        if (isset($this->get)) {
            return $this->get;
        }

        return [];
    }


    /**
     * Returns the specified GET key
     *
     * @return array
     */
    public function getGet(Stringable|string|float|int $key, mixed $default = null, bool $exception = true): mixed
    {
        if (isset($this->get)) {
            return array_get_safe($this->get, $key, $default, $exception);
        }

        return $default;
    }


    /**
     * Returns the specified POST key
     *
     * @return array
     */
    public function getPost(Stringable|string|float|int $key, mixed $default = null, bool $exception = true): mixed
    {
        if (isset($this->post)) {
            return array_get_safe($this->post, $key, $default, $exception);
        }

        return $default;
    }


    /**
     * Sets GET data for this page
     *
     * @param array|null $get
     * @return static
     */
    public function setGetData(?array $get): static
    {
        $this->get = $get ?? [];
        return $this;
    }


    /**
     * Returns the available POST data for this page
     *
     * @return array
     */
    public function getPostData(): array
    {
        if (isset($this->post)) {
            return $this->post;
        }

        return [];
    }


    /**
     * Sets POST data for this page
     *
     * @param array|null $post
     * @return static
     */
    public function setPostData(?array $post): static
    {
        $this->post = $post ?? [];
        return $this;
    }


    /**
     * Returns the value="" if the specified key exists in the specified method
     *
     * @param EnumHttpRequestMethod $method
     * @param string                $key
     * @param bool                  $exception
     *
     * @return string|null
     */
    public function getValue(EnumHttpRequestMethod $method, string $key, bool $exception = false): ?string
    {
        $value = $this->get($method, $key, $exception);

        if ($value) {
            return ' value="' . $value . '"';
        }

        return null;
    }


    /**
     * Returns the value="" if the specified key exists in the specified method
     *
     * @param EnumHttpRequestMethod $method
     * @param string                $key
     * @param bool                  $exception
     *
     * @return string|null
     */
    public function get(EnumHttpRequestMethod $method, string $key, bool $exception = false): ?string
    {
        $method = match ($method) {
            EnumHttpRequestMethod::get,
            EnumHttpRequestMethod::post => $method->value,
            default                     => throw new OutOfBoundsException(tr('Unsupported HTTP request method ":method" specified', [
                ':method' => $method
            ])),
        };

        if (empty($this->$method)) {
            $this->$method = [];
        }

        if (array_get_safe($this->$method, $key)) {
            return $this->$method[$key];
        }

        if ($exception) {
            throw new OutOfBoundsException(tr('Cannot return ":method" data key ":key", the key does not exist', [
                ':method' => $method,
                ':key'    => $key
            ]));
        }

        return null;
    }
}
