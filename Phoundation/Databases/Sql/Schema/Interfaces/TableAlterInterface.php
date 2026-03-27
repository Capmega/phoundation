<?php

namespace Phoundation\Databases\Sql\Schema\Interfaces;

interface TableAlterInterface
{
    /**
     * Sets the table name
     *
     * @param string|null $name
     *
     * @return static
     */
    public function setName(?string $name): static;


    /**
     * Add the array of columns to the table
     *
     * @note This will clear the current columns array
     *
     * @param string|array $columns
     * @param string       $after
     *
     * @return static
     */
    public function addColumns(string|array $columns, string $after): static;


    /**
     * Add a single column to the table
     *
     * @note This will clear the current columns array
     *
     * @param string $column
     * @param string $before_after
     *
     * @return static
     */
    public function addColumn(string $column, string $before_after): static;


    /**
     * Drop the specified column from the table
     *
     * @param string $column          The column to drop
     * @param bool   $if_exists       [false] If true, will only try to remove the index if it exists. If the index does not exist, nothing will be done. If false,
     *                                the method will execute the DROP INDEX command which will fail if the index does not exist
     *
     * @return static
     */
    public function dropColumn(string $column, bool $if_exists = false): static;


    /**
     * Modify the specified column from the table
     *
     * @param string $column
     * @param string $to_definition
     *
     * @return static
     */
    public function modifyColumn(string $column, string $to_definition): static;


    /**
     * Change the specified column from the table
     *
     * @param string $column
     * @param string $to_definition
     *
     * @return static
     */
    public function changeColumn(string $column, string $to_definition): static;


    /**
     * Rename the specified column
     *
     * @param string $from_name
     * @param string $to_name
     * @param bool   $rename_index If this option is true, and the table contains an index with the same column name,
     *                             the index with the old name will be removed, and an index with the new name will be
     *                             created
     *
     * @return static
     */
    public function renameColumn(string $from_name, string $to_name, bool $rename_index = true): static;


    /**
     * Add the array of indices to the table
     *
     * @param string|array $indices
     *
     * @return static
     */
    public function addIndices(string|array $indices): static;


    /**
     * Returns an array with all table definitions
     *
     * @return array
     */
    public function getDefinitions(): array;


    /**
     * Returns the definition for the specified column
     *
     * @param string      $column
     * @param string|null $filter_extra
     *
     * @return string
     */
    public function getDefinition(string $column, ?string $filter_extra = null): string;


    /**
     * Renames the index with the specified name
     *
     * @param string $from_name
     * @param string $to_name
     *
     * @return static
     */
    public function renameIndex(string $from_name, string $to_name): static;


    /**
     * Add a single index to the table
     *
     * @param string $index
     *
     * @return static
     */
    public function addIndex(string $index): static;


    /**
     * Drop the specified index from the table
     *
     * @param string $index           The index to drop
     * @param bool   $if_exists       [false] If true, will only try to remove the index if it exists. If the index does not exist, nothing will be done. If false,
     *                                the method will execute the DROP INDEX command which will fail if the index does not exist
     *
     * @return static
     */
    public function dropIndex(string $index, bool $if_exists = false): static;


    /**
     * Add the array of foreign_keys to the table
     *
     * @param string|array $foreign_keys
     *
     * @return static
     */
    public function addForeignKeys(string|array $foreign_keys): static;


    /**
     * Add a single foreign_key to the table
     *
     * @param string $foreign_key
     *
     * @return static
     */
    public function addForeignKey(string $foreign_key): static;


    /**
     * Drop the specified foreign_key from the table
     *
     * @param string $foreign_key         The foreign key to drop
     * @param bool   $if_exists           [false] If true, will only try to remove the index if it exists. If the index does not exist, nothing will be done. If false,
     *                                    the method will execute the DROP INDEX command which will fail if the index does not exist
     *
     * @return static
     */
    public function dropForeignKey(string $foreign_key, bool $if_exists = false): static;
}
