<?php

/**
 * A class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Interfaces\AInterface;
use Phoundation\Web\Html\Enums\EnumAnchorTarget;
use Phoundation\Web\Http\Interfaces\UrlBuilderInterface;
use Phoundation\Web\Http\UrlBuilder;

class A extends Span implements AInterface
{
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
     * Returns the href for this anchor
     *
     * @return string|null
     */
    public function getHref(): ?string
    {
        return $this->attributes->get('href', false);
    }


    /**
     * Sets the href for this anchor
     *
     * @param UrlBuilderInterface|string|null $href
     *
     * @return $this
     */
    public function setHref(UrlBuilderInterface|string|null $href): static
    {
        // Run the href through UrlBuilder to ensure that preconfigured URL's like "sign-out" are converted to full URLs
        $this->attributes->set((string) UrlBuilder::getWww($href), 'href');

        return $this;
    }


    /**
     * Returns the target for this anchor
     *
     * @return EnumAnchorTarget|null
     */
    public function getTarget(): ?EnumAnchorTarget
    {
        return $this->attributes->get('target', false);
    }


    /**
     * Sets the target for this anchor
     *
     * @param EnumAnchorTarget|null $target
     *
     * @return $this
     */
    public function setTarget(?EnumAnchorTarget $target): static
    {
        $this->attributes->set($target, 'target');

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        if ($this->child_element) {
            // Render the parent first and use it as content
            if ($this->content) {
                // This A element already has content, can't have a parent AND content!
                throw new OutOfBoundsException(tr('Cannot render A element, it has child element ":child" and content ":content". It must have one or the other', [
                    ':parent'  => get_class($this->child_element),
                    ':content' => $this->content,
                ]));
            }
            $this->child_element->setAnchor(null);
            $this->content = $this->child_element->render();
        }

        return parent::render();
    }
}