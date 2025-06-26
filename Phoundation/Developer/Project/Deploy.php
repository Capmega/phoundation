<?php

/**
 * Class Deploy
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Project;

use Phoundation\Accounts\Config\Config;
use Phoundation\Core\Hooks\Hook;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Deploy\Exception\DeployException;
use Phoundation\Developer\Project\Interfaces\DeployInterface;
use Phoundation\Developer\Project\Interfaces\ProjectInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Processes\Commands\Rsync;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Process;
use Phoundation\Servers\Server;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Throwable;

class Deploy implements DeployInterface
{
    /**
     * The project we're deploying
     *
     * @var ProjectInterface $project
     */
    protected ProjectInterface $project;

    /**
     * The configuration keys with their default values
     *
     * @var array $keys
     */
    protected array $keys = [
        'server'            => [],    // The target server to which we will deploy
        'hooks'             => [],    // The hooks to execute
        'ignore_changes'    => false, // If true, git changes will be ignored and deploy will be executed anyway
        'content_check'     => true,  // If true will check content
        'execute_hooks'     => true,  // If true will execute configured deployment hooks
        'sync'              => true,  // If true will sync before (optionally) executing an init before deploying
        'init'              => true,  // If true will execute a project init before deploying
        'notify'            => true,  // If true will send out notifications about this deploy
        'minify'            => true,  // If true will execute CDN file minification
        'push'              => true,  // If true will push changes to the remote git repository
        'parallel'          => true,  // If true will rsync to a parallel copy of the project instead of to the project directly. This can cause the project to be in an unknown state during deployment
        'translate'         => true,  // If true will translate the project before deploying
        'bom_check'         => true,  // If true the system will execute a BOM check on the files
        'update_file_modes' => true,  // If true will fix file modes after rsync
        'backup'            => true,  // If true the system will make a backup on the target environments
        'stash'             => true,  // If true, git will stash changes (if any) and pop those afterwards
        'update_sitemap'    => true,  // If true will update the sitemap before deploying
        'force'             => false, // If true will ignore halting issue and force deploy. DANGEROUS
        'test_syntax'       => true,  // If true will perform a syntax check before deploying
        'test_unit'         => true,  // If true will execute a unit test before deploying
    ];

    /**
     * The configuration as it will be used to deploy the project
     *
     * @var array $configuration
     */
    protected array $configuration;

    /**
     * The configuration modified as specified from the command line
     *
     * @var array $modifiers
     */
    protected array $modifiers = [];

    /**
     * The targets to which we are deploying
     *
     * @var array $targets
     */
    protected array $targets;

    /**
     * Tracks configuration for the target environment
     *
     * @var array $env_config
     */
    protected array $env_config;


    /**
     * Deploy class constructor
     *
     * @param ProjectInterface $project
     * @param array|null       $target_environments
     */
    public function __construct(ProjectInterface $project, array|null $target_environments)
    {
        $this->project       = $project;
        $this->configuration = $this->getConfig();
        $this->targets       = $target_environments ?? Arrays::force($this->configuration['targets']);
    }


    /**
     * Return all deployment configuration
     *
     * @return array
     * @throws Throwable
     */
    protected function getConfig(): array
    {
        return config('deploy')->getArray('', require_keys: 'targets');
    }


    /**
     * Start the deployment process
     *
     * @return static
     */
    public function execute(): static
    {
        if (!$this->targets) {
            throw new OutOfBoundsException(tr('No deployment target environments configured or specified on the commandline'));
        }

        foreach ($this->targets as $environment) {
            // Read environment config, update global config and then check which sections should be executed
            $this->env_config                     =  $this->getEnvironmentConfig($environment);
            $this->configuration['execute_hooks'] =  $this->env_config['execute_hooks'];
            $this->configuration['force']         = ($this->env_config['force'] or FORCE);

            if (!$this->env_config['ignore_changes']) {
                if ($this->project->getGit()->hasChanges()) {
                    throw new DeployException(tr('The project has pending git changes. Please commit or stash first'));
                }
            }

            $this->executeHook('start')
                 ->executeContentCheck()
                 ->executeBomCheck()
                 ->executeSyntaxCheck()
                 ->executeUnitTests();

            // TODO: Convert the rest of this method to separate methods

            static::executeHook('post-test-unit,pre-sync');

            if ($this->env_config['sync']) {
                Log::action(ts('Executing system synchronisation'));

            }

            static::executeHook('post-sync,pre-init');

            if ($this->env_config['init']) {
                // Initialize the system
                Log::action(ts('Executing project initialization'));
                Libraries::initialize(true, true, true, 'Executed by the Phoundation deployment system');
            }

            static::executeHook('post-init,pre-translate');

            if ($this->env_config['translate']) {
                Log::action(ts('Executing translation'));

            }

            static::executeHook('post-translate,pre-minify');

            if ($this->env_config['minify']) {
                Log::action(ts('Executing CDN data minification'));

            }

            static::executeHook('post-minify,pre-update-sitemap');

            if ($this->env_config['update_sitemap']) {
                Log::action(ts('Executing sitemap update'));

            }

            static::executeHook('post-update-sitemap,pre-push');

            if ($this->env_config['push']) {
                Log::action(ts('Pushing updates to remote GIT'));
            }

            static::executeHook('post-push,pre-connect,pre-backup');

            if ($this->env_config['backup']) {
                Log::action(ts('Executing remote backup'));
            }

            static::executeHook('post-backup,pre-parallel');

            if ($this->env_config['parallel']) {
                Log::action(ts('Executing remote project copy to prepare for parallel rsync'));
            }

            static::executeHook('post-parallel,pre-rsync');

            // Build the rsync target
            if (empty($this->env_config['server']['host'])) {
                throw new OutOfBoundsException(tr('No host configured for target ":target"', [
                    ':target' => $environment,
                ]));
            }

            $rsync_target = Strings::ensureEndsWith($this->env_config['server']['host'], ':');
            $rsync_target .= $this->env_config['server']['path'];

            if ($this->env_config['server']['user']) {
                $rsync_target = $this->env_config['server']['user'] . '@' . $rsync_target;
            }

// TODO Add support for languages!
//            foreach (Translations::getLanguages() as $language)
            // Add the project directory to the rsync_target
            $project = Strings::fromReverse(Strings::ensureEndsNotWith(DIRECTORY_ROOT, '/'), '/');

            // Execute rsync
            Log::action(ts('Executing rsync to target ":target"', [
                ':target' => Strings::ensureEndsWith($rsync_target, '/') . $project,
            ]));

            // First ensure the target base directory exists!
            Process::new('mkdir')
                   ->setServerObject(Server::new()
                                           ->setHostname($this->env_config['server']['host'])
                                           ->setPort($this->env_config['server']['port'])
                                           ->setSshAccountObject())
                   ->addArguments(['-p', $this->env_config['server']['path']])
                   ->execute(EnumExecuteMethod::noReturn);

            // And then rsync!
            Rsync::new()
                 ->setArchive(true)
                 ->setVerbose(true)
                 ->setCompress(true)
                 ->setRemoteSudo($this->env_config['server']['sudo'])
                 ->setPort($this->env_config['server']['port'])
                 ->setSource(DIRECTORY_ROOT)
                 ->setTarget(Strings::ensureEndsWith($rsync_target, '/') . $project)
                 ->addExclude('.git')
                 ->addExclude('.gitignore')
                 ->addExclude('nohup.out')
                 ->addExclude('data/run')
                 ->addExclude('data/log')
                 ->addExclude('data/tmp')
                 ->addExclude('data/cache')
                 ->addExclude('data/system')
                 ->addExclude('data/sources')
                 ->addExclude('data/cookies')
                 ->addExclude('data/sessions')
                 ->execute();

            static::executeHook('post-rsync,pre-update-file-modes');

            if ($this->env_config['update_file_modes']) {
                Log::action(ts('Executing file mode update'));
            }

            static::executeHook('post-update-file-modes,pre-notify');

            if ($this->env_config['notify']) {
                Log::action(ts('Sending out notifications'));

            }

            static::executeHook('post-notify,finish');
        }

        return $this;
    }


    /**
     * Executes the content check
     *
     * ???
     *
     * @return static
     */
    protected function executeUnitTests(): static
    {
        static::executeHook('pre-unit-test');

        if (!$this->env_config['unit_test']) {
            Log::action(ts('Executing unit tests'));
        }

        static::executeHook('post-unit-test');
        return $this;
    }


    /**
     * Executes the content check
     *
     * ???
     *
     * @return static
     */
    protected function executeContentCheck(): static
    {
        static::executeHook('pre-content-check');

        if (!$this->env_config['content_check']) {
            Log::action(ts('Executing content check'));
        }

        static::executeHook('post-content-check');
        return $this;
    }


    /**
     * ???
     *
     * @return static
     */
    protected function executeSyntaxCheck(): static
    {
        static::executeHook('pre-test-syntax');

        if ($this->env_config['bom_check']) {
            Log::action(ts('Executing BOM check'));
        }

        static::executeHook('post-test-syntax');
        return $this;
    }


    /**
     * Executes the BOM (Byte Order Marker) check
     *
     * This method will execute a test on all files to ensure they do not start with the 4 byte UTF BOM string
     *
     * @return static
     */
    public function executeBomCheck(): static
    {
        static::executeHook('pre-bom-check');

        if ($this->env_config['bom_check']) {
            Log::action(ts('Executing BOM check'));
//                BomDirectory::new(DIRECTORY_ROOT, DIRECTORY_ROOT)->clearBom();
        }

        static::executeHook('post-bom-check');
        return $this;
    }


    /**
     * Loads and returns the configuration for the specified environment
     *
     * @param $environment
     *
     * @return array
     */
    protected function getEnvironmentConfig($environment): array
    {
        $return = [];

        if (!Config::environmentExists('deploy/' . $environment)) {
            throw new DeployException(tr('The specified environment ":environment" has no configuration file available in DIRECTORY_ROOT/config/deploy/', [
                ':environment' => $environment,
            ]));
        }

        $config = config('deploy', $environment)->get('');

        foreach ($this->keys as $key => $default) {
            $key = str_replace('-', '_', $key);
            switch ($key) {
                case 'server':
                    Arrays::default($return, $key, get_safe_typed('array', $config, $key, $default));
                    Arrays::ensure($return[$key], 'host,path,port,user,sudo');
                    break;

                case 'hooks':
                    Arrays::default($return, $key, get_safe_typed('array', $config, $key, $default));
                    break;

                default:
                    Arrays::default($return, $key, get_safe_typed('bool', $config, $key, $default));
            }

            if (array_key_exists($key, $this->modifiers)) {
                if ($this->modifiers[$key] !== null) {
                    // Override was specified on the command line
                    $return[$key] = $this->modifiers[$key];
                }
            }
        }

        return $return;
    }


    /**
     * Try to execute the specified hook
     *
     * @param array|string $hooks
     *
     * @return static
     */
    protected function executeHook(array|string $hooks): static
    {
        if ($this->configuration['execute_hooks']) {
            Hook::new('deploy')
                ->execute($hooks);
        }

        return $this;
    }


    /**
     * Sets if compression should be used or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setCompress(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('compress', $do, $dont);
    }


    /**
     * Sets the requested modifier configuration
     *
     * @param string    $modifier
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    protected function setModifier(string $modifier, ?bool $do, ?bool $dont): static
    {
        if ($do) {
            if ($dont) {
                throw new OutOfBoundsException(tr('Both "do" and "dont" modifiers were specified for ":modifier". Please specify only one', [
                    ':modifier' => $modifier,
                ]));

            }
            $value = true;

        } elseif ($dont) {
            $value = false;

        } else {
            $value = null;
        }
        $this->modifiers[$modifier] = $value;

        return $this;
    }


    /**
     * Sets if hooks should be executed or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setExecuteHooks(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('execute_hooks', $do, $dont);
    }


    /**
     * Sets if content checks should be executed or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setContentCheck(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('content_check', $do, $dont);
    }


    /**
     * Sets if init should be executed or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setInit(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('init', $do, $dont);
    }


    /**
     * Sets if notifications should be sent out or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setNotify(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('notifications', $do, $dont);
    }


    /**
     * Sets if git should push all changes to another repository or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setPush(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('push', $do, $dont);
    }


    /**
     * Sets if rsync should copy to a parallel version of the site or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setParallel(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('parallel', $do, $dont);
    }


    /**
     * Sets if the sitemap should be updated before rsync or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setUpdateSitemap(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('update_sitemap', $do, $dont);
    }


    /**
     * Sets if the project should be translated before deployment or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setTranslate(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('translate', $do, $dont);
    }


    /**
     * Sets if the project should be scanned for BOM bytes before deployment
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setBomCheck(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('bom_check', $do, $dont);
    }


    /**
     * Sets if the project on the target environment should be backed up before deployment
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setBackup(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('backup', $do, $dont);
    }


    /**
     * Sets if git will stash changes before deployment and pop after
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setStash(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('stash', $do, $dont);
    }


    /**
     * Sets if PHP will be syntax checked before deployment
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setTestSyntax(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('text_syntax', $do, $dont);
    }


    /**
     * Sets if unit Tests will be executed before deployment or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setTestUnit(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('test_unit', $do, $dont);
    }


    /**
     * Sets if git changes should be ignored or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     *
     * @return static
     */
    public function setIgnoreChanges(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('ignore_changes', $do, $dont);
    }
}
