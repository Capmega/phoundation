<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionDefaults;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;


/**
 * Class Right
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Right extends DataEntry implements RightInterface
{
    use DataEntryNameDescription;


    /**
     * Right class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = false)
    {
        $this->table        = 'accounts_rights';
        $this->entry_name   = 'right';

        parent::__construct($identifier, $init);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->add(DefinitionDefaults::getName()
                ->setHelpText(tr('The name for this right')))
            ->add(DefinitionDefaults::getSeoName())
            ->add(DefinitionDefaults::getDescription()
                ->setHelpText(tr('The description for this right')));
    }
}