<?php

/**
 * Network class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network;

use Phoundation\Core\Log\Log;
use Phoundation\Network\Curl\Exception\CurlException;
use Phoundation\Network\Curl\Get;
use Phoundation\Network\Exception\NetworkException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;


class Network
{
    /**
     * Returns the public IP address for this machine, if possible
     *
     * @return string
     */
    public static function getPublicIpAddress(): string
    {
        try {
            return Process::new('dig')
                          ->addArgument('+short')
                          ->addArgument('myip.opendns.com')
                          ->addArgument('@resolver1.opendns.com')
                          ->executeReturnString();

        } catch (ProcessFailedException $e) {
            try {
                Log::warning(tr('The dig command failed with the following exception'));
                Log::warning(tr('This issue might be caused by a VPN, retrying with curl'));
                Log::warning($e);

                return Get::new('https://ipinfo.io/ip')
                          ->execute()
                          ->getResultData();

            } catch (CurlException $f) {
                Log::warning(tr('Failed to get public IP address from ipinfo.io'));
                throw new NetworkException(tr('Failed to determine public IP address'), $f);
            }
        }
    }
}
