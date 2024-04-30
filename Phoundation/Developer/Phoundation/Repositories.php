<?php

/**
 * Repositories class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Developer\Phoundation;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Phoundation\Interfaces\RepositoriesInterface;
use Phoundation\Developer\Phoundation\Interfaces\RepositoryInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;
use Stringable;

class Repositories extends Iterator implements RepositoriesInterface
{
    /**
     * Adds the specified repository to this repositories list
     *
     * @param mixed                            $repository
     * @param float|Stringable|int|string|null $name
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return $this
     */
    public function add(mixed $repository, float|Stringable|int|string|null $name = null, bool $skip_null = true, bool $exception = true): static
    {
        if (!$repository instanceof RepositoryInterface) {
            throw new OutOfBoundsException(tr('Specified repository ":path" must be a RepositoriesInterface object', [
                ':path' => $repository->getPath()
            ]));
        }

        if (!$repository->exists()) {
            throw new OutOfBoundsException(tr('The path for the specified repository ":path" does not exist', [
                ':path' => $repository->getPath()
            ]));
        }

        if (!$repository->isRepository()) {
            throw new OutOfBoundsException(tr('The path for the specified repository ":path" does not exist', [
                ':path' => $repository->getPath()
            ]));
        }

        return parent::add($repository, $name, $skip_null, $exception);
    }


    /**
     * Scans for available phoundation and or phoundation plugin and or phoundation template repositories
     *
     * @return $this
     */
    public function scan(): static
    {
        // Paths (in order) which will be scanned for Phoundation repositories
        $directories = [
            '~/projects/',
            '~/PhpstormProjects/',
            '~/PhpStormProjects/',
            '~/phpstormprojects/',
            '../',
            '../../',
            '../../../',
            '/var/www/html/',
        ];

        Log::action(tr('Scanning for Phoundation core, plugin, and template repositories'));

        // Scan for phoundation repositories
        foreach ($directories as $directory) {
            $directory = Directory::normalizePath($directory);
            $directory = Directory::new($directory, Restrictions::readonly(dirname($directory), 'Repositories::scan()'), make_absolute: true);

            Log::action(tr('Scanning directory ":directory"', [
                ':directory' => $directory->getPath()
            ]));

            if (!$directory->exists()) {
                // Nothing here
                continue;
            }

            // The main phoundation directory should be called either phoundation or Phoundation.
            foreach ($directory->scan() as $name) {
                $repository = $directory . $name;
                $repository = new Repository($repository, Restrictions::writable(dirname($repository), 'Repositories::scan() > ' . $name));

                if (!$repository->isRepository()) {
                    Log::warning(tr('Ignoring directory ":directory", it does not exist', [
                        ':directory' => $repository->getPath(),
                    ]), 2);
                    continue;
                }

                Log::success(tr('Found Phoundation repository in ":path"', [':path' => $repository->getPath()]), 3);
                $this->add($repository, $repository->getName());
            }
        }

        return $this;
    }
}