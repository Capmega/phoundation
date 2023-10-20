<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Exception;
use Phoundation\Accounts\Users\Interfaces\EmailsInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Data\Validator\Validate;
use Phoundation\Data\Validator\Validator;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Form;
use Phoundation\Web\Http\Html\Components\Interfaces\FormInterface;


/**
 * Class Emails
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Emails extends DataList implements EmailsInterface
{
    /**
     * Users class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `accounts_emails`.`id`,
                                        `accounts_emails`.`email`,
                                        `accounts_emails`.`account_type`
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
    public static function getUniqueField(): ?string
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
            return $this->setParentTrait($parent);
        }

        throw new OutOfBoundsException(tr('Specified parent ":parent" is invalid, it must have a UserInterface interface', [
            ':parent' => $parent
        ]));
    }


    /**
     * Returns Emails list object with emails for the specified user.
     *
     * @return static
     * @throws SqlMultipleResultsException, NotExistsException
     */
    public function load(): static
    {
        $this->parent  = User::get($this->parent, 'seo_name');
        $this->execute = [':users_id' => $this->parent->getId()];

        return parent::load();
    }


    /**
     * Creates and returns an HTML for the emails
     *
     * @param string $name
     * @return FormInterface
     */
    public function getHtmlForm(string $name = 'emails[]'): FormInterface
    {
        // Add extra entry with nothing selected
        $form      = Form::new();
        $content[] = Email::new()->getHtmlForm()->render();

        foreach ($this->ensureDataEntries()->getSource() as $email) {
            $content[] = $email->getHtmlForm()->render();
        }

        $form->addContent(implode('<hr>', $content));
        return $form;
    }


    /**
     * Apply all email account updates
     *
     * @param bool $clear_source
     * @return static
     * @throws Exception
     */
    public function apply(bool $clear_source = true): static
    {
        $this->checkReadonly('apply');

        if (empty($this->parent)) {
            throw new OutOfBoundsException(tr('Cannot apply emails, no parent user specified'));
        }

        $emails = [];
        $post   = Validator::get()
            ->select('--emails', true)->isOptional()->hasMaxCharacters(32768)->sanitizeForceArray()->isArray()->each()->hasMaxCharacters(276)
            ->validate(false);

        // Parse and sub validate
        if (isset($post['emails'])) {
            foreach ($post['emails'] as $email) {
                $email = trim($email);

                // Email type specified? extract, else default
                if (preg_match('/^\|(?:personal|business|other)$/i', $email)) {
                    $type  = Strings::fromReverse($email, '|');
                    $type  = strtolower($type);
                    $email = Strings::untilReverse($email, '|');

                } else {
                    $type  = 'other';
                }

                // Validate the email address
                Validate::new($email)->isEmail();

                $emails[$email] = [
                    'account_type' => $type,
                    'email'        => $email
                ];
            }
        }

        // Get a list of what we should add and remove and apply this
        $diff = Arrays::valueDiff(array_keys($this->getSource()), array_keys($emails));

        foreach ($diff['remove'] as $email) {
            Email::new($email, 'email')->delete();
            $this->removeByColumnValue($diff['remove'], 'email');
        }

        foreach ($diff['add'] as $email) {
            $email = Email::new()->setSource($emails[$email])->setUsersId($this->parent->getId())->save();
            $this->add($email);
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

        foreach ($this->getSource() as $email) {
            $email->save($force, $comments);
        }

        return $this;
    }
}
