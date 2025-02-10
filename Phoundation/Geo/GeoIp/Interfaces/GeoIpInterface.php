<?php

namespace Phoundation\Geo\GeoIp\Interfaces;

interface GeoIpInterface
{
    /**
     * Returns the ip address for this user
     *
     * @return string|null
     */
    public function getIpAddress(): ?string;

    /**
     * Sets the ip address for this user
     *
     * @param string|null $ip_address
     *
     * @return static
     */
    public function setIpAddress(?string $ip_address): static;

    /**
     * Returns true if the IP is European
     *
     * @return bool
     */
    public function isEuropean(): bool;
}