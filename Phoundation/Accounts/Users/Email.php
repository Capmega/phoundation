<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Users\Exception\EmailNotExistsException;
use Phoundation\Accounts\Users\Exception\Interfaces\EmailNotExistsExceptionInterface;
use Phoundation\Accounts\Users\Interfaces\EmailInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Interfaces\UsersInterface;
use Phoundation\Core\Arrays;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryUser;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Http\Html\Components\Form;
use Phoundation\Web\Http\Html\Components\Interfaces\FormInterface;
use Phoundation\Web\Http\Html\Enums\InputElement;
use Phoundation\Web\Http\Html\Enums\InputType;


/**
 * Class Email
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Email extends DataEntry
{
    use DataEntryUser;


    /**
     * @var
     */
    protected $type;

    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_emails';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Email');
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
     * Returns the user to which this email belongs
     *
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        if (empty($this->user)) {
            $this->user = User::new($this)->load();
        }

        return $this->user;
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
     * Returns a DataEntry object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @return static|null
     * @throws EmailNotExistsExceptionInterface
     */
    public static function get(DataEntryInterface|string|int|null $identifier = null, ?string $column = null): ?static
    {
        try {
            return parent::get($identifier, $column);

        } catch (DataEntryNotExistsExceptionInterface $e) {
            throw new EmailNotExistsException($e);
        }
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new($this, 'verification_code')
                ->setOptional(true)
                ->setVisible(false)
                ->setReadonly(true))
            ->addDefinition(DefinitionFactory::getUsersId($this)
                ->setVisible(false))
            ->addDefinition(Definition::new($this, 'type')
                ->setOptional(true)
                ->setElement(InputElement::select)
                ->setSize(3)
                ->setCliField('-t,--type')
                ->setSource([
                    'personal' => tr('Personal'),
                    'business' => tr('Business'),
                ])
                ->setCliAutoComplete([
                    'word'   => function (string $word) { return Arrays::filterValues([tr('Business'), tr('Personal'), tr('Other')], $word); },
                    'noword' => function ()             { return [tr('Business'), tr('Personal'), tr('Other')]; },
                ])
                ->setLabel(tr('Type'))
                ->setHelpText(tr('The type of email address'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->hasMaxCharacters(6);
                }))
            ->addDefinition(DefinitionFactory::getEmail($this)
                ->setHelpText(tr('The extra email address for the user'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isUnique(tr('value ":email" already exists', [':email' => $validator->getSourceValue()]));
                }))
            ->addDefinition(DefinitionFactory::getDateTime($this, 'verified_on')
                ->setReadonly(true)
                ->setNullInputType(InputType::text)
                ->setNullDb(true, tr('Not verified'))
                ->addClasses('text-center')
                ->setLabel(tr('Account verified on'))
                ->setHelpGroup(tr('Account information'))
                ->setHelpText(tr('The date when this user was email verified. Empty if not yet verified')))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this email')));
    }
}
