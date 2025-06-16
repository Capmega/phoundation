<?php

/**
 * Class Email
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Exception\EmailNotExistsException;
use Phoundation\Accounts\Users\Interfaces\EmailInterface;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryAccountType;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryEmail;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUser;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryVerificationCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryVerifiedOn;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;


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
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'accounts_emails';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
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
        if (!$this->is_loading) {
            if ($users_id) {
                $current = $this->getUsersId();

                if ($current and ($current !== $users_id)) {
                    throw new ValidationFailedException(tr('Cannot assign additional email to ":to" from ":from", only unassigned emails can be assigned', [
                        ':from' => $current,
                        ':to' => $users_id,
                    ]));
                }
            }
        }

        return $this->set($users_id, 'users_id');
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
        if ($users_email) {
            $current = $this->getUsersEmail();

            if ($current and ($current !== $users_email)) {
                throw new ValidationFailedException(tr('Cannot assign additional email to ":to" from ":from", only unassigned emails can be assigned', [
                    ':from' => $current,
                    ':to'   => $users_email,
                ]));
            }
        }

        return $this->set($users_email, 'users_email');
    }


    /**
     * Returns a DataEntry object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_load_null_identifier
     * @param EnumLoadParameters|null                   $on_load_not_exists
     *
     * @return static|null
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_load_null_identifier = null, ?EnumLoadParameters $on_load_not_exists = null): ?static
    {
        try {
            return parent::load($identifier, $on_load_null_identifier, $on_load_not_exists);

        } catch (DataEntryNotExistsException|DataEntryDeletedException $e) {
            throw new EmailNotExistsException($e);
        }
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(DefinitionFactory::newCode('verification_code')
                                             ->setOptional(true)
                                             ->setRender(false)
                                             ->setReadonly(true))

                      ->add(DefinitionFactory::newUsersId()
                                           ->setRender(false))

                      ->add(DefinitionFactory::newEmail()
                                           ->setSize(4)
                                           ->setOptional(true)
                                           ->setHelpText(tr('The extra email address for the user'))
                                           ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                               // Email cannot exist in accounts_users or accounts_emails!
                                               $o_validator->isUnique(tr('already exists as an additional email address'));

                                               $exists = sql()->getRow('SELECT `id` FROM `accounts_users` WHERE `email` = :email', [
                                                   ':email' => $o_validator->getSelectedValue(),
                                               ]);

                                               if ($exists) {
                                                   $o_validator->addSoftFailure(tr('value ":email" already exists as a primary email address', [':email' => $o_validator->getSelectedValue()]));
                                               }
                                           }))

                    ->add(Definition::new('account_type')
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
                                        'word'   => function (string $word) {
                                            return Arrays::removeMatchingValues([
                                                tr('Business'),
                                                tr('Personal'),
                                                tr('Other'),
                                            ], $word);
                                        },
                                        'noword' => function ($word) {
                                            return [
                                                tr('Business'),
                                                tr('Personal'),
                                                tr('Other'),
                                            ];
                                        },
                                    ])
                                    ->setLabel(tr('Type'))
                                    ->setHelpText(tr('The type of email address')))

                    ->add(DefinitionFactory::newDateTime('verified_on')
                                           ->setReadonly(true)
                                           ->setSize(3)
                                           ->setDbNullInputType(EnumInputType::text)
                                           ->setNullDisplay(tr('Not verified'))
                                           ->addClasses('text-center')
                                           ->setLabel(tr('Verified on'))
                                           ->setHelpGroup(tr('Account information'))
                                           ->setHelpText(tr('The date when this user was email verified. Empty if not yet verified')))

                    ->add(DefinitionFactory::newButton('delete')
                                           ->setInputType(EnumInputType::submit)
                                           ->setSize(2)
                                           ->setLabel(tr('Delete'))
                                           ->addClasses('btn btn-outline-warning')
                                           ->setContent(tr('Delete')))

                    ->add(DefinitionFactory::newDescription()
                                           ->setHelpText(tr('The description for this email')));

        return $this;
    }
}
