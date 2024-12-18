<?php

/**
 * Class SystemDService
 *
 *
 * @see https://stackoverflow.com/questions/2036654/run-php-script-as-daemon-process
 * @see https://www.freedesktop.org/software/systemd/man/latest/systemd.unit.html
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Services\SystemD;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataDescription;
use Phoundation\Data\Traits\TraitDataOsUser;
use Phoundation\Data\Traits\TraitStaticMethodNewArrayConfiguration;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Processes\Commands\SystemCtl;
use Phoundation\Os\Services\Exception\ServicesException;
use Phoundation\Os\Services\Service;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use TypeError;


class SystemDService extends Service
{
    use TraitStaticMethodNewArrayConfiguration;
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
     * Tracks services that must be started up BEFORE this service starts
     *
     * @var IteratorInterface $after
     */
    protected IteratorInterface $after;

    /**
     * Tracks services that must be started up AFTER this service starts
     *
     * @var IteratorInterface $before
     */
    protected IteratorInterface $before;

    /**
     * Tracks (weak) requirement dependencies on other units.
     *
     * @var IteratorInterface $wants
     */
    protected IteratorInterface $wants;

    /**
     * Tracks (stronger) requirement dependencies on other units.
     *
     * @var IteratorInterface $requires
     */
    protected IteratorInterface $requires;

    /**
     * Tracks similar to requires, but shows as being requisite for this service
     *
     * @var IteratorInterface $requisites
     */
    protected IteratorInterface $requisites;

    /**
     * Tracks (strongest) requirement dependencies on other units. When these units shut down, this service will so too.
     *
     * @var IteratorInterface $binds_to
     */
    protected IteratorInterface $binds_to;

    /**
     * The configuration requested to be applied
     *
     * @var array|null $configuration
     */
    protected ?array $configuration;


    /**
     * SystemDService class constructor
     */
    public function __construct(?array $configuration = null)
    {
        Core::checkProcessIsRoot();

        parent::__construct();

        $this->detectOsProcessName()
             ->applyConfiguration($configuration);
    }


    /**
     * Configures the SystemDService for this process and executes the service commands specified on the command line
     *
     * @param array $configuration
     *
     * @return void
     */
    public static function configure(array $configuration): void
    {
        if (CliCommand::getServiceCommands()) {
            static::new($configuration)->executeCommand(CliCommand::getServiceCommands());
        }
    }


    /**
     * Returns a new service unit iterator object
     *
     * @param string $name
     *
     * @return IteratorInterface
     */
    protected function newIterator(string $name): IteratorInterface
    {
        return Iterator::new()->setName($name)
                              ->addValidator(function (mixed $value) {
            return preg_match('/^[a-z-]+?\.service$/i', $value);
        }, 'valid service unit name');
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

        SystemCtl::new()->daemonReload();

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
            return SystemCtl::new($this->getOsProcessName())
                            ->getStatusObject()
                            ->isEnabled();
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
            return SystemCtl::new($this->getOsProcessName())
                            ->getStatusObject()
                            ->isEnabled();
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
        return $this->getServiceScript()
                    ->putContents('[Unit]
Description=' . $this->getDescription() . '
'. ($this->hasAfter()      ? 'After='     . Strings::force($this->after     , ' ') . PHP_EOL : null) . '
'. ($this->hasBefore()     ? 'Before='    . Strings::force($this->before    , ' ') . PHP_EOL : null) . '
'. ($this->hasWants()      ? 'Wants='     . Strings::force($this->wants     , ' ') . PHP_EOL : null) . '
'. ($this->hasRequires()   ? 'Requires='  . Strings::force($this->requires  , ' ') . PHP_EOL : null) . '
'. ($this->hasRequisites() ? 'Requisite=' . Strings::force($this->requisites, ' ') . PHP_EOL : null) . '
'. ($this->hasBindsTo()    ? 'BindsTo='   . Strings::force($this->binds_to  , ' ') . PHP_EOL : null) . '

[Service]
User=' . $this->getOsUser() . '
Environment="PHOUNDATION_' . PROJECT . '_ENVIRONMENT=' . ENVIRONMENT . '"
Type=simple
TimeoutSec=0
PIDFile=/var/run/' . $this->getOsProcessName() . '.pid
ExecStart=' . CliCommand::getCommandline() . ' > /dev/null 2>/dev/null
ExecStop=/bin/kill -TERM $MAINPID #It is the default you can change whats happens on stop command
ExecReload=/bin/kill -HUP $MAINPID
KillMode=process

Restart=on-failure
RestartSec=5s

StandardOutput=null #If you do not want to make toms of logs you can set it null if you sent a file or some other options it will send all PHP output to this one.
StandardError=/var/log/pho/.log
[Install]
WantedBy=multi-user.target');
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
     *
     * @return static
     */
    public function execute(callable $cycle): static
    {
        if ($this->processIsService()) {
            return parent::execute($cycle);
        }
        // This process is NOT yet a service. Instead of executing the specified cycle, execute SystemD commands
        $argv = ArgvValidator::new()
                             ->select('command')
                             ->isInArray(['start', 'stop', 'restart', 'status', 'show'])
                             ->validate();
        switch ($argv['command']) {
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
     * Enables this process as a service that will automatically start at system boot
     *
     * @return static
     */
    public function enable(): static
    {
        $this->ensureSystemFileInstalled();

        Log::action(tr('Enabling auto startup for SystemD service ":service"', [
            ':service' => $this->getOsProcessName()
        ]));

        SystemCtl::new()
                 ->setDebug(true)
                 ->setOsProcessName($this->getOsProcessName())
                 ->enable();

        return $this;
    }


    /**
     * Disables this process from being a service that will automatically start at system boot
     *
     * @return static
     */
    public function disable(): static
    {
        Log::action(tr('Disabling auto startup for SystemD service ":service"', [
            ':service' => $this->getOsProcessName()
        ]));

        SystemCtl::new()
                 ->setDebug(true)
                 ->setOsProcessName($this->getOsProcessName())
                 ->disable();

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
                 ->setDebug(true)
                 ->setOsProcessName($this->getOsProcessName())
                 ->start();

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
     * Parses the specified commands, applies settings, and returns an array with requested SystemD commands
     *
     * @param string $commands
     *
     * @return array
     */
    protected function parseCommands(string $commands): array
    {
        $commands = explode(',', $commands);
        $return   = [];
        $settings = [];

        foreach ($commands as $command) {
            $command = trim($command);

            switch ($command) {
                case 'start':
                    // no break

                case 'stop':
                    // no break

                case 'restart':
                    // no break

                case 'enable':
                    // no break

                case 'disable':
                    $return[] = $command;
                    break;

                default:
                    $settings[] = $command;
            }
        }

        $this->applyCommandLineConfiguration($settings, false);
        return $return;
    }


    /**
     * Executes the specified SystemD method
     *
     * @param string $commands
     *
     * @return never
     */
    #[NoReturn] public function executeCommand(string $commands): never
    {
        $commands = $this->parseCommands($commands);

        foreach ($commands as $command) {
            switch ($command) {
                case 'start':
                    $this->start();
                    break;

                case 'stop':
                    $this->stop();
                    break;

                case 'restart':
                    $this->restart();
                    break;

                case 'enable':
                    $this->enable();
                    break;

                case 'disable':
                    $this->disable();
                    break;

                case 'uninstall':
                    $this->uninstall();
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown SystemD service command ":command" specified', [
                        ':command' => $commands
                    ]));
            }
        }

        exit();
    }


    /**
     * Returns the list with services that are required for this service
     *
     * @return IteratorInterface
     */
    public function getRequiresObject(): IteratorInterface
    {
        if (empty($this->before)) {
            $this->requires = $this->newIterator('requires');
        }

        return $this->requires;
    }


    /**
     * Adds a list of services that are required for this service
     *
     * @param IteratorInterface|array|string $services
     *
     * @return void
     */
    public function addRequires(IteratorInterface|array|string $services): void
    {
        $services = Arrays::force($services);

        foreach ($services as $service) {
            $this->getRequiresObject()->add($service);
        }
    }


    /**
     * Returns true if this SystemDService has "requires" rules configured
     *
     * @return bool
     */
    public function hasRequires(): bool
    {
        return isset($this->requires) and $this->requires->isNotEmpty();
    }


    /**
     * Returns the list with services that must start BEFORE this service
     *
     * @return IteratorInterface
     */
    public function getRequisitesObject(): IteratorInterface
    {
        if (empty($this->requisites)) {
            $this->requisites = $this->newIterator('requisites');
        }

        return $this->requisites;
    }


    /**
     * Adds a list of services that are required for this service
     *
     * @param IteratorInterface|array|string $services
     *
     * @return void
     */
    public function addRequisites(IteratorInterface|array|string $services): void
    {
        $services = Arrays::force($services);

        foreach ($services as $service) {
            $this->getRequisitesObject()->add($service);
        }
    }


    /**
     * Returns true if this SystemDService has "requisites" rules configured
     *
     * @return bool
     */
    public function hasRequisites(): bool
    {
        return isset($this->requisites) and $this->requisites->isNotEmpty();
    }


    /**
     * Returns the list with services that must start BEFORE this service
     *
     * @return IteratorInterface
     */
    public function getWantsObject(): IteratorInterface
    {
        if (empty($this->wants)) {
            $this->wants = $this->newIterator('wants');
        }

        return $this->wants;
    }


    /**
     * Adds the list of services that must start BEFORE this service
     *
     * @param IteratorInterface|array|string $services
     *
     * @return void
     */
    public function addWants(IteratorInterface|array|string $services): void
    {
        $services = Arrays::force($services);

        foreach ($services as $service) {
            $this->getWantsObject()->add($service);
        }
    }


    /**
     * Returns true if this SystemDService has "wants" rules configured
     *
     * @return bool
     */
    public function hasWants(): bool
    {
        return isset($this->wants) and $this->wants->isNotEmpty();
    }


    /**
     * Returns the list with services that must start BEFORE this service
     *
     * @return IteratorInterface
     */
    public function getAfterObject(): IteratorInterface
    {
        if (empty($this->after)) {
            $this->after = $this->newIterator('after');
        }

        return $this->after;
    }


    /**
     * Adds the list of services that must start BEFORE this service
     *
     * @param IteratorInterface|array|string $services
     *
     * @return void
     */
    public function addAfter(IteratorInterface|array|string $services): void
    {
        $services = Arrays::force($services);

        foreach ($services as $service) {
            $this->getAfterObject()->add($service);
        }
    }


    /**
     * Returns true if this SystemDService has "after" rules configured
     *
     * @return bool
     */
    public function hasAfter(): bool
    {
        return isset($this->after) and $this->after->isNotEmpty();
    }


    /**
     * Returns the list with services that must start AFTER this service
     *
     * @return IteratorInterface
     */
    public function getBeforeObject(): IteratorInterface
    {
        if (empty($this->before)) {
            $this->before = $this->newIterator('before');
        }

        return $this->before;
    }


    /**
     * Adds the list of services that must start AFTER this service
     *
     * @param IteratorInterface|array|string $services
     *
     * @return void
     */
    public function addBefore(IteratorInterface|array|string $services): void
    {
        $services = Arrays::force($services);

        foreach ($services as $service) {
            $this->getBeforeObject()->add($service);
        }
    }


    /**
     * Returns true if this SystemDService has "before" rules configured
     *
     * @return bool
     */
    public function hasBefore(): bool
    {
        return isset($this->before) and $this->before->isNotEmpty();
    }


    /**
     * Returns the list with services that are absolutely required for this service.
     *
     * @return IteratorInterface
     */
    public function getBindsToObject(): IteratorInterface
    {
        if (empty($this->binds_to)) {
            $this->binds_to = $this->newIterator('binds_to');
        }

        return $this->binds_to;
    }


    /**
     * Adds a list with services that are absolutely required for this service.
     *
     * @param IteratorInterface|array|string $services
     *
     * @return void
     */
    public function addBindsTo(IteratorInterface|array|string $services): void
    {
        $services = Arrays::force($services);

        foreach ($services as $service) {
            $this->getBindsToObject()->add($service);
        }
    }


    /**
     * Returns true if this SystemDService has "binds_to" rules configured
     *
     * @return bool
     */
    public function hasBindsTo(): bool
    {
        return isset($this->binds_to) and $this->binds_to->isNotEmpty();
    }


    /**
     * Returns the requested configuration for this SystemDService object
     *
     * @return array|null
     */
    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }


    /**
     * Configures the SystemDService for this process
     *
     * @param array|null $configuration
     *
     * @return bool
     */
    protected function applyCommandLineConfiguration(?array $configuration): bool
    {
        try {
            $parsed = [];

            if ($configuration) {
                foreach ($configuration  as $item) {
                    $key   = Strings::until($item, '=');
                    $value = Strings::from ($item, '=');

                    $parsed[$key] = $value;
                }
            }

            return $this->applyConfiguration($parsed);

        } catch (OutOfBoundsException $e) {
            if ($e->getDataKey('iterator')) {
                $key = Strings::from($e->getDataKey('iterator'), '/');

                throw new ValidationFailedException(tr('Specified systemd service configuration value ":value" for key ":key" is not valid', [
                    ':value' => $e->getDataKey('value'),
                    ':key'   => $key
                ]), $e);
            }

            throw $e;
        }
    }


    /**
     * Configures the SystemDService for this process
     *
     * @param array|null $configuration
     *
     * @return bool
     */
    protected function applyConfiguration(?array $configuration): bool
    {
        $this->configuration = $configuration;

        if (empty($configuration)) {
            return false;
        }

        try {
            foreach ($configuration as $key => $value) {
                switch ($key) {
                    case 'after':
                        $this->addAfter($value);
                        break;

                    case 'before':
                        $this->addBefore($value);
                        break;

                    case 'wants':
                        $this->addWants($value);
                        break;

                    case 'requires':
                        $this->addRequires($value);
                        break;

                    case 'requisites':
                        $this->addRequisites($value);
                        break;

                    case 'binds-to':
                        // no break

                    case 'binds_to':
                        // no break

                    case 'bindsto':
                        $this->addBindsTo($value);
                        break;

                    case 'user':
                        $this->setOsUser($value);
                        break;

                    default:
                        throw new OutOfBoundsException(tr('Unknown SystemDService configuration key ":key" specified', [
                            ':key' => $key
                        ]));
                }
            }

        } catch (TypeError $e) {
            $message = Strings::from($e->getMessage(), 'must be of type');
            $message = Strings::until($message, ', ');
            $message = trim($message);

            throw new OutOfBoundsException(tr('Invalid value ":value" specified for key ":key", it should have one of types ":type"', [
                ':key'   => $key,
                ':value' => $value,
                ':type'  => $message,
            ]), $e);
        }

        return true;
    }


    /**
     * Returns a SystemDStatus object
     *
     * @return SystemDStatusInterface
     */
    public function getStatusObject(): SystemDStatusInterface
    {
        return new SystemDStatus();
    }


    /**
     * Returns the SystemD description for the current process
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'pho ' . CliCommand::getCommandsString();
    }
}
