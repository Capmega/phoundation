<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataDataEntryClass
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataDataEntryClass
{
    /**
     * The data entry class
     *
     * @var string|null $data_entry_class
     */
    protected ?string $data_entry_class = null;


    /**
     * Returns the data entry class
     *
     * @return string
     */
    public function getDataEntryClass(): string
    {
        return $this->data_entry_class;
    }


    /**
     * Sets the data entry class
     *
     * @param string|null $data_entry_class
     *
     * @return static
     */
    public function setDataEntryClass(?string $data_entry_class): static
    {
        $this->data_entry_class = $data_entry_class;
        return $this;
    }
}
