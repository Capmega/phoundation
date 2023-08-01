<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Users\Interfaces\UsersInterface;
use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Web\Http\Html\Components\Form;
use Phoundation\Web\Http\Html\Components\Interfaces\FormInterface;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


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
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_roles';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Role');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'seo_name';
    }


    /**
     * Add the specified rights to this role
     *
     * @return RightsInterface
     */
    public function getRights(): RightsInterface
    {
        if (!$this->list) {
            $this->list = Rights::new()->setParent($this)->load();
        }

        return $this->list;
    }


    /**
     * Returns the users that are linked to this role
     *
     * @return UsersInterface
     */
    public function getUsers(): UsersInterface
    {
        return Users::new()->setParent($this)->load();
    }


    /**
     * Creates and returns an HTML for the fir
     *
     * @param string $name
     * @return FormInterface
     */
    public function getRightsHtmlForm(string $name = 'rights_id[]'): FormInterface
    {
        $form   = Form::new();
        $rights = Rights::new();
        $select = $rights->getHtmlSelect()->setCache(true)->setName($name);

        // Add extra entry with nothing selected
        $select->clearSelected();
        $form->addContent($select->render() . '<br>');

        foreach ($this->getRights() as $right) {
            $select->setSelected($right->getId());
            $form->addContent($select->render() . '<br>');
        }

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
            ->addDefinition(DefinitionFactory::getName($this)
                ->setInputType(InputTypeExtended::name)
                ->setSize(12)
                ->setMaxlength(64)
                ->setHelpText(tr('The name for this role'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isUnique(tr('value ":name" already exists', [':name' => $validator->getSourceValue()]));
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this role')));
    }
}