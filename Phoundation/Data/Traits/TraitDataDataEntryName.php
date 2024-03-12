<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataDataEntryName
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opendata_entry_name.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataDataEntryName
{
    /**
     * The data_entry_name to use
     *
     * @var string|null $data_entry_name
     */
    protected ?string $data_entry_name = null;


    /**
     * Returns the data_entry_name
     *
     * @return string|null
     */
    public function getDataEntryName(): ?string
    {
        return $this->data_entry_name;
    }


    /**
     * Sets the data_entry_name
     *
     * @param string|null $data_entry_name
     * @return static
     */
    public function setDataEntryName(?string $data_entry_name): static
    {
        $this->data_entry_name = $data_entry_name;
        return $this;
    }
}