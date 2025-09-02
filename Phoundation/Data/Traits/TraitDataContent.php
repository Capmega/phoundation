<?php

/**
 * Trait TraitDataContent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opencontent.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Components\Interfaces\ElementAttributesInterface;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Html;


trait TraitDataContent
{
    /**
     * The content to use
     *
     * @var RenderInterface|string|float|int|null $content
     */
    protected RenderInterface|string|float|int|null $content = null;


    /**
     * Appends the specified content to the content of the element
     *
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     */
    public function appendContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static
    {
        $this->content = $this->content . Html::safe($content, $make_safe);
        return $this;
    }


    /**
     * Prepends the specified content to the content of the element
     *
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     */
    public function prependContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static
    {
        $this->content = Html::safe($content, $make_safe) . $this->content;
        return $this;
    }


    /**
     * Returns the content of the element to display
     *
     * @return RenderInterface|string|float|int|null
     */
    public function getContent(): RenderInterface|string|float|int|null
    {
        if ($this->content) {
            return $this->content;
        }

        if ($this instanceof ElementAttributesInterface) {
            return $this->null_display;
        }

        return null;
    }


    /**
     * Sets the content of the element
     *
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     */
    public function setContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static
    {
        $this->content = get_null(Html::safe($content, $make_safe));
        return $this;
    }
}
