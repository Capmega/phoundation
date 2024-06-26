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
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Os\Processes\Commands\Exception\ComposerException;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Stringable;

class Composer extends Command
{
    /**
     * Composer class constructor
     *
     * @param FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory
     * @param Stringable|string|null                            $operating_system
     * @param string|null                                       $packages
     */
    public function __construct(FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory = null, Stringable|string|null $operating_system = null, ?string $packages = null)
    {
        parent::__construct($execution_directory, $operating_system, $packages);
        $this->setCommand(DIRECTORY_ROOT . 'data/bin/composer.phar', false);
    }


    /**
     * ExecuteExecuteInterface composer update
     *
     * @param EnumExecuteMethod $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function update(EnumExecuteMethod $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'update');
    }


    /**
     * ExecuteExecuteInterface composer require
     *
     * @param EnumExecuteMethod $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function require(EnumExecuteMethod $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'require');
    }


    /**
     * ExecuteExecuteInterface composer remove
     *
     * @param EnumExecuteMethod $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function remove(EnumExecuteMethod $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'remove');
    }


    /**
     * ExecuteExecuteInterface composer why
     *
     * @param EnumExecuteMethod $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function why(EnumExecuteMethod $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'why');
    }


    /**
     * ExecuteExecuteInterface composer search
     *
     * @param EnumExecuteMethod $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function search(EnumExecuteMethod $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'search');
    }


    /**
     * ExecuteExecuteInterface composer fund
     *
     * @param EnumExecuteMethod $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function fund(EnumExecuteMethod $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        return $this->executeComposer($method, 'fund');
    }


    /**
     * Executed the requested composer command
     *
     * @param EnumExecuteMethod $method
     * @param string                     $command
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    protected function executeComposer(EnumExecuteMethod $method, string $command): IteratorInterface|array|string|int|bool|null
    {
        $this->addArguments('--ansi');

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
