<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Exception\OutOfBoundsException;

/**
 * Trait TraitDataTimeout
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataTimeout
{
    /**
     * The timeout for this object
     *
     * @var int|null $timeout
     */
    protected ?int $timeout = null;


    /**
     * Returns the timeout
     *
     * @return int|null
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }


    /**
     * Sets the timeout
     *
     * @param int|null $timeout
     *
     * @return static
     */
    public function setTimeout(?int $timeout): static
    {
        if (($timeout < 1)) {
            throw new OutOfBoundsException(tr('Invalid timeout ":timeout" specified, it must be a positive integer', [
                ':timeout' => $timeout,
            ]));
        }
        $this->timeout = get_null($timeout);

        return $this;
    }
}
