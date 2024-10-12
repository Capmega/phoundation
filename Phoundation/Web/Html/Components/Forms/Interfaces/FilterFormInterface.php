<?php

namespace Phoundation\Web\Html\Components\Forms\Interfaces;

use Phoundation\Date\Interfaces\DateTimeInterface;
use Stringable;


interface FilterFormInterface extends DataEntryFormInterface
{
    /**
     * Returns value for the specified key
     *
     * @note This is the standard Iterator::getSourceKey, but here $exception is by default false
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return mixed
     */
    public function get(Stringable|string|float|int $key, bool $exception = false): mixed;


    /**
     * Returns the date range mounting id
     *
     * @return string|null
     */
    public function getDateRangeSelector(): ?string;


    /**
     * Sets the date range mounting id
     *
     * @param string|null $id
     *
     * @return static
     */
    public function setDateRangeSelector(?string $id): static;


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
     * @return DateTimeInterface|null
     */
    public function getStartDate(?string $timezone = 'user'): ?DateTimeInterface;


    /**
     * Returns the stop date, if selected
     *
     * @param string|null $timezone
     *
     * @return DateTimeInterface|null
     */
    public function getStopDate(?string $timezone = 'user'): ?DateTimeInterface;


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
     *       here, FALSE means "don't filter", NULL means "filter on status NULL", and any string means "Filter on this
     *       string"
     *
     * @return string|false|null
     */
    public function getStatus(): string|false|null;
}
