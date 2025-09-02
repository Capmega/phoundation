<?php

/**
 * Trait TraitDataEntryContent
 *
 * This trait contains methods for DataEntry objects that require a content
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Html;


trait TraitDataEntryContent
{
    /**
     * Returns the content for this object
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->getTypesafe('string', 'content');
    }


    /**
     * Sets the content for this object
     *
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     */
    public function setContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static
    {
        return $this->set(get_null(Html::safe($content, $make_safe)), 'content');
    }
}
