<?php

/**
 * Trait TraitDataEnvironment
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openenvironment.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataEnvironment
{
    /**
     * The environment to use
     *
     * @var string|null $environment
     */
    protected ?string $environment;


    /**
     * Returns the environment
     *
     * @return string|null
     */
    public function getEnvironment(): ?string
    {
        return $this->environment;
    }


    /**
     * Sets the environment
     *
     * @param string|null $environment
     *
     * @return static
     */
    public function setEnvironment(?string $environment): static
    {
        $this->environment = $environment;

        return $this;
    }
}
