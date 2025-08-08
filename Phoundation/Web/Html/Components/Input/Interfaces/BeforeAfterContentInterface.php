<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;


use Phoundation\Web\Html\Components\Interfaces\RenderInterface;

interface BeforeAfterContentInterface
{
    /**
     * Returns if this input element has after content
     *
     * @return bool
     */
    public function hasAfterContent(): bool;

    /**
     * Returns the modal after_content
     *
     * @return array
     */
    public function getAfterContent(): array;

    /**
     * Sets the modal after_content
     *
     * @param RenderInterface|array|callable|string|null $after_content
     *
     * @return static
     */
    public function setAfterContent(RenderInterface|array|callable|string|null $after_content): static;

    /**
     * Sets the modal after_content
     *
     * @param RenderInterface|array|callable|string|null $after_content
     *
     * @return static
     */
    public function addAfterContent(RenderInterface|array|callable|string|null $after_content): static;

    /**
     * Returns if this input element has before content
     *
     * @return bool
     */
    public function hasBeforeContent(): bool;

    /**
     * Returns the modal before_content
     *
     * @return array
     */
    public function getBeforeContent(): array;

    /**
     * Sets the modal before_content
     *
     * @param RenderInterface|array|callable|string|null $before_content
     *
     * @return static
     */
    public function setBeforeContent(RenderInterface|array|callable|string|null $before_content): static;

    /**
     * Sets the modal before_content
     *
     * @param RenderInterface|array|callable|string|null $before_content
     *
     * @return static
     */
    public function addBeforeContent(RenderInterface|array|callable|string|null $before_content): static;
}
