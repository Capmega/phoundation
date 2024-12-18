<?php

/**
 * Class SystemDService
 *
 *
 *
 * @see https://stackoverflow.com/questions/2036654/run-php-script-as-daemon-process
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Services\SystemD;

use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataDescription;
use Phoundation\Data\Traits\TraitDataOsUser;
use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Processes\Commands\SystemCtl;
use Phoundation\Os\Services\Exception\ServicesException;
use Phoundation\Os\Services\Service;
use Phoundation\Utils\Strings;


class SystemDService extends Service
{
    use TraitStaticMethodNew;
    use TraitDataDescription {
        getDescription as protected __getDescription;
    }
    use TraitDataOsUser;


    /**
     * Tracks the service script used to manage the service process
     *
     * @var PhoFileInterface|null $service_script
     */
    protected ?PhoFileInterface $service_script = null;

    /**
     * Tracks the service target script used to manage the service process
     *
     * @var PhoFileInterface|null $script
     */
    protected ?PhoFileInterface $target_script = null;

    /**
     * Tracks required services for this service
     *
     * @var IteratorInterface $requires
     */
    protected IteratorInterface $requires;

    /**
     * Tracks services that must be started up before this service starts
     *
     * @var IteratorInterface $after
     */
    protected IteratorInterface $after;


    /**
     * SystemDService class constructor
     */
    public function __construct()
    {
        Core::checkProcessIsRoot();
        parent::__construct();

        $validator = function(mixed $value) {
            return preg_match('/^[a-z]+?.service$/i', $value);
        };

        $this->detectOsProcessName();

        $this->after    = Iterator::new()->addValidator($validator);
        $this->requires = Iterator::new()->addValidator($validator);
    }


    /**
     * Returns true if the SystemD system file is installed
     *
     * @return bool
     */
    public function systemFileInstalled(): bool
    {
        return $this->getTargetScript()->exists();
    }


    /**
     * Ensures that the SystemD system file is installed
     *
     * @return static
     */
    public function ensureSystemFileInstalled(): static
    {
        if ($this->systemFileInstalled() and !FORCE) {
            return $this;
        }

        return $this->install();
    }


    /**
     * Checks that the SystemD system file is installed
     *
     * @return static
     */
    public function checkSystemFileInstalled(): static
    {
        if ($this->systemFileInstalled()) {
            return $this;
        }

        throw new ServicesException(tr('System file ":file" is not installed', [
            ':file' => $this->service_script->getBasename()
        ]));
    }


    /**
     * Ensures that the SystemD script exists and is installed
     *
     * @return static
     */
    public function ensureInstalled(): static
    {
        if (!$this->isInstalled()) {
            return $this->install();
        }

        return $this;
    }


    /**
     * Generates a SystemD system file and ensures its symlinked in /etc/systemd/system/
     *
     * @return static
     */
    public function install(): static
    {
        $this->generate()->symlinkTargetFromThis($this->target_script);
        return $this;
    }


    /**
     * Removes the symlinked SystemD script in /etc/systemd/system/
     *
     * @return static
     */
    public function uninstall(): static
    {
        $this->getTargetScript()->delete(false);
        $this->getServiceScript()->delete(false);

        return $this;
    }


    /**
     * Returns true if the current service is installed
     *
     * The current service is installed if a SystemD system script exists and is symlinked in /etc/systemd/system
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->getServiceScript()->exists();
    }


    /**
     * Returns true if the current service is enabled
     *
     * @note This requires the service file to be installed
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        if ($this->isInstalled()) {
            return SystemCtl::new($this->getOsProcessName())->isEnabled();
        }

        return false;
    }


    /**
     * Returns true if the current service is enabled
     *
     * @note This requires the service file to be installed
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        if ($this->isInstalled()) {
            return SystemCtl::new($this->getOsProcessName())->getStatusObject()->isEnabled();
        }

        return false;
    }


    /**
     * Returns true if the current service is enabled
     *
     * @note This requires the service file to be installed
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if ($this->isInstalled()) {
            return SystemCtl::new($this->getOsProcessName())->getStatusObject()->isEnabled();
        }

        return false;
    }


    /**
     * Generates a SystemD service script
     *
     * @return PhoFileInterface
     */
    protected function generate(): PhoFileInterface
    {
        return $this->getServiceScript()->putContents('[Unit]
Description=' . $this->getDescription() . '
Requires=' . Strings::force($this->requires, ' ') . ' 
After=' . Strings::force($this->requires, ' ') . '

[Service]
User=' . $this->getOsUser() . '
Type=simple
TimeoutSec=0
PIDFile=/var/run/' . $this->getOsProcessName() . '.pid
ExecStart=' . CliCommand::getCommandline() . ' > /dev/null 2>/dev/null
ExecStop=/bin/kill -TERM $MAINPID #It is the default you can change whats happens on stop command
ExecReload=/bin/kill -HUP $MAINPID
KillMode=process

Restart=on-failure
RestartSec=42s

StandardOutput=null #If you do not want to make toms of logs you can set it null if you sent a file or some other options it will send all PHP output to this one.
StandardError=/var/log/myphpdaemon.log
    [Install]
WantedBy=default.target');
    }


    /**
     * Returns the service script
     *
     * The current service is enabled if a SystemD system script exists and is symlinked in /etc/systemd/system
     *
     * @return PhoFileInterface
     */
    public function getServiceScript(): PhoFileInterface
    {
        if (empty($this->service_script)) {
            // Initialize the service_script object for the current process
            $this->service_script = PhoFile::newDataObject(
                'system/systemd/' . $this->getOsProcessName() . '.service',
                PhoRestrictions::newData(true, 'system/systemd/')
            );
        }

        return $this->service_script;
    }


    /**
     * Returns the service target script location
     *
     * @return PhoFileInterface
     */
    public function getTargetScript(): PhoFileInterface
    {
        if (empty($this->target_script)) {
            // Initialize the target_script object for the current process
            $this->target_script = PhoFile::new(
                '/etc/systemd/system/' . $this->getOsProcessName() . '.service',
                PhoRestrictions::newWritable('/etc/systemd/system')
            );
        }

        return $this->target_script;
    }


    /**
     * Starts the service cycle (specified as a callback) and performs automatic garbage collection
     *
     * @param callable $cycle
     * @return static
     */
    public function execute(callable $cycle): static
    {
        if ($this->processIsService()) {
            return parent::execute($cycle);
        }

        // This process is NOT yet a service. Instead of executing the specified cycle, execute SystemD commands
        $argv = ArgvValidator::new()
                             ->select('command')->isInArray(['start', 'stop', 'restart', 'status', 'show'])
                             ->validate();

        switch($argv['command']) {
            case 'start':
                $this->start();
                break;

            case 'restart':
                $this->restart();
                break;

            case 'stop':
                $this->stop();
                break;

            case 'status':
                $this->status();
                break;

            case 'show':
                $this->show();
                break;

        }

        return $this;
    }


    /**
     * Starts this process as a service
     *
     * @return static
     */
    public function start(): static
    {
        $this->ensureSystemFileInstalled();

        Log::action(tr('Starting service ":service" as a SystemD service', [
            ':service' => $this->getOsProcessName()
        ]));

        SystemCtl::new()
                 ->setOsProcessName($this->getOsProcessName())
                 ->start();
showdie();
        return $this;
    }


    /**
     * Restarts this service process
     *
     * @return static
     */
    public function restart(): static
    {
        $this->ensureSystemFileInstalled();

        SystemCtl::new()
                 ->setOsProcessName($this->getOsProcessName())
                 ->restart();

        return $this;
    }


    /**
     * Stops this process as a service
     *
     * @return static
     */
    public function stop(): static
    {
        $this->ensureSystemFileInstalled();

        Log::action(tr('Stopping SystemD service ":service"', [
            ':service' => $this->getOsProcessName()
        ]));

        SystemCtl::new()
                 ->setOsProcessName($this->getOsProcessName())
                 ->stop();

        return $this;
    }


    /**
     * Logs the status of this service
     *
     * @return static
     */
    public function status(): static
    {
        $this->ensureSystemFileInstalled();

        Log::printr(SystemCtl::new()
                             ->setOsProcessName($this->getOsProcessName())
                             ->status());

        return $this;
    }


    /**
     * Logs the details of this service
     *
     * @return static
     */
    public function show(): static
    {
        $this->ensureSystemFileInstalled();

        Log::printr(SystemCtl::new()
                             ->setOsProcessName($this->getOsProcessName())
                             ->show());

        return $this;
    }


    /**
     * Executes the specified SystemD method
     *
     * @param string $command
     *
     * @return $this
     */
    public function executeCommand(string $command): static
    {
        switch ($command) {
            case 'start':
                return $this->start();

            case 'stop':
                return $this->stop();

            case 'restart':
                return $this->restart();

            case 'enable':
                return $this->ensureInstalled();

            case 'disable':
                return $this->uninstall();

            default:
                throw new OutOfBoundsException(tr('Unknown SystemD service command ":command" specified', [
                    ':command' => $command
                ]));
        }
    }


    /**
     * Returns the description for the current process
     *
     * @return string
     */
    public function getDescription(): string
    {
        return CliCommand::getHelp();
    }
}
