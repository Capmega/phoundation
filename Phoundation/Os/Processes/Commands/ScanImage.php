<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Utils\Strings;


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
        $output = $this
            ->setCommand('scanimage')
            ->addArguments(['--formatted-device-list', '%d^^^%v^^^%m^^^%t^^^%i'])
            ->setTimeout(60)
            ->executeReturnArray();

        // Parse the output
        $return = [];

        foreach ($output as $line_number => $line) {
            $line = explode('^^^', $line);

            $return[$line[0]] = [
                'device' => $line[0],
                'vendor' => $line[1],
                'model'  => $line[2],
                'class'  => $line[3],
                'index'  => $line[4],
            ];
        }

        return $return;
    }


    /**
     * Execute the configured scan
     *
     * @param EnumExecuteMethodInterface $method
     * @return string|int|bool|array|null
     */
    public function scan(EnumExecuteMethodInterface $method = EnumExecuteMethod::noReturn): string|int|bool|array|null
    {
    }
}
