<?php

/**
 * Class RightsBySeoName
 *
 * Same as Rights class, only the rights are now stored by seo_name key instead of ID
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Rights;


class RightsBySeoName extends Rights
{
    /**
     * Roles class constructor
     */
    public function __construct()
    {
        $this->keys_are_unique_column = true;
        parent::__construct();
    }
}
