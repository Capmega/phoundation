<?php

/**
 * Trait TraitMethodComponent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Web\Traits;

use Phoundation\Web\Html\Components\Interfaces\ComponentInterface;


trait TraitDataComponent
{
    /**
     * Tracks the web HTML component object
     *
     * @var ComponentInterface|null
     */
    protected ?ComponentInterface $o_component = null;


    /**
     * Returns the web HTML component object
     *
     * @return ComponentInterface|null
     */
    public function getComponentObject(): ?ComponentInterface
    {
        return $this->o_component;
    }


    /**
     * Sets the web HTML component object
     *
     * @param ComponentInterface|null $o_component
     *
     * @return static
     */
    public function setComponentObject(?ComponentInterface $o_component): static
    {
        $this->o_component = $o_component;
        return $this;
    }
}
