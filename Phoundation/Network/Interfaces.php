<?php

/**
 * Class Interfaces
 *
 * This class manages network interfaces
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network;

use Phoundation\Network\Exception\SocketException;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


class Interfaces
{
    /**
     * Returns a random interface IP
     *
     * @param bool $allow_localhosts
     *
     * @return string
     */
    public static function getRandomInterfaceIp(bool $allow_localhosts = false): string
    {
        $ips = static::listIps();
        // Filter local IP's?
        // Todo find a better way to handle this, as 172.* is typically used by virtual machines, so don't have internet
        // Todo ideally we would need to know if the interface has internet access before we return its ip
        if (!$allow_localhosts) {
            foreach ($ips as $id => $ip) {
                if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
                    unset($ips[$id]);
                }
                if (str_starts_with($ip, '172.')) {
                    unset($ips[$id]);
                }
            }
        }

        return Arrays::getRandomValue($ips);
    }


    /**
     * Returns a random IP from the pool of all IP addresses available on this computer
     *
     * @param bool $ipv4      If set to true, IPv4 addresses are also returned
     * @param bool $ipv6      If set to true, IPv6 addresses are also returned
     * @param bool $localhost If set to true, the localhost ip 127.0.0.1 is also returned
     *
     * @return array          All IP addresses available on this server
     * @todo Implement IPv6 support! The variable is there, but not implemented yet
     */
    public static function listIps(bool $ipv4 = true, bool $ipv6 = false, bool $localhost = true): array
    {
        $results = Process::new('ifconfig')
                          ->setPipe(Process::new('egrep')
                                           ->addArgument('-i')
                                           ->addArgument('addr|inet'))
                          ->executeReturnString();
        if (!preg_match_all('/(?:addr|inet)6?(?:\:| )(.+?) /', $results, $matches)) {
            throw new SocketException(tr('ifconfig returned no IPs'));
        }
        if (!$matches or empty($matches[1])) {
            throw new SocketException(tr('No IP interface information found'));
        }
        $flags   = FILTER_VALIDATE_IP;
        $options = null;
        $ips     = [];
        if (!$ipv4) {
            if (!$ipv6) {
                throw new SocketException(tr('Both IPv4 and IPv6 IP\'s are specified to be disallowed'));
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
        // Ensure we have unique IP addresses
        $ips = array_unique($ips);
        if (!$ips) {
            throw new SocketException(tr('Failed to find any IP addresses'));
        }

        return $ips;
    }
}
