<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Interfaces\EmailInterface;
use Phoundation\Accounts\Users\Interfaces\EmailsInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Traits\TraitDataParent;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\Interfaces\OutOfBoundsExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Stringable;


/**
 * Class Emails
 *
 *
 *
 * @see DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Emails extends DataList implements EmailsInterface
{
    use TraitDataParent {
        setParent as __setParent;
    }


    /**
     * Users class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `accounts_emails`.*
                               FROM     `accounts_emails`
                               WHERE    `accounts_emails`.`users_id` = :users_id
                                 AND    `accounts_emails`.`status` IS NULL
                               ORDER BY `email`');

        parent::__construct();
    }


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
    public static function getEntryClass(): string
    {
        return Email::class;
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
     * Returns Emails list object with emails for the specified user.
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
     * Creates and returns an HTML for the emails
     *
     * @param string $name
     * @param bool $meta_visible
     * @return DataEntryFormInterface
     */
    public function getHtmlDataEntryForm(string $name = 'emails[][]', bool $meta_visible = false): DataEntryFormInterface
    {
        // Add extra entry with nothing selected
        $email = Email::new()->setColumnPrefix($name)->getHtmlDataEntryFormObject()->setMetaVisible($meta_visible);
        $definitions = $email->getDefinitions();
        $definitions->get('email')->setSize(6);
        $definitions->get('account_type')->setSize(6);
        $definitions->get('verified_on')->setRender(false);
        $definitions->get('delete')->setRender(false);

        $content[] = $email->render();

        foreach ($this->ensureDataEntries() as $email) {
            $content[] = $email
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
     * Add the specified email to the iterator array
     *
     * @param mixed $value
     * @param Stringable|string|float|int|null $key
     * @param bool $skip_null
     * @param bool $exception
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static
    {
        if (!$value instanceof EmailInterface) {
            if (!is_string($value)) {
                throw new OutOfBoundsException(tr('Invalid value ":value" specified, can only add "EmailInterface" to Emails Iterator class', [
                    ':value' => $value
                ]));
            }

            $value = Email::new($value, 'email')->setAccountType('other');
        }

        // Ensure that the email list has a parent
        if (empty($this->parent)) {
            throw new OutOfBoundsException(tr('Cannot add email ":email" to this emails list, the list has no parent specified', [
                ':email' => $value->getLogId()
            ]));
        }

        // Ensure that the email has a users id and that the users id matches the id of the users parent
        if ($value->getUsersId()) {
            if ($value->getUsersId() !== $this->parent->getId()) {
                throw new OutOfBoundsException(tr('Specified email ":email" has a different users id than the users id ":parent" for the emails in this list', [
                    ':email' => $value->getEmail(),
                    ':parent' => $this->parent->getId()
                ]));
            }

        } else {
            $value->setUsersId($this->parent->getId())->save();
        }

        return parent::add($value, $key, $skip_null, $exception);
    }


    /**
     * Apply all email account updates
     *
     * @param bool $clear_source
     * @return static
     * @throws ValidationFailedException|OutOfBoundsExceptionInterface
     */
    public function apply(bool $clear_source = true): static
    {
        $this->checkReadonly('apply');

        if (empty($this->parent)) {
            throw new OutOfBoundsException(tr('Cannot apply emails, no parent user specified'));
        }

        $emails = [];
        $post   = Validator::get()
            ->select('emails')->isOptional()->sanitizeForceArray()
            ->validate($clear_source);

        // Parse and sub validate
        if (isset($post['emails'])) {
            foreach ($post['emails'] as $email) {
                // Command line specified emails will have a EMAIL|TYPE|DESCRIPTION string format instead of an array
                if (!is_array($email)) {
                    if (!is_string($email)) {
                        throw new ValidationFailedException(tr('Specified phone number has an invalid datatype'));
                    }

                    $email = trim($email);
                    $email = explode('|', $email);
                    $email = [
                        'email'        => isset_get($email[0]),
                        'account_type' => isset_get($email[1]),
                        'description'  => isset_get($email[2])
                    ];
                }

                // Ignore empty entries
                if (empty($email['email'])) {
                    continue;
                }

                $emails[isset_get($email['email'])] = $email;
            }

            // Get a list of what we should add and remove and apply this
            $diff = Arrays::valueDiff($this->getAllRowsSingleColumn('email'), array_keys($emails), true);
            $diff = Arrays::deleteDiff($diff, $emails);

            foreach ($diff['delete'] as $id => $email) {
                Email::get($id, 'id')->setEmail(null)->save()->erase();
                $this->removeKeys($id);
            }

            foreach ($diff['add'] as $email) {
                if ($email) {
                    $this->add(Email::new()
                        ->apply(false, $emails[$email])
                        ->setUsersId($this->parent->getId())
                        ->save());
                }
            }

            // Update all other email addresses
            foreach ($diff['keep'] as $id => $email) {
                Email::get($id, 'id')
                    ->apply(false, $emails[$email])
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
     * Save all the emails for this user
     *
     * @param bool $force
     * @param string|null $comments
     * @return static
     */
    public function save(bool $force = false, ?string $comments = null): static
    {
        $this->checkReadonly('save');

        if (empty($this->parent)) {
            throw new OutOfBoundsException(tr('Cannot apply emails, no parent user specified'));
        }

        foreach ($this->ensureDataEntries() as $email) {
            $email->save($force, $comments);
        }

        return $this;
    }
}
