<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;


use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Stringable;

/**
 * Class Composer
 *
 * This class manages the PHP "composer" command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
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
     * Returns an array containing the found files
     *
     * @param EnumExecuteMethodInterface $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function update(EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): IteratorInterface|array|string|int|bool|null
    {
        array_unshift($this->arguments, [
            'escape_argument' => true,
            'escape_quotes'   => true,
            'argument'        => 'update',
        ]);

        return $this->execute($method);
    }
}
