<?php

/**
 * Trait TraitMethodComponent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
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
    protected ?ComponentInterface $_component = null;


    /**
     * Returns the web HTML component object
     *
     * @return ComponentInterface|null
     */
    public function getComponentObject(): ?ComponentInterface
    {
        return $this->_component;
    }


    /**
     * Sets the web HTML component object
     *
     * @param ComponentInterface|null $_component
     *
     * @return static
     */
    public function setComponentObject(?ComponentInterface $_component): static
    {
        $this->_component = $_component;
        return $this;
    }
}
