<?php

/**
 * Class DataEntryReadonlyException
 *
 * This exception is thrown when a data entry is trying to save its data while being in readonly state
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Exception;

use Phoundation\Data\DataEntries\Exception\Interfaces\DataEntryReadonlyExceptionInterface;
use Phoundation\Utils\Arrays;


class DataEntryReadonlyException extends DataEntryException implements DataEntryReadonlyExceptionInterface
{
    /**
     * @var array|string[] $allow
     */
    protected array $allow = ['get'];


    /**
     * Returns the list of actions that are allowed
     *
     * @return array
     */
    public function getAllow(): array
    {
        return $this->allow;
    }


    /**
     * Add a single action or a list of actions that are allowed
     *
     * @param string|array $allow
     *
     * @return static
     */
    public function setAllow(string|array $allow): static
    {
        $this->allow = Arrays::force($allow);
    }
}
