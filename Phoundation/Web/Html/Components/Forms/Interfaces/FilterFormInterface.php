<?php

namespace Phoundation\Web\Html\Components\Forms\Interfaces;

use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;
use ReturnTypeWillChange;
use Stringable;


interface FilterFormInterface extends DataEntryFormInterface
{
    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): mixed;

    /**
     * Returns the date range mounting id
     *
     * @return string|null
     */
    public function getDateRangeSelector(): ?string;


    /**
     * Sets the date range mounting id
     *
     * @param string|null $selector
     *
     * @return static
     */
    public function setDateRangeSelector(?string $selector): static;


    /**
     * Returns the date range default value
     *
     * @return array|null
     */
    public function getDateRangeDefault(): ?array;


    /**
     * Returns the date range default value
     *
     * @param array|string $date_range_default
     *
     * @return static
     */
    public function setDateRangeDefault(array|string $date_range_default): static;


    /**
     * Returns the date range
     *
     * @return string|null
     */
    public function getDateRange(): ?string;


    /**
     * Returns the start date, if selected
     *
     * @param string|null $timezone
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStartDateObject(?string $timezone = 'user'): ?PhoDateTimeInterface;


    /**
     * Returns the stop date, if selected
     *
     * @param string|null $timezone
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStopDateObject(?string $timezone = 'user'): ?PhoDateTimeInterface;


    /**
     * Returns the filtered users_id
     *
     * @return int|null
     */
    public function getUsersId(): ?int;


    /**
     * Returns the filtered status
     *
     * @note This method is one of the very few object::getStatus() methods that might return FALSE. The reason for that
     *       is that "not selected" would normally return NULL, but status NULL actually (mostly) means "normal". So
     *       here, FALSE means "do not filter", NULL means "filter on status NULL", and any string means "Filter on this
     *       string"
     *
     * @return string|false|null
     */
    public function getStatus(): string|false|null;

    /**
     * Returns the start date, if available
     *
     * @return string|null
     */
    public function getStartDate(): ?string;

    /**
     * Returns the stop date, if available
     *
     * @return string|null
     */
    public function getStopDate(): ?string;

    /**
     * Sets all render definitions in one go
     *
     * @param array $definitions
     *
     * @return $this
     */
    public function setRenderDefinitions(array $definitions): static;

    /**
     * Sets all size definitions in one go
     *
     * @param array $definitions
     *
     * @return $this
     */
    public function setSizeDefinitions(array $definitions): static;

    /**
     * Sets all disabled definitions in one go
     *
     * @param array $definitions
     *
     * @return $this
     */
    public function setDisabledDefinitions(array $definitions): static;

    /**
     * Sets all display definitions in one go
     *
     * @param array $definitions
     *
     * @return $this
     */
    public function setDisplayDefinitions(array $definitions): static;

    /**
     * Sets all readonly definitions in one go
     *
     * @param array $definitions
     *
     * @return $this
     */
    public function setReadonlyDefinitions(array $definitions): static;
}
