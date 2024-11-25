<?php

namespace Phoundation\Network\PhoMeta\Interfaces;

use PDOStatement;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Network\PhoMeta\PhoMeta;

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
     * Loads the specified data into this PhoMeta object
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     * @param array|null                                       $execute
     *
     * @return $this
     */
    public function setSource(IteratorInterface|array|string|PDOStatement|null $source = null, ?array $execute = null): static;


    /**
     * Returns the hash for this object
     *
     * @return string|int|null
     */
    public function getHash(): string|int|null;


    /**
     * Sets the hash for this object
     *
     * @param string|int|null $hash
     *
     * @return static
     */
    public function setHash(string|int|null $hash): static;


    /**
     * Returns the phoundation key for this object
     *
     * @return string|int|null
     */
    public function getPhoundation(): string|int|null;


    /**
     * Sets the phoundation key for this object
     *
     * @param string|int|null $value
     *
     * @return static
     */
    public function setPhoundation(string|int|null $value): static;


    /**
     * Returns the local_id for this object
     *
     * @return string|int|null
     */
    public function getLocalId(): string|int|null;


    /**
     * Sets the local_id for this object
     *
     * @param string|int|null $local_id
     *
     * @return static
     */
    public function setLocalId(string|int|null $local_id): static;


    /**
     * Returns the global_id for this object
     *
     * @return string|int|null
     */
    public function getGlobalId(): string|int|null;


    /**
     * Sets the global_id for this object
     *
     * @param string|int|null $global_id
     *
     * @return static
     */
    public function setGlobalId(string|int|null $global_id): static;


    /**
     * Adds a test value to this PhoMeta object's source
     *
     * @param PhoMetaTestInterface $test
     *
     * @return $this
     */
    public function addTest(PhoMetaTestInterface $test): static;


    /**
     * Removes the test object with a given component from this PhoMeta object
     *
     * @param string $component
     *
     * @return static
     */
    public function removeTest(string $component): static;


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
     * @return PhoMeta
     */
    public function processTestObjects(string $component): static;


    /**
     * Checks if there is PhoMetaTest information, and if so, if specified component is the terminal test. Terminal test
     * refers to the only remaining test that matches the component name, which means this test is meant to end at
     * the specified component without going any further
     *
     * @param string $component
     *
     * @return bool
     */
    public function isTerminalTest(string $component): bool;


    /**
     * Returns the count of PhoMetaTest objects in the data array
     *
     * @return int
     */
    public function getTestCount(): int;
}