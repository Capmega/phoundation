<?php

namespace Phoundation\Databases\Sql\Schema\Interfaces;

use Phoundation\Databases\Sql\Schema\TableDefine;

interface TableDefineInterface
{
    /**
     * Clears the table columns
     *
     * @return TableDefine
     */
    public function clearColumns(): TableDefine;


    /**
     * Sets the table columns
     *
     * @note This will clear the current columns array
     *
     * @param string|array $columns
     *
     * @return TableDefine
     */
    public function setColumns(string|array $columns): TableDefine;


    /**
     * Add the array of columns to the table
     *
     * @note This will clear the current columns array
     *
     * @param string|array $columns
     *
     * @return TableDefine
     */
    public function addColumns(string|array $columns): TableDefine;


    /**
     * Add a single column to the table
     *
     * @note This will clear the current columns array
     *
     * @param string $column
     *
     * @return TableDefine
     */
    public function addColumn(string $column): TableDefine;


    /**
     * Clears the table indices
     *
     * @return TableDefine
     */
    public function clearIndices(): TableDefine;


    /**
     * Sets the table indices
     *
     * @note This will clear the current indices array
     *
     * @param string|array $indices
     *
     * @return TableDefine
     */
    public function setIndices(string|array $indices): TableDefine;


    /**
     * Add the array of indices to the table
     *
     * @param string|array $indices
     *
     * @return TableDefine
     */
    public function addIndices(string|array $indices): TableDefine;


    /**
     * Add a single index to the table
     *
     * @param string $index
     *
     * @return TableDefine
     */
    public function addIndex(string $index): TableDefine;


    /**
     * Clears the table foreign_keys
     *
     * @return TableDefine
     */
    public function clearForeignKeys(): TableDefine;


    /**
     * Sets the table foreign_keys
     *
     * @note This will clear the current foreign_keys array
     *
     * @param string|array $foreign_keys
     *
     * @return TableDefine
     */
    public function setForeignKeys(string|array $foreign_keys): TableDefine;


    /**
     * Add the array of foreign_keys to the table
     *
     * @param string|array $foreign_keys
     *
     * @return TableDefine
     */
    public function addForeignKeys(string|array $foreign_keys): TableDefine;


    /**
     * Add a single foreign_key to the table
     *
     * @param string $foreign_key
     *
     * @return TableDefine
     */
    public function addForeignKey(string $foreign_key): TableDefine;


    /**
     * Create the specified table
     *
     * @return void
     */
    public function create(): void;
}
