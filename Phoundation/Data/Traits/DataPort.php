<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataPort
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataPort
{
    /**
     * The port for this object
     *
     * @var int|null $port
     */
    protected ?int $port = null;


    /**
     * Returns the port
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }


    /**
     * Sets the port
     *
     * @param int|null $port
     * @return static
     */
    public function setPort(?int $port): static
    {
        if ($port) {
            if (($port < 1) or ($port > 65535)) {
                throw new OutOfBoundsException(tr('Invalid port ":port" specified', [
                    ':port' => $port
                ]));
            }
        }

        $this->port = get_null($port);
        return $this;
    }
}
