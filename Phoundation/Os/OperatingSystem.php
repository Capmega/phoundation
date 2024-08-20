<?php

/**
 * Class OperatingSystem
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryVersion;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Os\Interfaces\OperatingSystemInterface;


class OperatingSystem extends DataEntry implements OperatingSystemInterface
{
    use TraitDataEntryNameDescription;
    use TraitDataEntryVersion;

    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'os_operating_systems';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('Operating System');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Returns either the specified operating system, or the current one
     *
     * @param string|null $operating_system
     *
     * @return static
     */
    public static function getSpecifiedOrCurrent(?string $operating_system): static
    {
        if ($operating_system === null) {
            return static::detect();
        }

        return static::load($operating_system);
    }


    /**
     * Detects the current operating system and returns an operating system object for it
     *
     * @return static
     */
    public static function detect(): static
    {
        throw new UnderConstructionException();
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::getName($this))
                    ->add(DefinitionFactory::getDescription($this));
    }
}
