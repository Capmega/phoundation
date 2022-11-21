<?php

namespace Phoundation\Databases;

use Phoundation\Core\Core;
use Phoundation\Core\Strings;
use Phoundation\Databases\Exception\MysqlException;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Process;
use Phoundation\Servers\Server;
use Phoundation\Servers\Servers;



/**
 * Mysql class
 *
 * This class can manage MySQL servers and databases
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Mysql
{
    /**
     * The server object to execute commands on different servers if needed
     *
     * @var Server|Restrictions|array|string|null $server_restrictions
     */
    protected Server|Restrictions|array|string|null $server_restrictions = null;


    /**
     * Mysql class constructor
     *
     * @param Server|Restrictions|array|string|null $server_restrictions
     */
    public function __construct(Server|Restrictions|array|string|null $server_restrictions = null)
    {
        $this->server_restrictions = Core::ensureServer($server_restrictions);
    }



    /**
     * Get a new instance of the Mysql class
     *
     * @param Server|Restrictions|array|string|null $server_restrictions
     * @return Mysql
     */
    public static function getInstance(Server|Restrictions|array|string|null $server_restrictions = null): Mysql
    {
        return new Mysql($server_restrictions);
    }



    /**
     * Execute a query on a remote SSH server in a bash command
     *
     * @note: This does NOT support bound variables!
     * @param string|Server $server_restrictions
     * @param string $query
     * @param bool $root
     * @param bool $simple_quotes
     * @return array
     *@todo: This method uses a password file which might be left behind if (for example) the connection would drop
     *        half way
     */
    public function exec(string $query, bool $root = false, bool $simple_quotes = false): array
    {
        try {
            $query = addslashes($query);

            // Are we going to execute as root?
            if ($root) {
                $this->createPasswordFile('root', $server_restrictions['db_root_password'], $server_restrictions);

            } else {
                $this->createPasswordFile($server_restrictions['db_username'], $server_restrictions['db_password'], $server_restrictions);
            }

            if ($simple_quotes) {
                $results = Servers::exec($server_restrictions, 'mysql -e \'' . Strings::ends($query, ';') . '\'');

            } else {
                $results = Servers::exec($server_restrictions, 'mysql -e \"' . Strings::ends($query, ';') . '\"');
            }

            $this->deletePasswordFile($server_restrictions);

            return $results;
        } catch (MysqlException $e) {
            // Ensure that the password file will be removed
            $this->deletePasswordFile($server_restrictions);
        }
    }



    /**
     * Import all timezones in MySQL
     *
     * @note: This was designed for Ubuntu Linux and currently any support for other operating systems is NON-EXISTENT
     *        I'll gladly add support later if I ever have time
     * @return void
     */
    public function importTimezones(): void
    {
        $mysql = Process::new('mysql')
            ->setTimeout(10)
            ->addArguments(['-p', '-u', 'root', 'mysql']);

        Process::new('mysql_tzinfo_to_sql')
            ->setTimeout(10)
            ->addArgument('/usr/share/zoneinfo')
            ->setPipe($mysql)
            ->executePassthru();
    }
}