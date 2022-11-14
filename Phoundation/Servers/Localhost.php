<?php

namespace Phoundation\Servers;

use Phoundation\Core\Core;
use Phoundation\Filesystem\Restrictions;



/**
 * Localhost class
 *
 * This class manages the localhost server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
class Localhost
{
    /**
     * File access restrictions class
     *
     * @var Restrictions $restrictions
     */
    protected Restrictions $restrictions;

    /**
     * If true, this is THIS server, localhost
     *
     * @var bool $localhost
     */
    protected bool $localhost = true;

    /**
     * The hostname for this server
     *
     * @var string|null $hostname
     */
    protected ?string $hostname = 'localhost';

    /**
     * The SSH port for this server
     *
     * @var int|null $port
     */
    protected ?int $port = null;

    /**
     * The SSH key used to connect to this server
     *
     * @var string|null $port
     */
    protected ?string $ssh_key = null;

    /**
     * True if we're connected to this server
     *
     * @var bool $connected
     */
    protected bool $connected = false;



    /**
     * Server constructor
     *
     * @param Restrictions|array|string|null $restrictions
     */
    public function __construct(Restrictions|array|string|null $restrictions)
    {
        $this->setRestrictions($restrictions);
    }



    /**
     * Returns a new server object
     *
     * @param string $hostname
     * @param Restrictions|array|string|null $restrictions
     * @return Localhost
     */
    public static function new(string $hostname, Restrictions|array|string|null $restrictions = null): static
    {
        return new Localhost($hostname, $restrictions);
    }



    /**
     * Returns the filesystem restrictions for this File object
     *
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }



    /**
     * Sets the filesystem restrictions for this File object
     *
     * @param string $hostname
     * @return void
     */
    public function setHostname(string $hostname): void
    {
        if (!$hostname) {
            $hostname = 'localhost';
        }

        $this->hostname = $hostname;
    }



    /**
     * Returns the filesystem restrictions for this File object
     *
     * @return Restrictions
     */
    public function getRestrictions(): Restrictions
    {
        return $this->restrictions;
    }



    /**
     * Sets the filesystem restrictions for this File object
     *
     * @param Restrictions|array|string|null $restrictions
     * @return void
     */
    public function setRestrictions(Restrictions|array|string|null $restrictions): void
    {
        $this->restrictions = Core::ensureRestrictions($restrictions);
    }
}