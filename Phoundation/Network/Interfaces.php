<?php

namespace Phoundation\Network;

use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Network\Exception\NetworkException;
use Phoundation\Processes\Process;



/**
 * Class Interfaces
 *
 * This class manages network interfaces
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
class Interfaces
{
    /**
     * Returns a random IP from the pool of all IP addresses available on this computer
     *
     * @todo Implement IPv6 support! The variable is there, but not implemented yet
     * @param bool $ipv4      If set to true, IPv4 addresses are also returned
     * @param bool $ipv6      If set to true, IPv6 addresses are also returned
     * @param bool $localhost If set to true, the localhost ip 127.0.0.1 is also returned
     * @return array          All IP addresses available on this server
     */
    public static function listIps(bool $ipv4 = true, bool $ipv6 = false, bool $localhost = true): array
    {
        try {
            $results = Process::new('ifconfig')->setPipe(Process::new('egrep')
                    ->addArgument('-i')
                    ->addArgument('addr|inet'))
                ->executeReturnArray();

        }catch(Throwable $e) {
            throw new NetworkException(tr('Failed to execute ifconfig'), $e);
        }

        if (!preg_match_all('/(?:addr|inet)6?(?:\:| )(.+?) /', $results, $matches)) {
            throw new NetworkException(tr('ifconfig returned no IPs'));
        }

        if (!$matches or empty($matches[1])) {
            throw new NetworkException(tr('No IP interface information found'));
        }

        $flags   = FILTER_VALIDATE_IP;
        $options = null;
        $ips     = array();

        if (!$ipv4) {
            if (!$ipv6) {
                throw new NetworkException(tr('Both IPv4 and IPv6 IP\'s are specified to be disallowed'));
            }

            $options = $options | FILTER_FLAG_IPV6;

        } elseif (!$ipv6) {
            $options = $options | FILTER_FLAG_IPV4;
        }

        foreach ($matches[1] as $ip) {
            if (!$ip) {
                continue;
            }

            $ip = str_replace(':', '', $ip);
            $ip = trim(Strings::from($ip, 'addr'));

            if ($ip == '127.0.0.1') {
                if (!$localhost) {
                    continue;
                }
            }

            if (filter_var($ip, $flags, $options)) {
                $ips[] = $ip;
            }
        }

        if (!$ips) {
            throw new NetworkException(tr('Failed to find any IP addresses'));
        }

        return $ips;
    }



    /**
     * Returns a random interface IP
     *
     * @return string
     */
    public static function getRandomIp(): string
    {
        return Arrays::getRandomValue(static::listIps());
    }
}