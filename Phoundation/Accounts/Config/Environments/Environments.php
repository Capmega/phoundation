<?php

/**
 * Class Environments
 *
 * This class represents a collection of Environment objects and can manage, create, update, update and delete
 * environments
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Config\Environments;

use Phoundation\Filesystem\PhoFilesCore;


class Environments extends PhoFilesCore
{
    /**
     * Environments class constructor
     */
    public function __construct()
    {
        $this->setSource(DIRECTORY_ROOT . '/config/environments/');
        parent::__construct();
    }


    /**
     *
     *
     * @return EnvironmentInterface
     */
    public function getEnvironment(): EnvironmentInterface
    {

    }
}