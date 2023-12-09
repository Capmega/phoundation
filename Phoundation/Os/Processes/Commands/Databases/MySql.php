<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands\Databases;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataHostnamePort;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataUserPass;
use Phoundation\Databases\Exception\MysqlException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Filesystem\Exception\FileTypeNotSupportedException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Os\Processes\Commands\Zcat;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Phoundation\Os\Processes\Process;
use Phoundation\Servers\Servers;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Throwable;


/**
 * Class MySql
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class MySql extends Command
{
    use DataHostnamePort;
    use DataUserPass;
    use DataSource;


    /**
     * Imports the specified MySQL dump file into the specified database
     *
     * @param string $database
     * @param bool $drop
     * @param string $file
     */
    public function import(string $database, string $file, bool $drop, int $timeout = 3600): void
    {
        //
        $file         = Filesystem::absolute($file, DIRECTORY_DATA . 'sources/');
        $restrictions = Restrictions::new(DIRECTORY_DATA . 'sources/', false, 'Mysql importer');
        $threshold    = Log::setThreshold(3);
        $config       = static::getInstanceConfigForDatabase($database);

        // Drop the requested database
        if ($drop) {
            sql($database, false)->schema(false)
                ->database($config['database'])
                ->drop()
                ->create();
        }

        // Check file restrictions and start the import
        Log::setThreshold($threshold);

        $file = File::new($file, $restrictions)->checkReadable();

        switch ($file->getMimetype()) {
            case 'text/plain':
                $this->setInternalCommand('mysql')
                    ->setTimeout($timeout)
                    ->addArguments(['-h', $config['hostname'], '-u', $config['username'], '-p' . $config['password'], '-B', $config['database']])
                    ->setInputRedirect($file)
                    ->executeNoReturn();
                break;

            case 'application/gzip':
                $this->setInternalCommand('mysql')
                    ->setTimeout($timeout)
                    ->addArguments(['-h', $config['hostname'], '-u', $config['username'], '-p' . $config['password'], '-B', $config['database']]);

                Zcat::new()
                    ->setTimeout($timeout)
                    ->setFile($file)
                    ->setPipe($this)
                    ->execute();
                break;

            default:
                throw new FileTypeNotSupportedException(tr('The specified file ":file" has the unsupported filetype ":type"', [
                    ':file' => $file->getPath(),
                    ':type' => $file->getMimetype()
                ]));
        }
    }


    /**
     * Execute a query on a remote SSH server in a bash command
     *
     * @note This does NOT support bound variables!
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
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethodInterface $method
     * @return int|null
     */
    public function execute(EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): ?int
    {
        $password_file = static::createPasswordFile();

        try {
            // Build the process parameters, then execute
            $this->setInternalCommand('mysql')
                 ->addArgument($this->hostname ? '--host' . $this->hostname : null)
                 ->addArgument($this->port     ? '--port' . $this->port     : null)
                 ->addArgument('--user' . $this->user)
                 ->addArgument('--defaults-extra-file=' . $password_file);

            if ($this->source) {
                $this->setInputRedirect($this->source);
            }

            if ($method === EnumExecuteMethod::background) {
                $pid = $this->executeBackground();

                Log::success(tr('Executed wget as a background process with PID ":pid"', [
                    ':pid' => $pid
                ]), 4);

                // TODO Password file should only be deleted after execution has finished
                static::deletePasswordFile();
                return $pid;
            }

            $results = $this->execute($method);

            Log::notice($results, 4);
            static::deletePasswordFile();
            return null;

        } catch (Throwable $e) {
            // Ensure the password file is gone before we continue
            if ($password_file) {
                static::deletePasswordFile();
            }

            throw $e;
        }
    }


    /**
     * Creates a MySQL password file
     *
     * @return string
     */
    protected function createPasswordFile(): string
    {
        $file = '/tmp/.' . Strings::random(16) . '.cnf';
//        Process::new()
//            ->setServer($this->server)
//
//            ->, "rm ~/.my.cnf -f; touch ~/.my.cnf; chmod 0600 ~/.my.cnf; echo '[client]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysql]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldump]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldiff]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n' >> ~/.my.cnf");
    }


    /**
     * @return $this
     */
    protected function deletePasswordFile(): static
    {
        File::new('~/.my.cnf', '~/.my.cnf')
            ->setServer($this->server)
            ->secureDelete();

        return $this;
    }


    /**
     * Import all timezones in MySQL
     *
     * @note: This was designed for Ubuntu Linux, and currently any support for other operating systems is NON-EXISTENT
     *        I'll gladly add support later if I ever have time
     * @param string $password
     * @return void
     */
    public function importTimezones(string $password): void
    {
        // Test the specified root password
        $result = Process::new('mysql')
            ->setSudo(true)
            ->setTimeout(10)
            ->addArguments(['-p' . $password, '-u', 'root', 'mysql', '-e', 'SELECT 1\G'])
            ->executeReturnString();

        if (!str_ends_with($result, '1: 1')) {
            throw new ProcessesException(tr('Failed to connect with MySQL server'));
        }

        // Import timezones
        $mysql = Process::new('mysql')
            ->setSudo(true)
            ->setTimeout(10)
            ->addArguments(['-p' . $password, '-u', 'root', 'mysql']);

        Process::new('mysql_tzinfo_to_sql', Restrictions::new('/usr/share/zoneinfo'))
            ->setTimeout(10)
            ->addArgument('/usr/share/zoneinfo')
            ->setPipe($mysql)
            ->executePassthru();
    }


    /**
     * Returns the instance configuration
     *
     * @param string $database
     * @return array
     */
    protected function getInstanceConfigForDatabase(string $database): array
    {
        return Config::getArray('databases.connectors.sql.' . $database);
        foreach (Sql::getConnectors() as $connector) {

        }
    }
}
