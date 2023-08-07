<?php

declare(strict_types=1);

namespace Phoundation\Databases;

use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Strings;
use Phoundation\Databases\Exception\MysqlException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Process;
use Phoundation\Servers\Servers;


/**
 * Mysql class
 *
 * This class can manage MySQL servers and databases
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Mysql
{
    /**
     * The server object to execute commands on different servers if needed
     *
     * @var RestrictionsInterface|array|string|null $restrictions
     */
    protected RestrictionsInterface|array|string|null $restrictions = null;


    /**
     * Mysql class constructor
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     */
    public function __construct(RestrictionsInterface|array|string|null $restrictions = null)
    {
        $this->restrictions = Core::ensureRestrictions($restrictions);
    }


    /**
     * Get a new instance of the Mysql class
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return Mysql
     */
    public static function getInstance(RestrictionsInterface|array|string|null $restrictions = null): Mysql
    {
        return new Mysql($restrictions);
    }


    /**
     * Imports the specified MySQL dump file into the specified database
     *
     * @param string $database
     * @param string $file
     */
    public static function import(string $database, string $file, int $timeout = 3600): void
    {
        $file         = PATH_DATA . 'sources/' . $file;
        $restrictions = Restrictions::new(PATH_DATA . 'sources/', false, 'Mysql importer');

        // Drop the requested database
        sql()->schema()
            ->database($database)
                ->drop()
                ->create();

        // Start the import
        File::new($file, $restrictions)->checkReadable();

        Process::new('mysql', $restrictions)
            ->setTimeout($timeout)
            ->addArguments(['-h', Config::getString('databases.sql.instances.system.host'), '-u', Config::getString('databases.sql.instances.system.user'), '-p' . Config::getString('databases.sql.instances.system.pass'), '-B', $database])
            ->setInputRedirect($file)
            ->executeNoReturn();
    }


    /**
     * Execute a query on a remote SSH server in a bash command
     *
     * @note: This does NOT support bound variables!
     * @param string $query
     * @param bool $root
     * @param bool $simple_quotes
     * @return array
     * @todo: This method uses a password file which might be left behind if (for example) the connection would drop
     *        half way
     */
    public function exec(string $query, bool $root = false, bool $simple_quotes = false): array
    {
        try {
            $query = addslashes($query);

            // Are we going to execute as root?
            if ($root) {
                $this->createPasswordFile('root', $restrictions['db_root_password'], $restrictions);

            } else {
                $this->createPasswordFile($restrictions['db_username'], $restrictions['db_password'], $restrictions);
            }

            if ($simple_quotes) {
                $results = Servers::exec($restrictions, 'mysql -e \'' . Strings::ends($query, ';') . '\'');

            } else {
                $results = Servers::exec($restrictions, 'mysql -e \"' . Strings::ends($query, ';') . '\"');
            }

            $this->deletePasswordFile($restrictions);

            return $results;
        } catch (MysqlException $e) {
            // Ensure that the password file will be removed
            $this->deletePasswordFile($restrictions);
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
