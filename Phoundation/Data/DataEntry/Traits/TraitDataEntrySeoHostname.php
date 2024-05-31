<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntryHostnamePort
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntrySeoHostname
{
    /**
     * Returns the SEO hostname for this object
     *
     * @return string|null
     */
    public function getSeoHostname(): ?string
    {
        return $this->getValueTypesafe('string', 'seo_hostname');
    }
}