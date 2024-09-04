<?php

namespace Phoundation\Core\Meta\Interfaces;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Stringable;


interface MetaInterface
{
    /**
     * Creates a new meta entry and returns the database id for it
     *
     * @param string                                          $action
     * @param string|null                                     $comments
     * @param ArrayableInterface|Stringable|array|string|null $data
     *
     * @return static
     */
    public function action(string $action, ?string $comments = null, ArrayableInterface|Stringable|array|string|null $data = null): static;


    /**
     * Returns the id for this metadata object
     *
     * @return int
     */
    public function getId(): int;


    /**
     * Erases all meta history for this meta id
     *
     * @return void
     */
    public function erase(): void;


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     *
     * @return HtmlTableInterface
     */
    public function getHtmlTable(array|string|null $columns = null): HtmlTableInterface;
}
