<?php

/**
 * Class Phone
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

use Phoundation\Accounts\Users\Exception\PhoneNotExistsException;
use Phoundation\Accounts\Users\Interfaces\PhoneInterface;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryAccountType;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPhone;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUser;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryVerificationCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryVerifiedOn;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Sanitize;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;
use Stringable;


class Phone extends DataEntry implements PhoneInterface
{
    use TraitDataEntryUser;
    use TraitDataEntryPhone;
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
        return 'accounts_phones';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
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
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_null_identifier
     * @param EnumLoadParameters|null                   $on_not_exists
     *
     * @return static|null
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null): ?static
    {
        try {
            return parent::load($identifier, $on_null_identifier, $on_not_exists);

        } catch (DataEntryNotExistsException|DataEntryDeletedException $e) {
            throw new PhoneNotExistsException($e);
        }
    }


    /**
     * Returns true if an entry with the specified identifier exists
     *
     * @param array|Stringable|string|int $identifier      The unique identifier, but typically not the database id, usually
     *                                                     the seo_email, or seo_name
     * @param int|null                    $not_id
     * @param bool                        $throw_exception If the entry does not exist, instead of returning false will throw
     *                                                     a DataEntryNotExistsException
     *
     * @return bool
     */
    public static function exists(array|Stringable|string|int $identifier, ?int $not_id = null, bool $throw_exception = false): bool
    {
        $identifier = Sanitize::new($identifier)
                              ->phoneNumber()
                              ->getSource();

        return parent::notExists($identifier, $not_id, $throw_exception);
    }


    /**
     * Returns true if an entry with the specified identifier does not exist
     *
     * @param array|Stringable|string|int $identifier      The unique identifier, but typically not the database id, usually
     *                                                     the seo_email, or seo_name
     * @param int|null                    $id              If specified, will ignore the found entry if it has this ID as it
     *                                                     will be THIS object
     * @param bool                        $throw_exception If the entry exists (and does not match id, if specified), instead
     *                                                     of returning false will throw a DataEntryNotExistsException
     *
     * @return bool
     */
    public static function notExists(array|Stringable|string|int $identifier, ?int $id = null, bool $throw_exception = false): bool
    {
        $identifier = Sanitize::new($identifier)
                              ->phoneNumber()
                              ->getSource();

        return parent::notExists($identifier, $id, $throw_exception);
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
                    throw new ValidationFailedException(tr('Cannot assign additional phone to ":to" from ":from", only unassigned phones can be assigned', [
                        ':from' => $current,
                        ':to'   => $users_id,
                    ]));
                }
            }
        }

        return $this->set($users_id, 'users_id');
    }


    /**
     * Sets the users_phone for this additional phone
     *
     * @param string|null $users_phone
     *
     * @return static
     */
    public function setUsersPhone(?string $users_phone): static
    {
        if ($users_phone) {
            $current = $this->getUsersPhone();

            if ($current and ($current !== $users_phone)) {
                throw new ValidationFailedException(tr('Cannot assign additional phone to ":to" from ":from", only unassigned phones can be assigned', [
                    ':from' => $current,
                    ':to'   => $users_phone,
                ]));
            }
        }

        return $this->set($users_phone, 'users_phone');
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

                      ->add(DefinitionFactory::newUsersEmail('users_email')
                                             ->setOptional(true)
                                             ->setVirtual(true)
                                             ->setRender(false))

                      ->add(DefinitionFactory::newPhone()
                                           ->setSize(4)
                                           ->setOptional(true)
                                           ->setHelpText(tr('An extra phone for the user')))

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
                                    ->setHelpText(tr('The type of phone')))

                    ->add(DefinitionFactory::newDateTime('verified_on')
                                           ->setReadonly(true)
                                           ->setSize(3)
                                           ->setDbNullInputType(EnumInputType::text)
                                           ->setNullDisplay(tr('Not verified'))
                                           ->addClasses('text-center')
                                           ->setLabel(tr('Verified on'))
                                           ->setHelpGroup(tr('Account information'))
                                           ->setHelpText(tr('The date when this user was phone verified. Empty if not yet verified')))

                    ->add(DefinitionFactory::newButton('delete')
                                           ->setSize(2)
                                           ->setOptional(true)
                                           ->setVirtual(true)
                                           ->setLabel(tr('Delete'))
                                           ->setOutput(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                               return Button::new()
                                                            ->setButtonType(EnumButtonType::submit)
                                                            ->setBlock(true)
                                                            ->setMode(EnumDisplayMode::danger)
                                                            ->setOutlined(true)
                                                            ->setValue('delete_' . $source['id'])
                                                            ->setContent(tr('Delete'));
                                           }))

                    ->add(DefinitionFactory::newDescription()
                                           ->setHelpText(tr('The description for this phone')));

        return $this;
    }
}
