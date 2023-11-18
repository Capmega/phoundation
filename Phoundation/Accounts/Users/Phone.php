<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Exception\Interfaces\PhoneNotExistsExceptionInterface;
use Phoundation\Accounts\Users\Exception\PhoneNotExistsException;
use Phoundation\Accounts\Users\Interfaces\PhoneInterface;
use Phoundation\Core\Arrays;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryAccountType;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryPhone;
use Phoundation\Data\DataEntry\Traits\DataEntryUser;
use Phoundation\Data\DataEntry\Traits\DataEntryVerificationCode;
use Phoundation\Data\DataEntry\Traits\DataEntryVerifiedOn;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\InputElement;
use Phoundation\Web\Html\Enums\InputType;


/**
 * Class Phone
 *
 *
 *
 * @see DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Phone extends DataEntry implements PhoneInterface
{
    use DataEntryUser;
    use DataEntryPhone;
    use DataEntryVerifiedOn;
    use DataEntryAccountType;
    use DataEntryDescription;
    use DataEntryVerificationCode;


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_phones';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Phone');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'phone';
    }


    /**
     * Returns a DataEntry object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool $meta_enabled
     * @return static|null
     */
    public static function get(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, bool $meta_enabled = true): ?static
    {
        try {
            return parent::get($identifier, $column, $meta_enabled);

        } catch (DataEntryNotExistsExceptionInterface $e) {
            throw new PhoneNotExistsException($e);
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
            ->addDefinition(DefinitionFactory::getPhone($this)
                ->setSize(4)
                ->setOptional(false)
                ->setHelpText(tr('The extra phone for the user'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Phone cannot already exist in accounts_users or accounts_emails for THIS user!
                    $exists = sql()->get('SELECT `id` FROM `accounts_users` WHERE `id` != :id AND `phone` = :phone', [
                        ':phone' => $validator->getSelectedValue(),
                        ':id'    => (int) $validator->getSourceValue('users_id')
                    ]);

                    if ($exists) {
                        $validator->addFailure($failure ?? tr('with value ":value" already exists', [':value' => $validator->getSelectedValue()]));
                    }

                    $exists = sql()->get('SELECT `id` FROM `accounts_phones` WHERE `users_id` = :users_id AND `phone` = :phone AND `id` != :id', [
                        ':users_id' => (int) $validator->getSourceValue('users_id'),
                        ':phone'    => $validator->getSelectedValue(),
                        ':id'       => (int) $validator->getSourceValue('id')
                    ]);

                    if ($exists) {
                        $validator->addFailure($failure ?? tr('with value ":value" already exists', [':value' => $validator->getSelectedValue()]));
                    }
                }))
            ->addDefinition(Definition::new($this, 'account_type')
                ->setOptional(true)
                ->setElement(InputElement::select)
                ->setSize(3)
                ->setCliField('-t,--type')
                ->setSource([
                    'personal' => tr('Personal'),
                    'business' => tr('Business'),
                    'other'    => tr('Other'),
                ])
                ->setCliAutoComplete([
                    'word'   => function (string $word) { return Arrays::filterValues([tr('Business'), tr('Personal'), tr('Other')], $word); },
                    'noword' => function ()             { return [tr('Business'), tr('Personal'), tr('Other')]; },
                ])
                ->setLabel(tr('Type'))
                ->setHelpText(tr('The type of phone')))
            ->addDefinition(DefinitionFactory::getDateTime($this, 'verified_on')
                ->setReadonly(true)
                ->setSize(3)
                ->setNullInputType(InputType::text)
                ->setNullDb(true, tr('Not verified'))
                ->addClasses('text-center')
                ->setLabel(tr('Verified on'))
                ->setHelpGroup(tr('Account information'))
                ->setHelpText(tr('The date when this user was phone verified. Empty if not yet verified')))
            ->addDefinition(Definition::new($this, 'delete')
                ->setVirtual(true)
                ->setInputType(InputType::submit)
                ->setSize(2)
                ->setLabel(tr('Delete'))
                ->addClasses('btn btn-outline-warning')
                ->setValue(tr('Delete')))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this phone')));
    }
}
