<?php

namespace Phoundation\Developer\Project;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Hooks\Hook;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Deploy\Exception\DeployException;
use Phoundation\Developer\Project\Interfaces\DeployInterface;
use Phoundation\Developer\Project\Interfaces\ProjectInterface;
use Phoundation\Developer\Tests\BomPath;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Commands\Rsync;
use Phoundation\Processes\Enum\ExecuteMethod;
use Phoundation\Processes\Process;
use Phoundation\Servers\Server;
use Phoundation\Translator\Translation;
use Throwable;


/**
 * Class Deploy
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
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
        'server'            => [],      // The target server to which we will deploy
        'hooks'             => [],      // The hooks to execute
        'ignore_changes'    => false,   // If true, git changes will be ignored and deploy will be executed anyway
        'content_check'     => true,    // If true will check content
        'execute_hooks'     => true,    // If true will execute configured deployment hooks
        'sync'              => true,    // If true will sync before (optionally) executing an init before deploying
        'init'              => true,    // If true will execute a system init before deploying
        'notify'            => true,    // If true will send out notifications about this deploy
        'minify'            => true,    // If true will execute CDN file minification
        'push'              => true,    // If true will push changes to the remote git repository
        'parallel'          => true,    // If true will rsync to a parallel copy of the project instead of to the project directly. This can cause the project to be in an unknown state during deployment
        'translate'         => true,    // If true will translate the project before deploying
        'bom_check'         => true,    // If true the system will execute a BOM check on the files
        'update_file_modes' => true,    // If true will fix file modes after rsync
        'backup'            => true,    // If true the system will make a backup on the target environments
        'stash'             => true,    // If true, git will stash changes (if any) and pop those afterwards
        'update_sitemap'    => true,    // If true will update the sitemap before deploying
        'force'             => false,   // If true will ignore halting issue and force deploy. DANGEROUS
        'test_syntax'       => true,    // If true will perform a syntax check before deploying
        'test_unit'         => true,    // If true will execute a unit test before deploying
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
     * Deploy class constructor
     *
     * @param ProjectInterface $project
     * @param array|null $target_environments
     */
    public function __construct(ProjectInterface $project, array|null $target_environments)
    {
        $this->project       = $project;
        $this->configuration = $this->getConfig();
        $this->targets       = $target_environments ?? Arrays::force($this->configuration['targets']);
    }


    /**
     * Start the deployment process
     *
     * @return $this
     */
    public function execute(): static
    {
        if (!$this->targets) {
            throw new OutOfBoundsException(tr('No deployment target environments configured or specified on the commandline'));
        }

        foreach ($this->targets as $environment) {
            // Read environment config, update global config and then check which sections should be executed
            $env_config = $this->getEnvironmentConfig($environment);

            $this->configuration['execute_hooks'] =  $env_config['execute_hooks'];
            $this->configuration['force']         = ($env_config['force'] or FORCE);

            if (!$env_config['ignore_changes']) {
                if ($this->project->getGit()->hasChanges()) {
                    throw new DeployException(tr('The project has pending git changes. Please commit or stash first'));
                }
            }

            static::executeHook('start,pre-content-check');

            if (!$env_config['content_check']) {
                Log::action(tr('Executing content check'));

            }

            static::executeHook('post-content-check,pre-bom-check');

            if ($env_config['bom_check']) {
                Log::action(tr('Executing BOM check'));
//                BomPath::new(PATH_ROOT, PATH_ROOT)->clearBom();
            }

            static::executeHook('post-bom-check,pre-test-syntax');

            if ($env_config['test_syntax']) {
                Log::action(tr('Executing syntax check'));
            }

            static::executeHook('post-test-syntax,pre-test-unit');

            if ($env_config['test_unit']) {
                Log::action(tr('Executing unit tests'));

            }

            static::executeHook('post-test-unit,pre-sync');

            if ($env_config['sync']) {
                Log::action(tr('Executing system synchronisation'));

            }

            static::executeHook('post-sync,pre-init');

            if ($env_config['init']) {
                // Initialize the system
                Log::action(tr('Executing system initialization'));
                Libraries::initialize(true, true, true, 'Executed by the Phoundation deployment system');
            }

            static::executeHook('post-init,pre-translate');

            if ($env_config['translate']) {
                Log::action(tr('Executing translation'));

            }

            static::executeHook('post-translate,pre-minify');

            if ($env_config['minify']) {
                Log::action(tr('Executing CDN data minification'));

            }

            static::executeHook('post-minify,pre-update-sitemap');

            if ($env_config['update_sitemap']) {
                Log::action(tr('Executing sitemap update'));

            }

            static::executeHook('post-update-sitemap,pre-push');

            if ($env_config['push']) {
                Log::action(tr('Pushing updates to remote GIT'));

            }

            static::executeHook('post-push,pre-connect,pre-backup');

            if ($env_config['backup']) {
                Log::action(tr('Executing remote backup'));

            }

            static::executeHook('post-backup,pre-parallel');

            if ($env_config['parallel']) {
                Log::action(tr('Executing remote project copy to prepare for parallel rsync'));
            }

            static::executeHook('post-parallel,pre-rsync');

            // Build the rsync target
            if (empty($env_config['server']['host'])) {
                throw new OutOfBoundsException(tr('No host configured for target ":target"', [
                    ':target' => $environment
                ]));
            }

            $rsync_target  = Strings::endsWith($env_config['server']['host'], ':');
            $rsync_target .= $env_config['server']['path'];

            if ($env_config['server']['user']) {
                $rsync_target = $env_config['server']['user'] . '@' . $rsync_target;
            }

// TODO Add support for languages!
//            foreach (Translations::getLanguages() as $language)

            // Add the project directory to the rsync_target
            $project = Strings::fromReverse(Strings::endsNotWith(PATH_ROOT, '/'), '/');

            // Execute rsync
            Log::action(tr('Executing rsync to target ":target"', [
                ':target' => Strings::endsWith($rsync_target, '/') . $project
            ]));

            // First ensure the target base directory exists!
            Process::new('mkdir')
                ->setServer(Server::new()
                    ->setHostname($env_config['server']['host'])
                    ->setPort($env_config['server']['port'])
                    ->setSshAccount())
                ->addArguments(['-p', $env_config['server']['path']])
                ->execute(ExecuteMethod::noReturn);

            // And then rsync!
            Rsync::new()
                ->setArchive(true)
                ->setVerbose(true)
                ->setCompress(true)
                ->setRemoteSudo($env_config['server']['sudo'])
                ->setPort($env_config['server']['port'])
                ->setSource(PATH_ROOT)
                ->setTarget(Strings::endsWith($rsync_target, '/') . $project)
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

            if ($env_config['update_file_modes']) {
                Log::action(tr('Executing file mode update'));
            }

            static::executeHook('post-update-file-modes,pre-notify');

            if ($env_config['notify']) {
                Log::action(tr('Sending out notifications'));

            }

            static::executeHook('post-notify,finish');
        }

        return $this;
    }


    /**
     * Try to execute the specified hook
     *
     * @param array|string $hooks
     * @return $this
     */
    protected function executeHook(array|string $hooks): static
    {
        if ($this->configuration['execute_hooks']) {
            Hook::new('deploy')->execute($hooks);
        }

        return $this;
    }


    /**
     * Return all deployment configuration
     *
     * @return array
     * @throws Throwable
     */
    protected function getConfig(): array
    {
        try {
            Config::setEnvironment('deploy/deploy', false);
            $configuration = Config::get('');
            Config::setEnvironment(ENVIRONMENT);
            Arrays::ensure($configuration, 'targets');

            return $configuration;

        } catch (Throwable $e) {
            // Whatever went wrong, make sure that the configuration environment is set back to normal
            Config::setEnvironment(ENVIRONMENT);
            throw $e;
        }
    }


    /**
     * Sets the requested modifier configuration
     *
     * @param string $modifier
     * @param bool|null $do
     * @param bool|null $dont
     * @return $this
     */
    protected function setModifier(string $modifier, ?bool $do, ?bool $dont): static
    {
        if ($do) {
            if ($dont) {
                throw new OutOfBoundsException(tr('Both "do" and "dont" modifiers were specified for ":modifier". Please specify only one', [
                    ':modifier' => $modifier
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
     * Loads and returns the configuration for the specified environment
     *
     * @param $environment
     * @return array
     */
    protected function getEnvironmentConfig($environment): array
    {
        $return = [];

        try {
            if (!Config::environmentExists('deploy/' . $environment)) {
                throw new DeployException(tr('The specified environment ":environment" has no configuration file available in PATH_ROOT/config/deploy/', [
                    ':environment' => $environment
                ]));
            }

            Config::setEnvironment('deploy/' . $environment, false);
            $config = Config::get('');
            Config::setEnvironment(ENVIRONMENT);

            foreach ($this->keys as $key => $default) {
                $key = str_replace('-', '_', $key);

                switch ($key) {
                    case 'server':
                        Arrays::default($return, $key, isset_get_typed('array', $config[$key], $default));
                        Arrays::ensure($return[$key], 'host,path,port,user,sudo');
                        break;

                    case 'hooks':
                        Arrays::default($return, $key, isset_get_typed('array', $config[$key], $default));
                        break;

                    default:
                        Arrays::default($return, $key, isset_get_typed('bool', $config[$key], $default));
                }

                if (array_key_exists($key, $this->modifiers)) {
                    if ($this->modifiers[$key] !== null) {
                        // Override was specified on the command line
                        $return[$key] = $this->modifiers[$key];
                    }
                }
            }

            return $return;

        } catch (Throwable $e) {
            // Whatever went wrong, make sure that the configuration environment is set back to normal
            Config::setEnvironment(ENVIRONMENT);
            throw $e;
        }
    }


    /**
     * Sets if hooks should be executed or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setNotify(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('notifications', $do, $dont);
    }


    /**
     * Sets if compression should be used or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     * @return $this
     */
    public function setCompress(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('compress', $do, $dont);
    }


    /**
     * Sets if git should push all changes to another repository or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setTestSyntax(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('text_syntax', $do, $dont);
    }


    /**
     * Sets if unit tests will be executed before deployment or not
     *
     * @param bool|null $do
     * @param bool|null $dont
     * @return $this
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
     * @return $this
     */
    public function setIgnoreChanges(?bool $do, ?bool $dont): static
    {
        return $this->setModifier('ignore_changes', $do, $dont);
    }
}
