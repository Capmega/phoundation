<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands\Databases;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataHostnamePort;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataUserPass;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Commands\Command;
use Phoundation\Processes\Process;
use Throwable;

/**
 * Class MySql
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class MySql extends Command
{
    use DataHostnamePort;
    use DataUserPass;
    use DataSource;


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param bool $background
     * @return int|null
     */
    public function execute(bool $background = false): ?int
    {
        static::createPasswordFile();

        try {
            // Build the process parameters, then execute
            $process = Process::new('mysql')
                ->addArguments($this->hostname ? ['--host'    , $this->hostname] : null)
                ->addArguments($this->port     ? ['--port'    , $this->port]     : null)
                ->addArguments($this->user     ? ['--user'    , $this->user]     : null)
                ->addArguments($this->pass     ? ['--password', $this->pass]     : null);

            if ($this->source) {
                $process->setInputRedirect($this->source);
            }

            if ($background) {
                $pid = $this->process->executeBackground();

                Log::success(tr('Executed wget as a background process with PID ":pid"', [
                    ':pid' => $pid
                ]), 4);

                return $pid;

            }

            $results = $this->process->executeReturnArray();

            Log::notice($results, 4);
            static::deletePasswordFile();
            return null;

        } catch (Throwable $e) {
            // Ensure the password file is gone before we continue
            static::deletePasswordFile();
            throw $e;
        }
    }


    /**
     * Creates a MySQL password file
     *
     * @return static
     */
    protected function createPasswordFile(): static
    {
        static::deletePasswordFile();
//        file_put_contents('~/.my.cnf')
//        servers_exec($server, "rm ~/.my.cnf -f; touch ~/.my.cnf; chmod 0600 ~/.my.cnf; echo '[client]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysql]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldump]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldiff]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n' >> ~/.my.cnf");
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
}