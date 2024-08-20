<?php

/**
 * Trait TraitDataDescription
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opendescription.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Html;

trait TraitDataDescription
{
    /**
     * The description to use
     *
     * @var string|null $description
     */
    protected ?string $description = null;


    /**
     * Returns the description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }


    /**
     * Sets the description
     *
     * @param string|null $description
     * @param bool        $make_safe
     *
     * @return static
     */
    public function setDescription(?string $description, bool $make_safe = true): static
    {
        if ($make_safe) {
            $description = Html::safe($description);
        }
        $this->description = $description;

        return $this;
    }
}
