<?php

namespace Phoundation\Accounts\Users;



/**
 * Class Table
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Table extends \Phoundation\Web\Http\Html\Elements\Table
{
    /**
     * Table class constructor
     */
    public function __construct()
    {
        $this->setSourceQuery('SELECT * FROM `accounts_users`');
        parent::__construct();
    }
}