<?php

namespace Phoundation\Servers;

use Phoundation\Filesystem\Restrictions;



/**
 * Server class
 *
 * This class manages a single server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
class Server
{
    /**
     * File access restrictions class
     *
     * @var Restrictions|null
     */
    protected ?Restrictions $restrictions = null;

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
    protected ?string $hostname = null;

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
     * @param Restrictions|null $restrictions
     */
    public function __construct(?Restrictions $restrictions) {
        $this->restrictions = $restrictions;
    }



    /**
     * Returns file restrictions access
     *
     * @return Restrictions
     */
    public function restrictions(): Restrictions
    {
        if (!$this->restrictions) {
            // No restrictions were set for this server, return empty restrictions
            $this->restrictions = new Restrictions();
        }

        return $this->restrictions;
    }
}