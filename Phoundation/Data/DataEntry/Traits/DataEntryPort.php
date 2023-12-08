<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


use Phoundation\Seo\Seo;


/**
 * Trait DataEntryHostnamePort
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryPort
{
    /**
     * Returns the port for this object
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->getSourceFieldValue('int', 'port');
    }


    /**
     * Sets the port for this object
     *
     * @param int|null $port
     * @return static
     */
    public function setPort(?int $port): static
    {
        return $this->setSourceValue('port', $port);
    }
}