<?php

declare(strict_types=1);

namespace Phoundation\Core\Meta\Interfaces;

use Phoundation\Web\Html\Components\Interfaces\HtmlTableInterface;


/**
 * interface MetaInterface
 *
 * This class keeps track of metadata for database entries throughout phoundation projects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Core
 */
interface MetaInterface
{
    /**
     * Returns the id for this metadata object
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Creates a new meta entry and returns the database id for it
     *
     * @param string $action
     * @param string|null $comments
     * @param string|null $data
     * @return static
     */
    public function action(string $action, ?string $comments = null, ?string $data = null): static;

    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     * @return HtmlTableInterface
     */
    public function getHtmlTable(array|string|null $columns = null): HtmlTableInterface;
}
