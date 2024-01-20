<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;


/**
 * Class ScanImage
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class ScanImage extends Command
{
    /**
     *
     *
     * @param EnumExecuteMethodInterface $method
     * @return string|int|bool|array|null
     */
    public function listDevices(EnumExecuteMethodInterface $method = EnumExecuteMethod::noReturn): string|int|bool|array|null
    {
    }


    /**
     *
     *
     * @param EnumExecuteMethodInterface $method
     * @return string|int|bool|array|null
     */
    public function scan(EnumExecuteMethodInterface $method = EnumExecuteMethod::noReturn): string|int|bool|array|null
    {
    }
}
