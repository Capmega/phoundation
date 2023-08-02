<?php

namespace Phoundation\Developer\Project;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
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
class Deploy
{
    /**
     * The project we're deploying
     *
     * @var ProjectInterface $project
     */
    protected ProjectInterface $project;

    /**
     *
     *
     * @var array $configuration
     */
    protected array $configuration;


    /**
     * Deploy class constructor
     */
    public function __construct(ProjectInterface $project)
    {
        $this->project = $project;
    }


    protected function getConfig(string $project): array
    {

    }


    protected function loadConfig($project): array
    {
        Arrays::default($this->configuration, '', Config::get());

        /**
         * The target environments which will be deployed
         *
         * @var array $targets
         */

        /**
         * If true, git changes will be ignored and deploy will be executed anyway
         *
         * @var bool $ignore_changes
         */

        /**
         * Will not check content
         *
         * @var bool $do_content_check
         */

        /**
         * Will not execute configured deployment hooks
         *
         * @var bool $no_hooks
         */

        /**
         * Will not execute a system init before deploying
         *
         * @var bool $no_init
         */

        /**
         * If true will not send out notifications about this deploy
         *
         * @var bool $no_notify
         */

        /**
         * If true will not execute CDN file minification
         *
         * @var bool $no_minify
         */

        /**
         * If true will not push changes to the remote git repository
         *
         * @var bool $no_push
         */

        /**
         * If true will not rsync to a parallel version of the project but to the project directly. This can cause the
         * project to be in an unknown state during deployment
         *
         * @var bool $no_parallel
         */

        /**
         * If true the system will not rebuild the sitemap after deployment
         *
         * @var bool $no_sitemap
         */

        /**
         * If true will not translate the project before deploying
         *
         * @var bool $no_translate
         */

        /**
         * If true the system will not execute a BOM check on the files
         *
         * @var bool $no_bom_check
         */

        /**
         * If true the system will not make a backup on the target environments
         *
         * @var bool $no_backup
         */

        /**
         *
         *
         * @var bool $do_backup
         */

        /**
         *
         *
         * @var bool $stash
         */

        /**
         *
         *
         * @var bool $update_sitemap
         */

        /**
         * If true will ignore halting issue and force deploy. DANGEROUS
         *
         * @var bool $force
         */

        /**
         * If true will perform a syntax check before deploying
         *
         * @var bool $test_syntax
         */

        /**
         * If true will execute a unit test before deploying
         *
         * @var bool $test_unit
         */

        /**
         * If true will first sync and execute an init before deployg
         *
         * @var bool $test_sync_init
         */
    }
}