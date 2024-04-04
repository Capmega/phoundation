<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Exception\EmailNotExistsException;
use Phoundation\Accounts\Users\Interfaces\EmailInterface;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryAccountType;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryEmail;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUser;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryVerificationCode;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryVerifiedOn;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumElementInputType;


/**
 * Class Email
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */
class Email extends DataEntry implements EmailInterface
{
    use TraitDataEntryUser;
    use TraitDataEntryEmail;
    use TraitDataEntryVerifiedOn;
    use TraitDataEntryAccountType;
    use TraitDataEntryDescription;
    use TraitDataEntryVerificationCode;


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
    public static function getUniqueColumn(): ?string
    {
        return 'email';
    }


    /**
     * Sets the users_id for this object
     *
     * @param int|null $users_id
     *
     * @return static
     */
    public function setUsersId(?int $users_id): static
    {
        $current = $this->getUsersId();

        if ($current and ($current !== $users_id)) {
            throw new ValidationFailedException(tr('Cannot assign additional email to ":to" from ":from" , only unassigned emails can be assigned', [
                ':from' => $current,
                ':to'   => $users_id,
            ]));
        }

        return $this->setValue('users_id', $users_id);
    }


    /**
     * Sets the users_email for this additional email
     *
     * @param string|null $users_email
     *
     * @return static
     */
    public function setUsersEmail(?string $users_email): static
    {
        $current = $this->getUsersEmail();

        if ($current and ($current !== $users_email)) {
            throw new ValidationFailedException(tr('Cannot assign additional email to ":to" from ":from" , only unassigned emails can be assigned', [
                ':from' => $current,
                ':to'   => $users_email,
            ]));
        }

        return $this->setValue('users_email', $users_email);
    }

    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->add(Definition::new($this, 'verification_code')
                            ->setOptional(true)
                            ->setRender(false)
                            ->setReadonly(true))
            ->add(DefinitionFactory::getUsersId($this)
                                   ->setRender(false))
            ->add(DefinitionFactory::getEmail($this)
                                   ->setSize(4)
                                   ->setOptional(false)
                                   ->setHelpText(tr('The extra email address for the user'))
                                   ->addValidationFunction(function (ValidatorInterface $validator) {
                                       // Email cannot exist in accounts_users or accounts_emails!
                                       $validator->isUnique(tr('value ":email" already exists as an additional email address', [':email' => $validator->getSelectedValue()]));

                                       $exists = sql()->get('SELECT `id` FROM `accounts_users` WHERE `email` = :email', [
                                           ':email' => $validator->getSelectedValue(),
                                       ]);

                                       if ($exists) {
                                           $validator->addFailure(tr('value ":email" already exists as a primary email address', [':email' => $validator->getSelectedValue()]));
                                       }
                                   }))
            ->add(Definition::new($this, 'account_type')
                            ->setOptional(true)
                            ->setElement(EnumElement::select)
                            ->setSize(3)
                            ->setCliColumn('-t,--type')
                            ->setDataSource([
                                                'personal' => tr('Personal'),
                                                'business' => tr('Business'),
                                                'other'    => tr('Other'),
                                            ])
                            ->setCliAutoComplete([
                                                     'word' => function (string $word) {
                                                         return Arrays::removeValues([
                                                                                         tr('Business'),
                                                                                         tr('Personal'),
                                                                                         tr('Other'),
                                                                                     ], $word);
                                                     },
                                                     'noword' => function () {
                                                         return [
                                                             tr('Business'),
                                                             tr('Personal'),
                                                             tr('Other'),
                                                         ];
                                                     },
                                                 ])
                            ->setLabel(tr('Type'))
                            ->setHelpText(tr('The type of email address')))
            ->add(DefinitionFactory::getDateTime($this, 'verified_on')
                                   ->setReadonly(true)
                                   ->setSize(3)
                                   ->setNullInputType(EnumElementInputType::text)
                                   ->setNullDb(true, tr('Not verified'))
                                   ->addClasses('text-center')
                                   ->setLabel(tr('Verified on'))
                                   ->setHelpGroup(tr('Account information'))
                                   ->setHelpText(tr('The date when this user was email verified. Empty if not yet verified')))
            ->add(Definition::new($this, 'delete')
                            ->setVirtual(true)
                            ->setInputType(EnumElementInputType::submit)
                            ->setSize(2)
                            ->setLabel(tr('Delete'))
                            ->addClasses('btn btn-outline-warning')
                            ->setValue(tr('Delete')))
            ->add(DefinitionFactory::getDescription($this)
                                   ->setHelpText(tr('The description for this email')));
    }

    /**
     * Returns a DataEntry object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null                        $column
     * @param bool                               $meta_enabled
     * @param bool                               $force
     *
     * @return Email
     */
    public static function get(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false, bool $force = false): static
    {
        try {
            return parent::get($identifier, $column, $meta_enabled, $force);

        } catch (DataEntryNotExistsExceptionInterface|DataEntryDeletedException $e) {
            throw new EmailNotExistsException($e);
        }
    }
}
