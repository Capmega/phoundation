<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Commands\Exception\CommandNotFoundException;
use Phoundation\Processes\Commands\Exception\CommandsException;
use Phoundation\Processes\Exception\ProcessFailedException;
use Phoundation\Processes\Process;


/**
 * Class SystemCommands
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class SystemCommands extends Command
{
    /**
     * Returns the realpath for the specified command
     *
     * @param string $command The command for which the realpath must be known
     * @return string The real path for the specified command
     */
    public function which(string $command): string
    {
        static $cache = [];

        // Do we have this which command in cache?
        if (array_key_exists($command, $cache)) {
            return $cache[$command];
        }

        $this->process
            ->setCommand('which', false)
            ->addArgument($command)
            ->setRegisterRunfile(false)
            ->setTimeout(1);

        try {
            $output   = $this->process->executeReturnArray();
            $result   = reset($output);
            $realpath = realpath($result);

            if (!$realpath) {
                // So which gave us a path that doesn't exist or that we can't access
                throw new CommandsException(tr('Failed to get realpath for which result ":result" for command  ":command"', [
                    ':command' => $command,
                    ':result' => $result
                ]));
            }

            // Cache and return
            $cache[$command] = $realpath;
            return $realpath;

        } catch (ProcessFailedException $e) {
            // The command which failed, likely it could not find the requested command
            Command::handleException('which', $e, function($first_line, $last_line, $e) use ($command) {
                if ($e->getCode() == 1) {
                    if (!$e->getData()['output']) {
                        throw new CommandNotFoundException(tr('Could not find command ":command"', [
                            ':command' => $command
                        ]));
                    }
                }
            });
        }
    }


    /**
     * Returns the user, group
     *
     * @param string $section
     * @return int
     */
    public function id(string $section): int
    {
        if (($section != 'u') and ($section != 'g')) {
            throw new OutOfBoundsException(tr('Invalid section ":section" specified. This value can only be "u" or "g"', [
                ':section' => $section
            ]));
        }

        $this->process
            ->setCommand('id')
            ->addArgument('-' . $section)
            ->setTimeout(1);

        try {
            $output = $this->process->executeReturnArray();
            $result = reset($output);

            if (!$result or !is_numeric($result)) {
                // So which gave us a path that doesn't exist or that we can't access
                throw new CommandsException(tr('Failed to get id'));
            }

            return (int) $result;

        } catch (ProcessFailedException $e) {
            // The command id failed
            Command::handleException('rm', $e);
        }
    }


    /**
     * Install the specified packages
     *
     * @param array|string $packages
     * @return void
     */
    public function aptGetInstall(array|string $packages): void
    {
        Log::action(tr('Installing packages ":packages"', [':packages' => $packages]));

        $this->process
            ->setCommand('apt-get')
            ->setSudo(true)
            ->addArguments(['-y', 'install'])
            ->addArguments($packages)
            ->setTimeout(120)
            ->executePassthru();
    }


    /**
     * Returns the available amount of memory
     *
     * @return array
     */
    public function free(): array
    {
        $output = $this->process
            ->setCommand('free')
            ->setTimeout(1)
            ->executeReturnArray();

        // Parse the output
        $return = [
            'memory' => [],
            'swap'   => [],
        ];

        foreach ($output as $line_number => $line) {
            if (!$line_number) {
                continue;
            }

            $line = Strings::noDouble($line, ' ', ' ');

            $data['total']       = Strings::until(Strings::skip($line, ' ', 1, true), ' ');
            $data['used']        = Strings::until(Strings::skip($line, ' ', 2, true), ' ');
            $data['free']        = Strings::until(Strings::skip($line, ' ', 3, true), ' ');
            $data['shared']      = Strings::until(Strings::skip($line, ' ', 4, true), ' ');
            $data['buff/cached'] = Strings::until(Strings::skip($line, ' ', 5, true), ' ');
            $data['available']   = Strings::until(Strings::skip($line, ' ', 6, true), ' ');

            switch ($line_number) {
                case 1:
                    $return['memory'] = $data;
                    break;

                case 2:
                    unset($data['shared']);
                    unset($data['buff/cached']);
                    unset($data['available']);

                    $return['swap'] = $data;
                    break;

                default:
                    Log::warning(tr('Ignoring unknown output ":line" from the command "free"', [':line' => $line]));
            }
        }

        return $return;
    }
}