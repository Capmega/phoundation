<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Interfaces\RoleInterface;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionDefaults;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Web\Http\Html\Components\Form;


/**
 * Class Role
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Role extends DataEntry implements RoleInterface
{
    use DataEntryNameDescription;


    /**
     * Role class constructor
     *
     * @param DataEntry|string|int|null $identifier
     */
    public function __construct(DataEntry|string|int|null $identifier = null)
    {
        static::$entry_name   = 'role';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_roles';
    }


    /**
     * Add the specified rights to this role
     *
     * @return Rights
     */
    public function rights(): Rights
    {
        if (!$this->list) {
            $this->list = new Rights($this);
        }

        return $this->list;
    }


    /**
     * Returns the users that are linked to this role
     *
     * @return Users
     */
    public function users(): Users
    {
        return new Users($this);
    }


    /**
     * Creates and returns an HTML for the fir
     *
     * @return Form
     */
    public function getRightsHtmlForm(): Form
    {
        $form   = Form::new();
        $rights = $this->rights();
        $select = $rights->getHtmlSelect()->setCache(true);

        foreach ($rights as $right) {
            $select->setSelected($right->getSeoName());
            $form->addContent($select->render() . '<br>');
        }

        // Add extra entry with nothing selected
        $select->clearSelected();
        $form->addContent($select->render());
        return $form;
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
                ->setHelpText(tr('The name for this role')))
            ->add(DefinitionDefaults::getSeoName())
            ->add(DefinitionDefaults::getDescription()
                ->setHelpText(tr('The description for this role')));
    }
}