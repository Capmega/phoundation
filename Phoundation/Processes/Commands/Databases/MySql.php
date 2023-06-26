<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands\Databases;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Traits\DataHostnamePort;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataUserPass;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Processes\Commands\Command;
use Phoundation\Processes\Enum\ExecuteMethod;
use Phoundation\Processes\Process;
use Throwable;


/**
 * Class MySql
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param ExecuteMethod $method
     * @return int|null
     */
    public function execute(ExecuteMethod $method = ExecuteMethod::passthru): ?int
    {
        $password_file = static::createPasswordFile();

        try {
            // Build the process parameters, then execute
            $process = Process::new('mysql')
                ->addArgument($this->hostname ? '--host' . $this->hostname : null)
                ->addArgument($this->port     ? '--port' . $this->port     : null)
                ->addArgument('--user' . $this->user)
                ->addArgument('--defaults-extra-file=' . $password_file);

            if ($this->source) {
                $process->setInputRedirect($this->source);
            }

            if ($method === ExecuteMethod::background) {
                $pid = $this->process->executeBackground();

                Log::success(tr('Executed wget as a background process with PID ":pid"', [
                    ':pid' => $pid
                ]), 4);

                // TODO Password file should only be deleted after execution has finished
                static::deletePasswordFile();
                return $pid;
            }

            $results = $this->process->execute($method);

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
        Process::new()
            ->setServer($this->server)

            ->, "rm ~/.my.cnf -f; touch ~/.my.cnf; chmod 0600 ~/.my.cnf; echo '[client]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysql]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldump]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldiff]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n' >> ~/.my.cnf");
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