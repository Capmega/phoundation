<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents;

use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

/**
 * Class Incidents
 *
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */
class Incidents extends DataIterator
{
    /**
     * Incidents class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `type`, `created_on`, `severity`, `title` 
                               FROM     `security_incidents` 
                               WHERE    `status` IS NULL 
                               ORDER BY `created_on` DESC');
        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'security_incidents';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string|null
     */
    public static function getEntryClass(): ?string
    {
        return Incident::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'code';
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
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order, $joins, $filters)
                     ->setName('incidents_id')
                     ->setNotSelectedLabel(tr('Select an incident'))
                     ->setComponentEmptyLabel(tr('No incidents available'));
    }
}
