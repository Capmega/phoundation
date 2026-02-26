<?php

/**
 * Class Emails
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

use Phoundation\Accounts\Users\Interfaces\EmailInterface;
use Phoundation\Accounts\Users\Interfaces\EmailsInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Data\DataEntries\DataIterator;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Traits\TraitDataParent;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;


class Emails extends DataIterator implements EmailsInterface
{
    use TraitDataParent {
        setParentObject as __setParent;
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
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'accounts_emails';
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
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
     * @param DataEntryInterface|RenderInterface|UrlInterface|null $_parent
     *
     * @return static
     */
    public function setParentObject(DataEntryInterface|RenderInterface|UrlInterface|null $_parent): static
    {
        if ($_parent instanceof UserInterface) {
            // Clear the source to avoid having a parent with the wrong children
            $this->source = [];

            return $this->__setParent($_parent);
        }

        throw new OutOfBoundsException(tr('Specified parent ":parent" is invalid, it must have a UserInterface interface', [
            ':parent' => $_parent,
        ]));
    }


    /**
     * Returns an Emails Iterator object with emails for the specified user.
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                      $like
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false): static
    {
        $this->_parent = User::new()->load($this->_parent);
        $this->execute  = [':users_id' => $this->_parent->getId()];

        return parent::load($identifiers, $like);
    }


    /**
     * Creates and returns an HTML for the emails
     *
     * @param string $name
     * @param bool   $meta_visible
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlFormObject(string $name = 'emails[][]', bool $meta_visible = false): DataEntryFormInterface
    {
        // Add extra entry with nothing selected
        $email       = Email::new()
                            ->setPrefix($name) // TODO What is this? Name is used as prefix, WHY?
                            ->getHtmlFormObject()
                                ->setRenderMeta($meta_visible);

        $_definitions = $email->getDefinitionsObject();
        $_definitions->get('email')->setSize(6);
        $_definitions->get('account_type')->setSize(6);
        $_definitions->get('verified_on')->setRender(false);
        $_definitions->get('delete')->setRender(false);

        $content[] = $email->render();

        foreach ($this->ensureDataEntries() as $email) {
            $content[] = $email->setPrefix($name)
                               ->getHtmlFormObject()
                               ->setRenderMeta($meta_visible)
                               ->render();
        }

        return DataEntryForm::new()
                            ->setDataEntryObject($this->_parent)
                            ->appendContent(implode('<hr>', $content))
                            ->setRenderContentsOnly(true);
    }


    /**
     * Apply all email account updates
     *
     * @param bool $require_clean_source
     * @param ValidatorInterface|array|null $source
     * @return static
     */
    public function apply(bool $require_clean_source = true, ValidatorInterface|array|null &$source = null): static
    {
        $this->checkReadonly('apply');

        if (empty($this->_parent)) {
            throw new OutOfBoundsException(tr('Cannot apply emails, no parent user specified'));
        }

        $emails = [];
        $post   = Validator::pick()
                           ->select('emails')->isOptional()->sanitizeForceArray()->skipValidation()
                           ->validate($require_clean_source);

        // Parse and sub validate
        if ($post['emails']) {
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
                        'description'  => isset_get($email[2]),
                    ];
                }

                // Ignore empty entries
                if (empty($email['email'])) {
                    continue;
                }

                $emails[array_get_safe($email, 'email')] = $email;
            }

            // Get a list of what we should add and remove and apply this
            $diff = Arrays::valueDiff($this->getAllRowsSingleColumn('email'), array_keys($emails), true);
            $diff = Arrays::deleteDiff($diff, $emails);

            foreach ($diff['delete'] as $id => $email) {
                Email::new()->load($id)->delete();

                $this->removeKeys($id);
            }

            foreach ($diff['add'] as $email) {
                if ($email) {
                    $this->add(Email::new(null)
                                    ->apply(false, $emails[$email])
                                    ->setUsersId($this->_parent->getId())
                                    ->save());
                }
            }

            // Update all other email addresses
            foreach ($diff['keep'] as $id => $email) {
                Email::new()->load($id)
                     ->apply(false, $emails[$email])
                     ->setUsersId($this->_parent->getId())
                     ->save();
            }
        }

        return $this;
    }


    /**
     * Save all the emails for this user
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

        if (empty($this->_parent)) {
            throw new OutOfBoundsException(tr('Cannot apply emails, no parent user specified'));
        }

        foreach ($this->ensureDataEntries() as $email) {
            $email->save($force, $skip_validation, $comments);
        }

        return $this;
    }


    /**
     * Add the specified email to the iterator array
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
        if (!$value instanceof EmailInterface) {
            if (!is_string($value)) {
                throw new OutOfBoundsException(tr('Invalid value ":value" specified, can only add "EmailInterface" to Emails Iterator class', [
                    ':value' => $value,
                ]));
            }

            $value = Email::new($value)
                          ->setAccountType('other');
        }

        // Ensure that the email list has a parent
        if (empty($this->_parent)) {
            throw new OutOfBoundsException(tr('Cannot add email ":email" to this emails list, the list has no parent specified', [
                ':email' => $value->getLogId(),
            ]));
        }

        // Ensure that the email has a users id and that the users id matches the id of the users parent
        if ($value->getUsersId()) {
            if ($value->getUsersId() !== $this->_parent->getId()) {
                throw new OutOfBoundsException(tr('Specified email ":email" has a different users id than the users id ":parent" for the emails in this list', [
                    ':email'  => $value->getEmail(),
                    ':parent' => $this->_parent->getId(),
                ]));
            }

        } else {
            $value->setUsersId($this->_parent->getId())
                  ->save();
        }

        return parent::add($value, $key, $skip_null_values, $exception);
    }
}
