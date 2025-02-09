<?php

/**
 * Trait TraitDataAction
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataAction
{
    /**
     * @var string|null $action
     */
    protected ?string $action;


    /**
     * Returns the source
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }


    /**
     * Sets the source
     *
     * @param string|null $action
     *
     * @return static
     */
    public function setAction(?string $action): static
    {
        $this->action = get_null($action);

        return $this;
    }
}
