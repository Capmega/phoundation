<?php

declare(strict_types=1);

namespace Phoundation\Geo\Timezones\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


/**
 * Interface TimezoneInterface
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
interface TimezoneInterface extends DataEntryInterface
{
    /**
     * Timezone class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool|null $meta_enabled
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null);

    /**
     * Returns the description for this object
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Sets the description for this object
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static;

    /**
     * Returns the name for this object
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Sets the name for this object
     *
     * @param string|null $name
     * @return static
     */
    public function setName(?string $name): static;
}
