<?php

/**
 * Class Phones
 *
 *
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Exception;
use Phoundation\Accounts\Users\Interfaces\PhoneInterface;
use Phoundation\Accounts\Users\Interfaces\PhonesInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Data\DataEntries\DataIterator;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Traits\TraitDataParent;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;


class Phones extends DataIterator implements PhonesInterface
{
    use TraitDataParent {
        setParentObject as __setParent;
    }


    /**
     * Users class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `accounts_phones`.*
                         FROM     `accounts_phones`
                         WHERE    `accounts_phones`.`users_id` = :users_id
                         AND      `accounts_phones`.`status` IS NULL
                         ORDER BY `phone`');

        parent::__construct();
    }


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
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return Phone::class;
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
     * Sets the parent
     *
     * @param DataEntryInterface|UrlInterface|RenderInterface|null $o_parent
     *
     * @return static
     */
    public function setParentObject(DataEntryInterface|UrlInterface|RenderInterface|null $o_parent): static
    {
        if ($o_parent instanceof UserInterface) {
            // Clear the source to avoid having a parent with the wrong children
            $this->source = [];

            return $this->__setParent($o_parent);
        }

        throw new OutOfBoundsException(tr('Specified parent ":parent" is invalid, it must have a UserInterface interface', [
            ':parent' => $o_parent,
        ]));
    }


    /**
     * Returns a Phones Iterator object with phones for the specified user.
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                      $like
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false): static
    {
        $this->o_parent  = User::new()->load($this->o_parent);
        $this->execute = [':users_id' => $this->o_parent->getId()];

        return parent::load($identifiers, $like);
    }


    /**
     * Creates and returns an HTML for the phones
     *
     * @param string $name
     * @param bool   $meta_visible
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlDataEntryFormObject(string $name = 'phones[][]', bool $meta_visible = false): DataEntryFormInterface
    {
        // Add extra entry with nothing selected
        $phone       = Phone::new()
                            ->setPrefix($name)
                            ->getHtmlDataEntryFormObject()
                            ->setRenderMeta($meta_visible);

        $o_definitions = $phone->getDefinitionsObject();
        $o_definitions->get('phone')->setSize(6);
        $o_definitions->get('account_type')->setSize(6);
        $o_definitions->get('verified_on')->setRender(false);
        $o_definitions->get('delete')->setRender(false);

        $content[] = $phone->render();

        foreach ($this->ensureDataEntries() as $phone) {
            $content[] = $phone->setPrefix($name)
                               ->getHtmlDataEntryFormObject()
                               ->setRenderMeta($meta_visible)
                               ->render();
        }

        return DataEntryForm::new()
                            ->setDataEntryObject($this->o_parent)
                            ->appendContent(implode('<hr>', $content))
                            ->setRenderContentsOnly(true);
    }


    /**
     * Apply all phone updates
     *
     * @param bool $require_clean_source
     * @param ValidatorInterface|array|null $source
     * @return static
     */
    public function apply(bool $require_clean_source = true, ValidatorInterface|array|null &$source = null): static
    {
        $this->checkReadonly('apply');

        if (empty($this->o_parent)) {
            throw new OutOfBoundsException(tr('Cannot apply phones, no parent user specified'));
        }

        $phones = [];
        $post   = Validator::pick()
                           ->select('phones')->isOptional()->sanitizeForceArray()->skipValidation()
                           ->validate($require_clean_source);

        // Parse and sub validate
        if ($post['phones']) {
            foreach ($post['phones'] as $phone) {
                if (empty(Strings::force($phone))) {
                    continue;
                }

                // Command line specified phones will have a EMAIL|TYPE|DESCRIPTION string format instead of an array
                if (!is_array($phone)) {
                    if (!is_string($phone)) {
                        throw new ValidationFailedException(tr('Specified phone number has an invalid datatype'));
                    }

                    $phone = trim($phone);
                    $phone = explode('|', $phone);
                    $phone = [
                        'phone'        => isset_get($phone[0]),
                        'account_type' => isset_get($phone[1]),
                        'description'  => isset_get($phone[2]),
                    ];
                }

                // Pre-validate the phone number because we need the phone numbers sanitized for comparison later!
                $phone = ArrayValidator::new($phone)
                                       ->select('phone')->isOptional()->sanitizePhoneNumber()
                                       ->select('delete')->isOptional()->sanitizeToBoolean()
                                       ->select('account_type')->isOptional('other')->hasMaxCharacters(8)->sanitizeLowercase()->isInArray([
                                           'personal',
                                           'business',
                                           'other',
                                       ])
                                       ->select('description')->isOptional()->isDescription()
                                       ->validate();

                // Ignore empty entries
                if (empty($phone['phone'])) {
                    continue;
                }

                $phones[array_get_safe($phone, 'phone')] = $phone;
            }

            // Get a list of what we should add and remove and apply this
            $diff = Arrays::valueDiff($this->getAllRowsSingleColumn('phone'), array_keys($phones), true);
            $diff = Arrays::deleteDiff($diff, $phones);

            foreach ($diff['delete'] as $id => $phone) {
                Phone::new()->load($id)->delete();

                $this->removeKeys($id);
            }

            foreach ($diff['add'] as $phone) {
                if ($phone) {
                    $this->add(Phone::new(null)
                                    ->apply(false, $phones[$phone])
                                    ->setUsersId($this->o_parent->getId())
                                    ->save());
                }
            }

            // Update all other phone numbers
            foreach ($diff['keep'] as $id => $phone) {
                $this->get($id)
                     ->apply(false, $phones[$phone])
                     ->setUsersId($this->o_parent->getId())
                     ->save();
            }
        }

        return $this;
    }


    /**
     * Save all the phones for this user
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        $this->checkReadonly('save');

        if (empty($this->o_parent)) {
            throw new OutOfBoundsException(tr('Cannot apply phones, no parent user specified'));
        }

        foreach ($this->ensureDataEntries() as $phone) {
            $phone->save($force, $skip_validation, $comments);
        }

        return $this;
    }


    /**
     * Add the specified phone to the iterator array
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        if (!$value instanceof PhoneInterface) {
            if (!is_string($value)) {
                throw new OutOfBoundsException(tr('Invalid value ":value" specified, can only add "PhoneInterface" to Phones Iterator class', [
                    ':value' => $value,
                ]));
            }

            $value = Phone::new()
                          ->setPhone($value)
                          ->setAccountType('other');
        }

        // Ensure that the phone list has a parent
        if (empty($this->o_parent)) {
            throw new OutOfBoundsException(tr('Cannot add phone ":phone" to this phones list, the list has no parent specified', [
                ':phone' => $value->getLogId(),
            ]));
        }

        // Ensure that the phone has a users id and that the users id matches the id of the users parent
        if ($value->getUsersId()) {
            if ($value->getUsersId() !== $this->o_parent->getId()) {
                throw new OutOfBoundsException(tr('Specified phone ":phone" has a different users id than the users id ":parent" for the phones in this list', [
                    ':phone'  => $value->getPhone(),
                    ':parent' => $this->o_parent->getId(),
                ]));
            }

        } else {
            $value->setUsersId($this->o_parent->getId())
                  ->save();
        }

        return parent::add($value, $key, $skip_null_values, $exception);
    }
}
