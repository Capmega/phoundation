<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Exception;
use Phoundation\Accounts\Users\Interfaces\PhonesInterface;
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
 * Class Phones
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Phones extends DataList implements PhonesInterface
{
    /**
     * Users class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `accounts_phones`.`id`,
                                        `accounts_phones`.`phone`,
                                        `accounts_phones`.`account_type`
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
    public static function getUniqueField(): ?string
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
            return $this->setParentTrait($parent);
        }

        throw new OutOfBoundsException(tr('Specified parent ":parent" is invalid, it must have a UserInterface interface', [
            ':parent' => $parent
        ]));
    }


    /**
     * Returns Phones list object with phones for the specified user.
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
     * Creates and returns an HTML for the phones
     *
     * @param string $name
     * @return FormInterface
     */
    public function getHtmlForm(string $name = 'phones[]'): FormInterface
    {
        // Add extra entry with nothing selected
        $form      = Form::new();
        $content[] = Phone::new()->getHtmlForm()->render();

        foreach ($this->ensureDataEntries()->getSource() as $phone) {
            $content[] = $phone->getHtmlForm()->render();
        }

        $form->addContent(implode('<hr>', $content));
        return $form;
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
            ->select('--phones', true)->isOptional()->hasMaxCharacters(32768)->sanitizeForceArray()->isArray()->each()->hasMaxCharacters(276)
            ->validate(false);

        // Parse and sub validate
        if (isset($post['phones'])) {
            foreach ($post['phones'] as $phone) {
                $phone = trim($phone);

                // Phone type specified? extract, else default
                if (preg_match('/^\|(?:personal|business|other)$/i', $phone)) {
                    $type = Strings::fromReverse($phone, '|');
                    $type = strtolower($type);
                    $phone = Strings::untilReverse($phone, '|');

                } else {
                    $type = 'other';
                }

                // Validate the phone address
                Validate::new($phone)->isPhone();

                $phones[$phone] = [
                    'account_type' => $type,
                    'phone' => $phone
                ];
            }
        }

        // Get a list of what we should add and remove and apply this
        $diff = Arrays::valueDiff(array_keys($this->getSource()), array_keys($phones));

        foreach ($diff['remove'] as $phone) {
            Phone::new($phone, 'phone')->delete();
            $this->removeByColumnValue($diff['remove'], 'phone');
        }

        foreach ($diff['add'] as $phone) {
            $phone = Phone::new()->setSource($phones[$phone])->setUsersId($this->parent->getId())->save();
            $this->add($phone);
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

        foreach ($this->getSource() as $phone) {
            $phone->save($force, $comments);
        }

        return $this;
    }
}
