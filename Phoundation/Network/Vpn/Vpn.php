<?php

/**
 * Class Vpn
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license   This plugin is developed by Medinet and may only be used by others with explicit written authorization
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Vpn;

use Phoundation\Network\Vpn\Exception\NetworkVpnNotConnectedException;


abstract class Vpn
{
    /**
     * Connects this VPN object
     *
     * @return static
     */
    abstract public function connect(): static;

    /**
     * Disconnects this VPN object
     *
     * @return static
     */
    abstract public function disconnect(): static;

    /**
     * Returns true if this VPN is connected, false otherwise
     *
     * @return bool
     */
    abstract public function isConnected(): bool;

    /**
     * Checks if the VPN is connected and throws an NetworkVpnNotConnectedException if not
     *
     * @return static
     */
    public function checkConnected(): static
    {
        if (!$this->isConnected()) {
            throw new NetworkVpnNotConnectedException(tr('The VPN is not connected'));
        }

        return $this;
    }

    /**
     * Returns the status for this VPN connection
     *
     * @return array
     */
    abstract public function getStatus(): array;

    /**
     * Returns the VPN servers object for this VPN connection
     *
     * @return VpnServersInterfac
     */
    abstract public function getVpnServersObject(): VpnServersInterface;
}
