<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Phoundation\Data\DataEntry\DataList;

/**
 * Class StaticRoutes
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class StaticRoutes extends DataList
{
    /**
     * StaticRoutes class constructor
     *
     * @param StaticRoute|null $parent
     * @param string|null $id_column
     */
    public function __construct(?StaticRoute $parent = null, ?string $id_column = null)
    {
        $this->entry_class = StaticRoute::class;
        self::$table       = StaticRoute::getTable();

        $this->setHtmlQuery('SELECT   `id`, `type`, `severity`, `title` 
                                   FROM     `static_routes` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `created_on` DESC');
        parent::__construct($parent, $id_column);
    }


    /**
     * @inheritDoc
     */
    protected function load(string|int|null $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }
}