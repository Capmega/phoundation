<?php

namespace Phoundation\Databases\Sql\Schema\Interfaces;

interface SchemaAbstractInterface
{
/**
     * Reload the table schema
     *
     * @return void
     */
    public function reload(): void;
}
