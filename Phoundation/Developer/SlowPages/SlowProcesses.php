<?php

declare(strict_types=1);

namespace Phoundation\Developer\SlowPages;

use Phoundation\Developer\Incidents\Incidents;


/**
 * SlowPages class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */
class SlowProcesses extends Incidents
{
    /**
     * SlowProcesses class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `created_on`, `status`, `title` 
                               FROM     `processes_slow` 
                               WHERE    `type` = "slow_page" AND `status` IS NULL 
                               ORDER BY `created_on`');
        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'processes_slow';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return SlowProcess::class;
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
}
