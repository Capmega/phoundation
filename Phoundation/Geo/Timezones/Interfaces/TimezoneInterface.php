<?php

declare(strict_types=1);

namespace Phoundation\Geo\Timezones\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;


interface TimezoneInterface extends DataEntryInterface
{
    /**
     * Timezone class constructor
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = null);


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
     *
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
     *
     * @return static
     */
    public function setName(?string $name): static;
}
