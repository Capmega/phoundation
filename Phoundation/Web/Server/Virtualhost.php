<?php

/**
 * Class Virtualhost
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Server;

use Phoundation\Data\Traits\TraitDataDomain;
use Phoundation\Data\Traits\TraitDataEnvironment;
use Phoundation\Data\Traits\TraitDataType;
use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Server\Interfaces\VirtualhostInterface;


abstract class Virtualhost implements VirtualhostInterface
{
    use TraitStaticMethodNew;
    use TraitDataDomain;
    use TraitDataEnvironment;
    use TraitDataType {
        setType as protected __setType;
    }


    /**
     * Virtualhost class constructor
     */
    public function __construct()
    {
        $this->domain = 'primary';
        $this->type   = 'web';
    }


    /**
     * Sets the domain type
     *
     * @param string|null $type
     *
     * @return $this
     */
    public function setType(?string $type): static {
        switch ($type) {
            case 'web':
                // no break

            case 'cdn':
                break;

            default:
                throw new OutOfBoundsException(tr('Unknown domain type ":type" specified, must be one of "web" or "cdn".', [
                    ':type' => $type
                ]));
        }

        return $this->__setType($type);
    }


    /**
     * Installs the virtualhost file
     *
     * @return static
     */
    public abstract function installFile(): static;
}
