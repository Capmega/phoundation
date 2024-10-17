<?php

/**
 * Class Bfg
 *
 * This class is a wrapper around the git related "bfg" command
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
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Os\Processes\Commands\Exception\ComposerException;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Stringable;

class Bfg extends Command
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
throw new UnderConstructionException();
        parent::__construct($execution_directory, $operating_system, $packages);

        $this->setCommand('java', false)
             ->addArguments(['--jar', DIRECTORY_ROOT . 'data/bin/bfg']);
    }


   /**
     * Execute composer fund
     *
     * @param EnumExecuteMethod $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function stripBlobsBiggerThan(EnumExecuteMethod $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
throw new UnderConstructionException();
        return $this->executeComposer($method, '--strip-blobs-bigger-than');
    }


    /**
     * Executed the requested composer command
     *
     * @param EnumExecuteMethod $method
     * @param string            $argument
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    protected function executeComposer(EnumExecuteMethod $method, string $argument): IteratorInterface|array|string|int|bool|null
    {
throw new UnderConstructionException();
        $this->addArguments($argument);

        try {
            array_unshift($this->arguments, [
                'escape_argument' => true,
                'escape_quotes'   => true,
                'argument'        => $argument,
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
