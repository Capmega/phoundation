<?php

/**
 * Interface VpnInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Vpn\Interfaces;


interface VpnInterface {
    /**
     * Connects this VPN object
     *
     * @return static
     */
    public function connect(): static;

    /**
     * Disconnects this VPN object
     *
     * @return static
     */
    public function disconnect(): static;

    /**
     * Returns true if this VPN is connected, false otherwise
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Checks if the VPN is connected and throws an NetworkVpnNotConnectedException if not
     *
     * @return static
     */
    public function checkConnected(): static;

    /**
     * Returns the status for this VPN connection
     *
     * @return array
     */
    public function getStatus(): array;

    /**
     * Returns the VPN servers object for this VPN connection
     *
     * @return VpnServersInterface
     */
    public function getVpnServersObject(): VpnServersInterface;
}
