<?php

namespace Phoundation\Processes;

use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Exception\Exception;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Exception\CommandsException;
use Phoundation\Servers\Server;



/**
 * Class Commands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Command
{
    /**
     * Where will this be executed? Locally or on the specified server
     *
     * @var Server $server
     */
    protected Server $server;

    /**
     * File restrictions for this class
     *
     * @var Restrictions $restrictions
     */
    protected Restrictions $restrictions;



    /**
     * @param Server|string|null $server
     */
    public function __construct(Server|string|null $server = null)
    {
        $this->setServer($server);
        $this->setRestrictions($server->getRestrictions());
    }



    /**
     * Returns a new Images object
     *
     * @param Server|string|null $server
     * @return Command
     */
    public static function new(Server|string|null $server = null): Command
    {
        return new Command($server);
    }



    /**
     * Sets the server for this commands object
     *
     * Sets the server by name or object, NULL for localhost
     *
     * @param Server|string|null $server
     * @return static
     */
    public function setServer(Server|string|null $server = null): static
    {
        if (!is_object($server)) {
            $server = new Server($server);
        }

        $this->server = $server;
        return $this;
    }



    /**
     * Returns the server object for this commands object
     *
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
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



    /**
     * Returns a commands object for this local machine
     *
     * @return static
     */
    public static function local(): static
    {
        return new static();
    }



   /**
     * Command exception handler
     *
     * @param string $command
     * @param Exception $e
     * @param callable|null $function
     * @return void
     */
    protected static function handleException(string $command, Exception $e, ?callable $function = null): void
    {
        if ($e->getData()['output']) {
            $data       = $e->getData()['output'];
            $first_line = Arrays::firstValue($data);
            $first_line = strtolower($first_line);
            $last_line  = Arrays::lastValue($data);
            $last_line  = strtolower($last_line);

            // Process specified handlers
            if ($function) {
                $function($first_line, $last_line, $e);
            }

            // Handlers were unable to make a clear exception out of this, show the standard command exception
            throw new CommandsException(tr('The command :command failed with ":output"', [':command' => $command, ':output' => $data]));
        }

        // Something else went wrong, no CLI output available
        throw new CommandsException(tr('The command :command failed for unknown reasons', [':command' => $command]));
    }
}