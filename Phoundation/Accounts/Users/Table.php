<?php

/**
 * Class Table
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Web\Html\Components\Tables\HtmlTable;


class Table extends HtmlTable
{
    /**
     * Table class constructor
     */
    public function __construct()
    {
        $this->setConnector($this->connector)
             ->setSourceQuery('SELECT * FROM `accounts_users`');
        parent::__construct();
    }
}
