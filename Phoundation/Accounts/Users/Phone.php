<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Exception\PhoneNotExistsException;
use Phoundation\Accounts\Users\Interfaces\PhoneInterface;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryAccountType;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryPhone;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUser;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryVerificationCode;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryVerifiedOn;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Sanitize;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;
use Stringable;

/**
 * Class Phone
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */
class Phone extends DataEntry implements PhoneInterface
{
    use TraitDataEntryUser;
    use TraitDataEntryPhone;
    use TraitDataEntryVerifiedOn;
    use TraitDataEntryAccountType;
    use TraitDataEntryDescription;
    use TraitDataEntryVerificationCode;

    /**
     * DataEntry class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null                        $column
     * @param bool|null                          $meta_enabled
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null)
    {
        $identifier = Sanitize::new($identifier)
                              ->phoneNumber()
                              ->getSource();
        parent::__construct($identifier, $column, $meta_enabled);
    }


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
    public static function getUniqueColumn(): ?string
    {
        return 'phone';
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
     * @return Phone
     */
    public static function load(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false, bool $force = false): static
    {
        try {
            return parent::load($identifier, $column, $meta_enabled, $force);

        } catch (DataEntryNotExistsExceptionInterface|DataEntryDeletedException $e) {
            throw new PhoneNotExistsException($e);
        }
    }


    /**
     * Returns true if an entry with the specified identifier exists
     *
     * @param Stringable|string|int $identifier      The unique identifier, but typically not the database id, usually
     *                                               the seo_email, or seo_name
     * @param string|null           $column
     * @param int|null              $not_id
     * @param bool                  $throw_exception If the entry does not exist, instead of returning false will throw
     *                                               a DataEntryNotExistsException
     *
     * @return bool
     */
    public static function exists(Stringable|string|int $identifier, ?string $column = null, ?int $not_id = null, bool $throw_exception = false): bool
    {
        $identifier = Sanitize::new($identifier)
                              ->phoneNumber()
                              ->getSource();

        return parent::notExists($identifier, $column, $not_id, $throw_exception);
    }


    /**
     * Returns true if an entry with the specified identifier does not exist
     *
     * @param Stringable|string|int $identifier      The unique identifier, but typically not the database id, usually
     *                                               the seo_email, or seo_name
     * @param string|null           $column
     * @param int|null              $id              If specified, will ignore the found entry if it has this ID as it
     *                                               will be THIS object
     * @param bool                  $throw_exception If the entry exists (and does not match id, if specified), instead
     *                                               of returning false will throw a DataEntryNotExistsException
     *
     * @return bool
     */
    public static function notExists(Stringable|string|int $identifier, ?string $column = null, ?int $id = null, bool $throw_exception = false): bool
    {
        $identifier = Sanitize::new($identifier)
                              ->phoneNumber()
                              ->getSource();

        return parent::notExists($identifier, $column, $id, $throw_exception);
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
            throw new ValidationFailedException(tr('Cannot assign additional phone to ":to" from ":from" , only unassigned phones can be assigned', [
                ':from' => $current,
                ':to'   => $users_id,
            ]));
        }

        return $this->set('users_id', $users_id);
    }


    /**
     * Sets the users_email for this additional phone
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

        return $this->set('users_email', $users_email);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(Definition::new($this, 'verification_code')
                                    ->setOptional(true)
                                    ->setRender(false)
                                    ->setReadonly(true))
                    ->add(DefinitionFactory::getUsersId($this)
                                           ->setRender(false))
                    ->add(DefinitionFactory::getPhone($this)
                                           ->setSize(4)
                                           ->setOptional(false)
                                           ->setHelpText(tr('An extra phone for the user')))
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
                                        'word'   => function (string $word) {
                                            return Arrays::removeMatchingValues([
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
                                    ->setHelpText(tr('The type of phone')))
                    ->add(DefinitionFactory::getDateTime($this, 'verified_on')
                                           ->setReadonly(true)
                                           ->setSize(3)
                                           ->setNullInputType(EnumInputType::text)
                                           ->setNullDb(true, tr('Not verified'))
                                           ->addClasses('text-center')
                                           ->setLabel(tr('Verified on'))
                                           ->setHelpGroup(tr('Account information'))
                                           ->setHelpText(tr('The date when this user was phone verified. Empty if not yet verified')))
                    ->add(Definition::new($this, 'delete')
                                    ->setVirtual(true)
                                    ->setInputType(EnumInputType::submit)
                                    ->setSize(2)
                                    ->setLabel(tr('Delete'))
                                    ->addClasses('btn btn-outline-warning')
                                    ->setValue(tr('Delete')))
                    ->add(DefinitionFactory::getDescription($this)
                                           ->setHelpText(tr('The description for this phone')));
    }
}
