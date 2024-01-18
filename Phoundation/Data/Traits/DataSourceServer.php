<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Servers\Interfaces\ServerInterface;


/**
 * Trait DataSourceServer
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource_server.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataSourceServer
{
    /**
     * @var ServerInterface|null $source_server
     */
    protected ?ServerInterface $source_server = null;


    /**
     * Returns the source_server
     *
     * @return ServerInterface|null
     */
    public function getSourceServer(): ?ServerInterface
    {
        return $this->source_server;
    }


    /**
     * Sets the source_server
     *
     * @param ServerInterface|null $source_server
     * @return static
     */
    public function setSourceServer(?ServerInterface $source_server): static
    {
        $this->source_server = $source_server;
        return $this;
    }
}