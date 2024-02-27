<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Interfaces\AInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Enums\Interfaces\EnumAnchorTargetInterface;
use Phoundation\Web\Http\Interfaces\UrlBuilderInterface;


/**
 * A class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class A extends Span implements AInterface
{
    /**
     * The parent where this anchor sits around
     *
     * @var ElementInterface|null $parent
     */
    protected ?ElementInterface $parent = null;


    /**
     * Form class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->setElement('a');
    }


    /**
     * Returns the parent for this anchor
     *
     * @return ElementInterface|null
     */
    public function getParent(): ?ElementInterface
    {
        return $this->parent;
    }


    /**
     * Sets the parent for this anchor
     *
     * @param ElementInterface|null $parent
     * @return $this
     */
    public function setParent(?ElementInterface $parent): static
    {
        $this->parent = $parent;
        return $this;
    }


    /**
     * Returns the href for this anchor
     *
     * @return string|null
     */
    public function getHref(): ?string
    {
        return $this->attributes->get('href');
    }


    /**
     * Sets the href for this anchor
     *
     * @param UrlBuilderInterface|string|null $href
     * @return $this
     */
    public function setHref(UrlBuilderInterface|string|null $href): static
    {
        $this->attributes->set((string) $href, 'href');
        return $this;
    }


    /**
     * Returns the target for this anchor
     *
     * @return EnumAnchorTargetInterface|null
     */
    public function getTarget(): ?EnumAnchorTargetInterface
    {
        return $this->attributes->get('target');
    }


    /**
     * Sets the target for this anchor
     *
     * @param EnumAnchorTargetInterface|null $target
     * @return $this
     */
    public function setTarget(?EnumAnchorTargetInterface $target): static
    {
        $this->attributes->set($target, 'target');
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        if ($this->parent) {
            // Render the parent first and use it as content
            if ($this->content) {
                // This A element already has content, can't have a parent AND content!
                throw new OutOfBoundsException(tr('Cannot render A element, it has parent "" and content "". It must have one or the other', [
                    ':parent' => get_class($this->parent),
                    ':conten' => $this->content
                ]));
            }

            $this->content = $this->parent->render();
        }

        return parent::render();
    }
}