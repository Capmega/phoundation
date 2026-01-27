<?php

/**
 * Trait TraitDataEntryTitle
 *
 * This trait contains methods for DataEntry objects that require a title
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


use Phoundation\Web\Html\Html;

trait TraitDataEntryTitle
{
    /**
     * Returns the title for this object
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getTypesafe('string', 'title');
    }


    /**
     * Sets the title for this object
     *
     * @param string|false|null $title            The title for this object
     * @param bool              $make_safe [true] If true, will make the title safe for use with HTML
     *
     * @return static
     */
    public function setTitle(string|false|null $title, bool $make_safe = true): static
    {
        return $this->set(get_null(get_value_unless_false($this->getTitle(), Html::safe($title, $make_safe))), 'title');
    }
}
