<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Schema\Interfaces;

use Phoundation\Databases\Sql\Interfaces\SqlInterface;

interface SchemaAbstractInterface
{
    /**
     * Returns the name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Sets the name
     *
     * @param string|null $name
     *
     * @return static
     */
    public function setName(?string $name): static;

    /**
     * Returns the SQL object for this schema
     *
     * @return SqlInterface
     */
    public function getSqlObject(): SqlInterface;

    /**
     * Reload the table schema
     *
     * @return void
     */
    public function reload(): void;
}
