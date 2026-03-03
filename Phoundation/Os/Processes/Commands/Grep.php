<?php

/**
 * Class Grep
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\TraitDataObjectDirectory;
use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Data\Traits\TraitDataObjectFile;
use Phoundation\Data\Traits\TraitDataStringValue;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Commands\Exception\FindException;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;


class Grep extends Command
{
    use TraitDataObjectFile {
        setFileObject as protected __setFileObject;
    }
    use TraitDataObjectDirectory {
        setDirectoryObject as protected __setDirectoryObject;
    }
    use TraitDataStringValue;


    /**
     * Tracks if grep should reverse filter or not
     *
     * @var bool $filter_reversed
     */
    protected bool $filter_reversed = false;

    /**
     * Tracks if grep should filter on regular expressions or not
     *
     * @var bool $filter_regular_expression
     */
    protected bool $filter_regular_expression = false;

    /**
     * Tracks the filter on which grep will filter
     *
     * @var string|null $filter
     */
    protected ?string $filter = null;


    /**
     * Tail class constructor
     *
     * @param PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory
     * @param string|null                                         $operating_system
     * @param string|null                                         $packages
     */
    public function __construct(PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory = null, ?string $operating_system = null, ?string $packages = null)
    {
        parent::__construct($execution_directory, $operating_system, $packages);
        $this->setCommand('grep');
    }


    /**
     * Returns if grep will filter reversed
     *
     * @return string
     */
    public function getFilter(): string
    {
        return $this->filter;
    }


    /**
     * Sets if grep will filter reversed
     *
     * @param string $filter The text to filter on
     *
     * @return static
     */
    public function setFilter(string $filter): static
    {
        $this->filter = $filter;
        return $this;
    }


    /**
     * Returns if grep will filter reversed
     *
     * @return bool
     */
    public function getFilterRegularExpression(): bool
    {
        return $this->filter_regular_expression;
    }


    /**
     * Sets if grep will filter reversed
     *
     * @param bool $filter If true, grep will filter reversed
     *
     * @return static
     */
    public function setFilterRegularExpression(bool $filter): static
    {
        $this->filter_regular_expression = $filter;
        return $this;
    }


    /**
     * Returns if grep will filter reversed
     *
     * @return bool
     */
    public function getFilterReversed(): bool
    {
        return $this->filter_reversed;
    }


    /**
     * Sets if grep will filter reversed
     *
     * @param bool $filter If true, grep will filter reversed
     *
     * @return static
     */
    public function setFilterReversed(bool $filter): static
    {
        $this->filter_reversed = $filter;
        return $this;
    }


    /**
     * Sets the file object
     *
     * @note This property is exclusive with directory. Either the file or the directory can be set, but not both
     *
     * @param PhoFileInterface|null $_file The file to grep
     *
     * @return static
     *
     * @throws OutOfBoundsException
     */
    public function setFileObject(?PhoFileInterface $_file): static
    {
        if ($this->_directory) {
            throw new OutOfBoundsException(ts('Cannot set file "" for Grep object, the directory "" is already specified', [
                ':directory' => $this->_directory,
                ':file'      => $this->_file,
            ]));
        }

        return $this->__setFileObject($_file);
    }


    /**
     * Sets the directory
     *
     * @param PhoDirectoryInterface|null $_directory The directory in which to grep all files
     *
     * @return static
     */
    public function setDirectoryObject(?PhoDirectoryInterface $_directory): static
    {
        if ($this->_directory) {
            throw new OutOfBoundsException(ts('Cannot set file "" for Grep object, the directory "" is already specified', [
                ':directory' => $this->_directory,
                ':file'      => $this->_file,
            ]));
        }

        return $this->__setDirectoryObject($_directory);
    }


    /**
     * Returns an array containing the found files
     *
     * @return array
     *
     * @throws OutOfBoundsException
     * @throws ProcessFailedException
     */
    public function executeReturnArray(): array
    {
        if (empty($this->filter)) {
            throw new OutOfBoundsException(ts('Cannot grep, no filter specified'));
        }

        try {
            $this->setCommand('grep') //->setDebug(true)
                 ->setExecutionDirectory($this->_directory ?? $this->_file?->getDirectoryObject())
                 ->appendArguments($this->filter_reversed           ? '-v'                      : null)
                 ->appendArguments($this->filter_regular_expression ? '-e'                      : null)
                 ->appendArguments($this->filter)
                 ->appendArguments($this->_file                     ? $this->_file              : null)
                 ->appendArguments($this->_directory                ? [$this->_directory, '-R'] : null);

            // Directory grep results are in format file:line, reformat and group the data
            $result = parent::executeReturnArray();
            $return = null;

            foreach ($result as $line) {
                $file = Strings::until($line, ':');
                $line = Strings::from($line, ':');

                array_put($return, $line, $file, '');
            }

            return $return;

        } catch (ProcessFailedException $e) {
            PhoPath::new($this->_directory ?? $this->_file?->getDirectoryObject())
                   ->checkReadable('grep', $e);
        }

        // Should never get here
        return [];
    }


    /**
     * Builds and returns the command line that will be executed
     *
     * @param bool $background
     * @param bool $pipe
     *
     * @return string
     */
    public function getFullCommandLine(bool $background = false, bool $pipe = false): string
    {
        if (empty($this->filter)) {
            throw new OutOfBoundsException(ts('Cannot grep, no filter specified'));
        }

        // TODO This is checking the pipe out, should be checking the in pipe
        if (empty($this->_file) and empty($this->_directory) and empty($this->pipe)) {
            throw new OutOfBoundsException(ts('Cannot grep, no file or directory specified to grep in'));
        }

        $this->setCommand('grep') //->setDebug(true)
             ->setExecutionDirectory($this->_directory ?? $this->_file?->getDirectoryObject())
             ->appendArguments($this->filter_reversed           ? '-v'                      : null)
             ->appendArguments($this->filter_regular_expression ? '-e'                      : null)
             ->appendArguments($this->filter)
             ->appendArguments($this->_file                     ?: null)
             ->appendArguments($this->_directory                ? [$this->_directory, '-R'] : null);

        return parent::getFullCommandLine($background, $pipe);
    }


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethod $method
     *
     * @return array
     * @deprecated
     */
    public function grep(EnumExecuteMethod $method): array
    {
        if (!$this->_directory and !$this->_file) {
            throw new CommandsException(tr('Cannot execute grep, no file or path specified'));
        }

        if (!$this->value) {
            throw new CommandsException(tr('Cannot execute grep, no filter value specified'));
        }

        // Return results
        return $this->clearArguments()
                    ->setAcceptedExitCodes([0, 1])
                    ->appendArgument($this->value)
                    ->appendArgument($this->_directory ?? $this->_file)
                    ->appendArgument($this->_directory ? '-R' : null)
                    ->execute($method);
    }
}
