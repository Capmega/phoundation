<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Servers\Interfaces\ServerInterface;

/**
 * Trait TraitDataServer
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */
trait TraitDataServer
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var ServerInterface|null $server
     */
    protected ?ServerInterface $server = null;


    /**
     * Returns the server servers
     *
     * @return ServerInterface|null
     */
    public function getServer(): ?ServerInterface
    {
        return $this->server;
    }


    /**
     * Returns the server servers
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    public function setServer(?ServerInterface $server): static
    {
        $this->server = $server;

        return $this;
    }
}
