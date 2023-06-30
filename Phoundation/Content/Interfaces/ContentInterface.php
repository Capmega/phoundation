<?php

namespace Phoundation\Content\Interfaces;

use Phoundation\Filesystem\Interfaces\FileInterface;


/**
 * Class View
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Content
 */
interface ContentInterface extends FileInterface
{
    /**
     * View the object file
     *
     * @return void
     */
    public function view(): void;
}