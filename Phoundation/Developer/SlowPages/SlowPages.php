<?php
/**
 * SlowPages class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Developer\SlowPages;

use Phoundation\Developer\Incidents\Incidents;

class SlowPages extends Incidents
{
    /**
     * SlowPages class constructor
     */
    public function __construct()
    {
        $this->entry_class = SlowResponse::class;
        $this->table_name  = 'developer_slow_pages';
        $this->setHtmlQuery('SELECT   `id`, `created_on`, `status`, `title` 
                             FROM     `accounts_users` 
                             WHERE    `type` = "slow_page" AND `status` IS NULL 
                             ORDER BY `created_on`');
        parent::__construct();
    }
}
