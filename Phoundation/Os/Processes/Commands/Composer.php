<?php

/**
 * Class Composer
 *
 * This class is a wrapper around the PHP "composer" command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Os\Processes\Commands\Exception\ComposerException;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Stringable;

class Composer extends Command
{
    /**
     * Composer class constructor
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param Stringable|string|null                  $operating_system
     * @param string|null                             $packages
     */
    public function __construct(RestrictionsInterface|array|string|null $restrictions = null, Stringable|string|null $operating_system = null, ?string $packages = null)
    {
        parent::__construct($restrictions, $operating_system, $packages);
        $this->setCommand(DIRECTORY_ROOT . 'data/bin/composer.phar', false);
    }


    /**
     * Execute composer update
     *
     * @param EnumExecuteMethodInterface $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function update(EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'update');
    }


    /**
     * Execute composer require
     *
     * @param EnumExecuteMethodInterface $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function require(EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'require');
    }


    /**
     * Execute composer remove
     *
     * @param EnumExecuteMethodInterface $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function remove(EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'remove');
    }


    /**
     * Execute composer why
     *
     * @param EnumExecuteMethodInterface $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function why(EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'why');
    }


    /**
     * Executed the requested composer command
     *
     * @param EnumExecuteMethodInterface $method
     * @param string                     $command
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    protected function executeComposer(EnumExecuteMethodInterface $method, string $command): IteratorInterface|array|string|int|bool|null
    {
        try {
            array_unshift($this->arguments, [
                'escape_argument' => true,
                'escape_quotes'   => true,
                'argument'        => $command,
            ]);

            return $this->execute($method);

        } catch (ProcessFailedException $e) {
            if (NOWARNINGS) {
                Log::warning(tr('Warning: The "-W" NOWARNING option was specified, did you mean to use "--W" instead, to pass "-W" on to composer?'));
            }

            throw new ComposerException(tr('PHP composer failed with exit code ":exitcode", see output', [
                ':exitcode' => $e->getDataKey('exit_code')
            ]), $e);
        }
    }
}
