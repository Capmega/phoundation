<?php

namespace Phoundation\Databases;

use Phoundation\Core\Strings;
use Phoundation\Databases\Exception\MysqlException;
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
     * Execute a query on a remote SSH server in a bash command
     *
     * @note: This does NOT support bound variables!
     * @todo: This method uses a password file which might be left behind if (for example) the connection would drop
     *        half way
     * @param string|Server $server
     * @param string $query
     * @param bool $root
     * @param bool $simple_quotes
     * @return array
     */
    public function exec(string|Server $server, string $query, bool $root = false, bool $simple_quotes = false): array
    {
        try {
            $query = addslashes($query);

            if (!is_array($server)) {
                $server = Servers::get($server, true);
            }

            // Are we going to execute as root?
            if ($root) {
                My$this->createPasswordFile('root', $server['db_root_password'], $server);

            } else {
                My$this->createPasswordFile($server['db_username'], $server['db_password'], $server);
            }

            if ($simple_quotes) {
                $results = Servers::exec($server, 'mysql -e \'' . Strings::ends($query, ';') . '\'');

            } else {
                $results = Servers::exec($server, 'mysql -e \"' . Strings::ends($query, ';') . '\"');
            }

            My$this->deletePasswordFile($server);

            return $results;
        } catch (MysqlException $e) {
            // Ensure that the password file will be removed
            My$this->deletePasswordFile($server);
        }
    }

}