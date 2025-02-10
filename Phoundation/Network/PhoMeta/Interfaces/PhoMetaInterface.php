<?php

namespace Phoundation\Network\PhoMeta\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;

interface PhoMetaInterface extends DataEntryInterface
{
    /**
     * @param bool $filter_meta
     *
     * @return array
     */
    public function getSource(bool $filter_meta = false): array;


    /**
     * Calls an existing 'extraction' method based on pho version
     *
     * @param string $message
     *
     * @return static
     */
    public function extractPhoMetaData(string $message): string;


    /**
     * Returns the hash for this object
     *
     * @return string|null
     */
    public function getHash(): ?string;


    /**
     * Sets the hash for this object
     *
     * @param string|null $hash
     *
     * @return static
     */
    public function setHash(string|null $hash): static;


    /**
     * Returns the global_id for this object
     *
     * @return string|null
     */
    public function getGlobalId(): ?string;


    /**
     * Sets the global_id for this object
     *
     * @param string|null $global_id
     *
     * @return static
     */
    public function setGlobalId(string|null $global_id): static;


    /**
     * Adds a test value to this PhoMeta object's source
     *
     * @param PhoMetaTestInterface $test
     *
     * @return $this
     */
    public function addTest(PhoMetaTestInterface $test): static;


    /**
     * Adds an array of data to this PhoMeta object's source
     *
     * @param string $key
     * @param array  $data
     * @param bool   $data_is_sub_array     Whether the data is stored as a sub array. If it is, the data will be
     *                                      stored inside the key=>value[], otherwise it will be stored as the
     *                                      key=>value
     *
     * @return $this
     */
    public function addData(string $key, array $data, bool $data_is_sub_array = false): static;


    /**
     * Checks the source for PhoMetaTest info and if it matches a specified component. If it does, it will remove
     * that PhoMetaTest, and have it record itself in its database. Returns static
     *
     * @param string $component
     *
     * @return bool
     */
    public function processTest(string $component): bool;
}
