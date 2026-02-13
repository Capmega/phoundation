<?php

/**
 * Class MySql
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands\Databases;

use Phoundation\Core\Core;
use Phoundation\Core\Interfaces\TimerInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Timer;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataHostnamePort;
use Phoundation\Data\Traits\TraitDataStringSource;
use Phoundation\Data\Traits\TraitDataUserPassword;
use Phoundation\Databases\Exception\MysqlException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileTypeNotSupportedException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Os\Processes\Commands\Grep;
use Phoundation\Os\Processes\Commands\Tail;
use Phoundation\Os\Processes\Commands\Zcat;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Phoundation\Os\Processes\Process;
use Phoundation\Servers\Servers;
use Phoundation\Utils\Strings;
use Throwable;


class MySql extends Command
{
    use TraitDataHostnamePort;
    use TraitDataUserPassword;
    use TraitDataStringSource;
    use TraitDataConnector;


    /**
     * Drops the specified database
     *
     * @param string|null $database
     *
     * @return static
     */
    public function drop(?string $database): static
    {
        $this->checkSpecified($database, tr('drop database'));

        // Drop the requested database
        sql($this->_connector, false)
            ->getSchemaObject(false)
            ->getDatabaseObject($database, false)
            ->drop();

        return $this;
    }


    /**
     * Creates the specified database
     *
     * @param string|null $database
     *
     * @return static
     */
    public function create(?string $database): static
    {
        $this->checkSpecified($database, tr('create database'));

        // Drop the requested database
        sql($this->_connector, false)
            ->getSchemaObject(false)
            ->getDatabaseObject($database, false)
            ->create();

        return $this;
    }


    /**
     * Checks that a database is specified
     *
     * @param string|null $database
     * @param string      $action
     *
     * @return void
     */
    protected function checkSpecified(?string $database, string $action): void
    {
        if (empty($database)) {
            throw new OutOfBoundsException(tr('Cannot execute action ":action", no database specified', [
                ':action' => $action,
            ]));
        }
    }


    /**
     * Imports the specified MySQL dump file into the specified database
     *
     * @see https://kedar.nitty-witty.com/blog/a-unique-foreign-key-issue-in-mysql-8-4
     *
     * @param PhoFileInterface $file
     * @return TimerInterface
     */
    public function import(PhoFileInterface $file): TimerInterface
    {
        // Start timer and get file and database information
        $_timer    = Timer::new('mysql import');
        $threshold = Log::setThreshold(3);

        // If we are importing the system database, then switch to init mode!
        if ($this->_connector->getDatabase() === sql()->getDatabase()) {
            Core::enableInitState();
        }

        // Check file restrictions and start the import
        Log::setThreshold($threshold);

        switch ($file->getMimetype()) {
            case 'text/plain':
                $this->setCommand('mysql')
                     ->setTimeout($this->timeout)
                     ->addArguments([
                         '-h',  $this->_connector->getHostname(),
                         '-u',  $this->_connector->getUsername(),
                         '-p' . $this->_connector->getPassword(), // The -p and password must be one string, so "-ppassword"!
                         '-B',  $this->_connector->getDatabase(),
                     ]);

                Tail::new()
                    ->setTimeout($this->timeout)
                    ->setFileObject($file)
                    ->addArgument('+2') // Strip the line     /*!999999\- enable the sandbox mode */
                    ->setPipe($this)
                    ->execute();

                break;

            case 'application/gzip':
                $this->setCommand('mysql')
                     ->setTimeout($this->timeout)
                     ->addArguments([
                         '-h',
                         $this->_connector->getHostname(),
                         '-u',
                         $this->_connector->getUsername(),
                         '-p' . $this->_connector->getPassword(),
                         '-B',
                         $this->_connector->getDatabase(),
                     ]);

                Grep::new()
                    ->setFilterReversed(true)
                    ->setFilterRegularExpression(true)
                    ->setFilter("USE \`tracking\`;")
                    ->setPipe(Zcat::new()
                                  ->setTimeout($this->timeout)
                                  ->setFileObject($file)
                                  ->setPipe(Tail::new()
                                                ->setTimeout($this->timeout)
                                                ->addArgument('+2') // Strip the line     /*!999999\- enable the sandbox mode */
                                                ->setPipe($this)))
                    ->executeReturnArray();

                break;

            default:
                throw new FileTypeNotSupportedException(tr('The specified file ":file" has the unsupported filetype ":type"', [
                    ':file' => $file->getSource(),
                    ':type' => $file->getMimetype(),
                ]));
        }

        return $_timer->stop();
    }


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethod $method
     *
     * @return int|null
     */
    public function execute(EnumExecuteMethod $method = EnumExecuteMethod::passthru): ?int
    {
        $password_file = static::createPasswordFile();

        try {
            // Build the process parameters, then execute
            $this->setCommand('mysql')
                 ->addArgument($this->hostname ? '--host' . $this->hostname : null)
                 ->addArgument($this->port ? '--port' . $this->port : null)
                 ->addArgument('--user' . $this->user)
                 ->addArgument('--defaults-extra-file=' . $password_file);

            if ($this->source) {
                $this->setInputRedirect($this->source);
            }

            if ($method === EnumExecuteMethod::background) {
                $pid = $this->executeBackground();
                Log::success(ts('Executed wget as a background process with PID ":pid"', [
                    ':pid' => $pid,
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
        $file = '/tmp/.' . Strings::getRandom(16) . '.cnf';
//        Process::new()
//            ->setServer($this->server)
//
//            ->, "rm ~/.my.cnf -f; touch ~/.my.cnf; chmod 0600 ~/.my.cnf; echo '[client]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysql]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldump]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldiff]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n' >> ~/.my.cnf");
    }


    /**
     * @return static
     */
    protected function deletePasswordFile(): static
    {
        PhoFile::new('~/.my.cnf', '~/.my.cnf')
            ->setServerObject($this->_server)
            ->secureDelete();

        return $this;
    }


    /**
     * Execute a query on a remote SSH server in a bash command
     *
     * @note This does NOT support bound variables!
     *
     * @param string $query
     * @param bool   $root
     * @param bool   $simple_quotes
     *
     * @return array
     * @todo : This method uses a password file which might be left behind if (for example) the connection would drop
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
     * @note: This was designed for Ubuntu Linux, and currently any support for other operating systems is NON-EXISTENT
     *        I will gladly add support later if I ever have time
     *
     * @param string $password
     *
     * @return void
     */
    public function importTimezones(string $password): void
    {
        // Test the specified root password
        $result = Process::new('mysql')
                         ->setSudo(true)
                         ->setTimeout(10)
                         ->addArguments([
                             '-p' . $password,
                             '-u',
                             'root',
                             'mysql',
                             '-e',
                             'SELECT 1\G',
                         ])
                         ->executeReturnString();

        if (!str_ends_with($result, '1: 1')) {
            throw new ProcessesException(tr('Failed to connect with MySQL server'));
        }

        // Import timezones
        $mysql = Process::new('mysql')
                        ->setSudo(true)
                        ->setTimeout(10)
                        ->addArguments([
                            '-p' . $password,
                            '-u',
                            'root',
                            'mysql',
                        ]);

        Process::new('mysql_tzinfo_to_sql', PhoRestrictions::new('/usr/share/zoneinfo'))
               ->setTimeout(10)
               ->addArgument('/usr/share/zoneinfo')
               ->setPipe($mysql)
               ->executePassthru();
    }


    /**
     * Returns the instance configuration
     *
     * @param string $database
     *
     * @return array
     */
    protected function getInstanceConfigForDatabase(string $database): array
    {
        return config()->getArray('databases.connectors.' . $database);

        foreach (Sql::getConnectorsObject() as $connector) {

        }
    }
}
