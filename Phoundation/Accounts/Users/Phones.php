<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Exception;
use Phoundation\Accounts\Users\Interfaces\PhoneInterface;
use Phoundation\Accounts\Users\Interfaces\PhonesInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Traits\TraitDataParent;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Stringable;


/**
 * Class Phones
 *
 *
 *
 * @see DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Phones extends DataList implements PhonesInterface
{
    use TraitDataParent {
        setParent as __setParent;
    }


    /**
     * Users class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `accounts_phones`.*
                               FROM     `accounts_phones`
                               WHERE    `accounts_phones`.`users_id` = :users_id
                                 AND    `accounts_phones`.`status` IS NULL
                               ORDER BY `phone`');

        parent::__construct();
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
    public static function getEntryClass(): string
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
     * @param DataEntryInterface $parent
     * @return static
     */
    public function setParent(DataEntryInterface $parent): static
    {
        if ($parent instanceof UserInterface) {
            // Clear the source to avoid having a parent with the wrong children
            $this->source = [];
            return $this->__setParent($parent);
        }

        throw new OutOfBoundsException(tr('Specified parent ":parent" is invalid, it must have a UserInterface interface', [
            ':parent' => $parent
        ]));
    }


    /**
     * Returns Phones list object with phones for the specified user.
     *
     * @param bool $clear
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static
    {
        $this->parent  = User::get($this->parent,  'seo_name');
        $this->execute = [':users_id' => $this->parent->getId()];

        return parent::load();
    }


    /**
     * Creates and returns an HTML for the phones
     *
     * @param string $name
     * @param bool $meta_visible
     * @return DataEntryFormInterface
     */
    public function getHtmlDataEntryForm(string $name = 'phones[][]', bool $meta_visible = false): DataEntryFormInterface
    {
        // Add extra entry with nothing selected
        $phone = Phone::new()->setColumnPrefix($name)->getHtmlDataEntryFormObject()->setMetaVisible($meta_visible);
        $definitions = $phone->getDefinitions();
        $definitions->get('phone')->setSize(6);
        $definitions->get('account_type')->setSize(6);
        $definitions->get('verified_on')->setRender(false);
        $definitions->get('delete')->setRender(false);

        $content[] = $phone->render();

        foreach ($this->ensureDataEntries() as $phone) {
            $content[] = $phone
                ->setColumnPrefix($name)
                ->getHtmlDataEntryFormObject()
                ->setMetaVisible($meta_visible)
                ->render();
        }

        return DataEntryForm::new()
            ->appendContent(implode('<hr>', $content))
            ->setRenderContentsOnly(true);
    }


    /**
     * Add the specified phone to the iterator array
     *
     * @param mixed $value
     * @param Stringable|string|float|int|null $key
     * @param bool $skip_null
     * @param bool $exception
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static
    {
        if (!$value instanceof PhoneInterface) {
            if (!is_string($value)) {
                throw new OutOfBoundsException(tr('Invalid value ":value" specified, can only add "PhoneInterface" to Phones Iterator class', [
                    ':value' => $value
                ]));
            }

            $value = Phone::new()
                ->setPhone($value)
                ->setAccountType('other');
        }

        // Ensure that the phone list has a parent
        if (empty($this->parent)) {
            throw new OutOfBoundsException(tr('Cannot add phone ":phone" to this phones list, the list has no parent specified', [
                ':phone' => $value->getLogId()
            ]));
        }

        // Ensure that the phone has a users id and that the users id matches the id of the users parent
        if ($value->getUsersId()) {
            if ($value->getUsersId() !== $this->parent->getId()) {
                throw new OutOfBoundsException(tr('Specified phone ":phone" has a different users id than the users id ":parent" for the phones in this list', [
                    ':phone' => $value->getPhone(),
                    ':parent' => $this->parent->getId()
                ]));
            }

        } else {
            $value->setUsersId($this->parent->getId())->save();
        }

        return parent::add($value, $key, $skip_null, $exception);
    }


    /**
     * Apply all phone updates
     *
     * @param bool $clear_source
     * @return static
     * @throws Exception
     */
    public function apply(bool $clear_source = true): static
    {
        $this->checkReadonly('apply');

        if (empty($this->parent)) {
            throw new OutOfBoundsException(tr('Cannot apply phones, no parent user specified'));
        }

        $phones = [];
        $post   = Validator::get()
            ->select('phones')->isOptional()->sanitizeForceArray()
            ->validate($clear_source);

        // Parse and sub validate
        if (isset($post['phones'])) {
            foreach ($post['phones'] as $phone) {
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
                        'description'  => isset_get($phone[2])
                    ];
                }

                // Pre-validate the phone number because we need the phone numbers sanitized for comparison later!
                $phone = ArrayValidator::new($phone)
                    ->select('phone')->isOptional()->sanitizePhoneNumber()
                    ->select('delete')->isOptional()->sanitizeToBoolean()
                    ->select('account_type')->isOptional('other')->hasMaxCharacters(8)->sanitizeLowercase()->isInArray(['personal', 'business', 'other'])
                    ->select('description')->isOptional()->isDescription()
                    ->validate();

                // Ignore empty entries
                if (empty($phone['phone'])) {
                    continue;
                }

                $phones[isset_get($phone['phone'])] = $phone;
            }

            // Get a list of what we should add and remove and apply this
            $diff = Arrays::valueDiff($this->getAllRowsSingleColumn('phone'), array_keys($phones), true);
            $diff = Arrays::deleteDiff($diff, $phones);

            foreach ($diff['delete'] as $id => $phone) {
                Phone::get($id, 'id')->setPhone(null)->save()->erase();
                $this->removeKeys($id);
            }

            foreach ($diff['add'] as $phone) {
                if ($phone) {
                    $this->add(Phone::new()
                        ->apply(false, $phones[$phone])
                        ->setUsersId($this->parent->getId())
                        ->save());
                }
            }

            // Update all other phone numbers
            foreach ($diff['keep'] as $id => $phone) {
                Phone::get($id, 'id')
                    ->apply(false, $phones[$phone])
                    ->setUsersId($this->parent->getId())
                    ->save();
            }
        }

        // Clear source if required
        if ($clear_source) {
            PostValidator::new()->noArgumentsLeft();
        }

        return $this;
    }


    /**
     * Save all the phones for this user
     *
     * @param bool $force
     * @param string|null $comments
     * @return static
     */
    public function save(bool $force = false, ?string $comments = null): static
    {
        $this->checkReadonly('save');

        if (empty($this->parent)) {
            throw new OutOfBoundsException(tr('Cannot apply phones, no parent user specified'));
        }

        foreach ($this->ensureDataEntries() as $phone) {
            $phone->save($force, $comments);
        }

        return $this;
    }
}
