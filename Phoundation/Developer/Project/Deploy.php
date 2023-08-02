<?php

namespace Phoundation\Developer\Project;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Developer\Project\Interfaces\DeployInterface;
use Phoundation\Developer\Project\Interfaces\ProjectInterface;


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
        'ignore_changes'    => false,   // If true, git changes will be ignored and deploy will be executed anyway
        'content_check'     => true,    // If true will check content
        'hooks'             => true,    // If true will execute configured deployment hooks
        'init'              => true,    // If true will execute a system init before deploying
        'notify'            => true,    // If true will send out notifications about this deploy
        'minify'            => true,    // If true will execute CDN file minification
        'push'              => true,    // If true will push changes to the remote git repository
        'parallel'          => true,    // If true will rsync to a parallel copy of the project instead of to the project directly. This can cause the project to be in an unknown state during deployment
        'sitemap'           => true,    // If true the system will rebuild the sitemap after deployment
        'translate'         => true,    // If true will translate the project before deploying
        'bom_check'         => true,    // If true the system will execute a BOM check on the files
        'update-file-modes' => true,    //
        'backup'            => true,    // If true the system will make a backup on the target environments
        'stash'             => true,    //
        'update_sitemap'    => true,    //
        'force'             => false,   // If true will ignore halting issue and force deploy. DANGEROUS
        'test_syntax'       => true,    // If true will perform a syntax check before deploying
        'test_unit'         => true,    // If true will execute a unit test before deploying
        'test_sync_init'    => true,    // If true will first sync and execute an init before deployg
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
    protected array $modifiers;


    /**
     * Deploy class constructor
     *
     * @param ProjectInterface $project
     * @param array|null $target_environments
     */
    public function __construct(ProjectInterface $project, array|null $target_environments)
    {
        $this->project = $project;

        foreach ($target_environments as $environment) {
            $configuration = $this->loadConfig($environment);

        }
    }


    /**
     * Loads and returns the configuration for the specified environment
     *
     * @param $environment
     * @return array
     */
    protected function loadConfig($environment): array
    {
        Config::setEnvironment('deploy/' . $environment);

        foreach ($this->keys as $key => $default) {
            Arrays::default($this->configuration, $key, Config::getBoolean($key, $default));

            if (array_key_exists($key, $this->modifiers)) {
                // Override was specified on the command line
                $this->configuration[$key] = $this->modifiers[$key];
            }
        }
    }
}