<?php

/**
 * Trait TraitDataTitle
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentitle.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Html;


trait TraitDataTitle
{
    /**
     * The title to use
     *
     * @var string|null $title
     */
    protected ?string $title = null;


    /**
     * Returns the title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }


    /**
     * Sets the title
     *
     * @param string|null $title
     * @param bool        $make_safe
     *
     * @return static
     */
    public function setTitle(?string $title, bool $make_safe = false): static
    {
        if ($make_safe) {
            $title = Html::safe($title);
        }

        $this->title = get_null($title);
        return $this;
    }
}
