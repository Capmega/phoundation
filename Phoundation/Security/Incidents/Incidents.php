<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents;

use PDOStatement;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Servers\Server;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;


/**
 * Class Incidents
 *
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */
class Incidents extends DataList
{
    /**
     * Incidents class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `type`, `severity`, `title` 
                                   FROM     `security_incidents` 
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
        return 'security_incidents';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Incident::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'code';
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id'): SelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column)
            ->setName('incidents_id')
            ->setNone(tr('Select an incident'))
            ->setEmpty(tr('No incidents available'));
    }
}