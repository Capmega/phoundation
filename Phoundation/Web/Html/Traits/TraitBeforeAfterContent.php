<?php

/**
 * Trait TraitBeforeAfterContent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Components\Interfaces\RenderInterface;

trait TraitBeforeAfterContent
{
    /**
     * The content added after the input element
     *
     * @var array
     */
    protected array $after_content = [];

    /**
     * The content added before the input element
     *
     * @var array
     */
    protected array $before_content = [];


    /**
     * Returns if this input element has after content
     *
     * @return bool
     */
    public function hasAfterContent(): bool
    {
        return (bool) count($this->after_content);
    }


    /**
     * Returns the modal after_content
     *
     * @return array
     */
    public function getAfterContent(): array
    {
        return $this->after_content;
    }


    /**
     * Sets the modal after_content
     *
     * @param RenderInterface|array|callable|string|null $after_content
     *
     * @return static
     */
    public function setAfterContent(RenderInterface|array|callable|string|null $after_content): static
    {
        $this->after_content = [];

        if (empty($after_content)) {
            return $this;
        }

        return $this->addAfterContent(function () use ($after_content) {
            return $after_content;
        });
    }


    /**
     * Sets the modal after_content
     *
     * @param RenderInterface|array|callable|string|null $after_content
     *
     * @return static
     */
    public function addAfterContent(RenderInterface|array|callable|string|null $after_content): static
    {
        if ($after_content) {
            if (is_array($after_content)) {
                foreach ($after_content as $content) {
                    $this->addAfterContent(function () use ($content) {
                        return $content;
                    });
                }

                return $this;
            }

            $this->after_content[] = $after_content;
        }

        return $this;
    }


    /**
     * Returns if this input element has before content
     *
     * @return bool
     */
    public function hasBeforeContent(): bool
    {
        return (bool) count($this->before_content);
    }


    /**
     * Returns the modal before_content
     *
     * @return array
     */
    public function getBeforeContent(): array
    {
        return $this->before_content;
    }


    /**
     * Sets the modal before_content
     *
     * @param RenderInterface|array|callable|string|null $before_content
     *
     * @return static
     */
    public function setBeforeContent(RenderInterface|array|callable|string|null $before_content): static
    {
        $this->before_content = [];

        if (empty($before_content)) {
            return $this;
        }

        return $this->addBeforeContent(function () use ($before_content) {
            return $before_content;
        });
    }


    /**
     * Sets the modal before_content
     *
     * @param RenderInterface|array|callable|string|null $before_content
     *
     * @return static
     */
    public function addBeforeContent(RenderInterface|array|callable|string|null $before_content): static
    {
        if ($before_content) {
            if (is_array($before_content)) {
                foreach ($before_content as $content) {
                    $this->addBeforeContent(function () use ($content) {
                        return $content;
                    });
                }

                return $this;
            }

            $this->before_content[] = $before_content;
        }

        return $this;
    }
}
