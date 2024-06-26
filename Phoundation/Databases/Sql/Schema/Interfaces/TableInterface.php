<?php

namespace Phoundation\Databases\Sql\Schema\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Sql\Schema\TableAlter;
use Phoundation\Databases\Sql\Schema\TableDefine;

interface TableInterface extends SchemaAbstractInterface
{
/**
     * Define and create the table
     *
     * @return TableDefine
     */
    public function define(): TableDefine;
/**
     * Define and create the table
     *
     * @return TableAlter
     */
    public function alter(): TableAlter;
/**
     * Renames this table
     *
     * @param string $table_name
     *
     * @return void
     */
    public function rename(string $table_name): void;
/**
     * Returns if the table exists in the database or not
     *
     * @return bool
     */
    public function exists(): bool;
/**
     * Will drop this table
     *
     * @return static
     */
    public function drop(): static;
/**
     * Will truncate this table
     *
     * @return void
     */
    public function truncate(): void;
/**
     * Returns the number of records in this table
     *
     * @return int
     */
    public function getCount(): int;
/**
     * Returns the table name
     *
     * @return string|null
     */
    public function getName(): ?string;
/**
     * Returns the table columns
     *
     * @param bool $cache
     *
     * @return IteratorInterface
     */
    public function getColumns(bool $cache = true): IteratorInterface;
/**
     * Returns the table foreign_keys
     *
     * @return array
     */
    public function getForeignKeys(): array;

    /**
     * Returns true if the specified column exists in this table
     *
     * @param string $column
     *
     * @return bool
     */
    public function columnExists(string $column): bool;
}
