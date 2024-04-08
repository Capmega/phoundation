<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Servers\Interfaces\ServerInterface;

/**
 * Trait TraitDataTargetServer
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentarget_server.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataTargetServer
{
    /**
     * @var ServerInterface|null $target_server
     */
    protected ?ServerInterface $target_server = null;


    /**
     * Returns the target_server
     *
     * @return ServerInterface|null
     */
    public function getTargetServer(): ?ServerInterface
    {
        return $this->target_server;
    }


    /**
     * Sets the target_server
     *
     * @param ServerInterface|null $target_server
     *
     * @return static
     */
    public function setTargetServer(?ServerInterface $target_server): static
    {
        $this->target_server = $target_server;

        return $this;
    }
}