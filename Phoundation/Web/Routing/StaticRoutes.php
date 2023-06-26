<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use PDOStatement;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;


/**
 * Class StaticRoutes
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class StaticRoutes extends DataList
{
    /**
     * StaticRoutes class constructor
     */
    public function __construct()
    {
        $this->entry_class = StaticRoute::class;
        $this->table       = 'static_routes';

        $this->setQuery('SELECT   `id`, `type`, `severity`, `title` 
                                   FROM     `static_routes` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `created_on` DESC');
        parent::__construct();
    }


    /**
     * @inheritDoc
     */
    public function load(?string $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
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


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'label', string $key_column = 'id'): SelectInterface
    {
        return InputSelect::new()
            ->setSourceQuery('SELECT   `' . $key_column . '`, `' . $value_column . '`
                                         FROM     `' . $this->table . '` 
                                         WHERE    `status` IS NULL 
                                         ORDER BY `created_on` ASC')
            ->setName('routes_id')
            ->setNone(tr('Select a static route'))
            ->setEmpty(tr('No static routes available'));
    }
}