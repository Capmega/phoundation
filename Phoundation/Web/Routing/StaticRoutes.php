<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use PDOStatement;
use Phoundation\Accounts\Roles\Role;
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
        $this->setQuery('SELECT   `id`, `type`, `severity`, `title` 
                                   FROM     `static_routes` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `created_on` DESC');
        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'static_routes';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return StaticRoute::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return null;
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
                                         FROM     `' . static::getTable() . '` 
                                         WHERE    `status` IS NULL 
                                         ORDER BY `created_on` ASC')
            ->setName('routes_id')
            ->setNone(tr('Select a static route'))
            ->setEmpty(tr('No static routes available'));
    }
}