<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

/**
 * Class StaticRoutes
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class StaticRoutes extends DataIterator
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
     * Returns the name of this DataEntry class
     *
     * @return string|null
     */
    public static function getEntryClass(): ?string
    {
        return StaticRoute::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'label', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return InputSelect::new()
                          ->setConnector(static::getConnector())
                          ->setSourceQuery('SELECT   `' . $key_column . '`, `' . $value_column . '`
                                         FROM     `' . static::getTable() . '` 
                                         WHERE    `status` IS NULL 
                                         ORDER BY `created_on` ASC')
                          ->setName('routes_id')
                          ->setNotSelectedLabel(tr('Select a static route'))
                          ->setComponentEmptyLabel(tr('No static routes available'));
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'static_routes';
    }
}
