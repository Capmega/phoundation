<?php

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Templates\Template;
use PHPMailer\PHPMailer\PHPMailer;


/**
 * Class Email
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Email
 */
class Email extends DataEntry
{
    /**
     * The template for this email
     *
     * @var Template $template
     */
    protected Template $template;

    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'emails';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Email');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return null;
    }


    /**
     * Sends the email
     *
     * @param bool $background
     * @return $this
     */
    public function send(bool $background = true): static
    {
        $account   = new EmailAccount($this->from);
        $phpmailer = new PHPMailer();
        $phpmailer->isSMTP();

        // Setup email host configuration
        $phpmailer->Host       = $account->getHost();
        $phpmailer->SMTPAuth   = $account->getSmtpAuth();
        $phpmailer->SMTPSecure = $account->getSmtpSecure();
        $phpmailer->Port       = $account->getPort();
        $phpmailer->Username   = $account->getUser();
        $phpmailer->Password   = $account->getPass();

        // Build email
        $phpmailer->body    = $this->body->;
        $phpmailer->subject = $this->subject;

        $phpmailer->isHTML($this->is_html);
        $phpmailer->setFrom($this->from->getEmail(), $this->from->getName());
        $phpmailer->setReplyTo($this->reply_to->getEmail(), $this->reply_to->getName());

        foreach ($this->cc as $cc) {
            $phpmailer->setCc($cc->getEmail(), $cc->getName());
        }

        foreach ($this->bcc as $bcc) {
            $phpmailer->setBcc($bcc->getEmail(), $bcc->getName());
        }


    }


    /**
     * Sets and returns the field definitions for the data fields in this DataEntry object
     *
     * Format:
     *
     * [
     *   field => [key => value],
     *   field => [key => value],
     *   field => [key => value],
     * ]
     *
     * "field" should be the database table column name
     *
     * Field keys:
     *
     * FIELD          DATATYPE           DEFAULT VALUE  DESCRIPTION
     * value          mixed              null           The value for this entry
     * visible        boolean            true           If false, this key will not be shown on web, and be readonly
     * virtual        boolean            false          If true, this key will be visible and can be modified but it
     *                                                  won't exist in database. It instead will be used to generate
     *                                                  a different field
     * element        string|null        "input"        Type of element, input, select, or text or callable function
     * type           string|null        "text"         Type of input element, if element is "input"
     * readonly       boolean            false          If true, will make the input element readonly
     * disabled       boolean            false          If true, the field will be displayed as disabled
     * label          string|null        null           If specified, will show a description label in HTML
     * size           int [1-12]         12             The HTML boilerplate column size, 1 - 12 (12 being the whole
     *                                                  row)
     * source         array|string|null  null           Array or query source to get contents for select, or single
     *                                                  value for text inputs
     * execute        array|null         null           Bound execution variables if specified "source" is a query
     *                                                  string
     * complete       array|bool|null    null           If defined must be bool or contain array with key "noword"
     *                                                  and "word". each key must contain a callable function that
     *                                                  returns an array with possible words for shell auto
     *                                                  completion. If bool, the system will generate this array
     *                                                  automatically from the rows for this field
     * cli            string|null        null           If set, defines the alternative column name definitions for
     *                                                  use with CLI. For example, the column may be name, whilst
     *                                                  the cli column name may be "-n,--name"
     * optional       boolean            false          If true, the field is optional and may be left empty
     * title          string|null        null           The title attribute which may be used for tooltips
     * placeholder    string|null        null           The placeholder attribute which typically shows an example
     * maxlength      string|null        null           The maxlength attribute which typically shows an example
     * pattern        string|null        null           The pattern the value content should match in browser client
     * min            string|null        null           The minimum amount for numeric inputs
     * max            string|null        null           The maximum amount for numeric inputs
     * step           string|null        null           The up / down step for numeric inputs
     * default        mixed              null           If "value" for entry is null, then default will be used
     * null_disabled  boolean            false          If "value" for entry is null, then use this for "disabled"
     * null_readonly  boolean            false          If "value" for entry is null, then use this for "readonly"
     * null_type      boolean            false          If "value" for entry is null, then use this for "type"
     *
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getParentsId($this)
                ->setReadonly(true)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Ensure categories id exists and that its or category
                    $validator->or('parents_name')->isDbId()->isQueryResult('SELECT `id` FROM `emails` WHERE `id` = :id AND `status` IS NULL', [':id' => '$emails']);
                }))
            ->addDefinition(DefinitionFactory::getParent($this)
                ->setReadonly(true)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Ensure category exists and that it's a category id or category name
                    $validator->or('parents_id')->isName()->setColumnFromQuery('parents_id', 'SELECT `id` FROM `templates` WHERE `name` = :name AND `status` IS NULL', [':id' => '$parents_name']);
                }))
            ->addDefinition(DefinitionFactory::getCategoriesId($this))
            ->addDefinition(DefinitionFactory::getCategory($this))
            ->addDefinition(DefinitionFactory::getCode($this)
                ->setDefault(tr('-')))
            ->addDefinition(DefinitionFactory::getUsersEmail($this, 'to_users_email')
                ->setCliField('--to-users-email USER-EMAIL')
                ->clearValidationFunctions()
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->or('to_users_id')->isEmail()->setColumnFromQuery('to_users_id', 'SELECT `id` FROM `accounts_users` WHERE `email` = :email AND `status` IS NULL', [':email' => '$to_users_email']);
                }))
            ->addDefinition(DefinitionFactory::getUsersId($this, 'to_users_id')
                ->setCliField('--to-users-id USERS-DATABASE-ID')
                ->setLabel(tr('Leader'))
                ->setHelpGroup(tr('Email meta information'))
                ->setHelpText(tr('The system user to whom we are sending this email'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->or('to_users_email')->isDbId()->isQueryResult('SELECT `id` FROM `accounts_users` WHERE `id` = :id AND `status` IS NULL', [':id' => '$to_users_id']);
                }))
            ->addDefinition(DefinitionFactory::getUsersEmail($this, 'from_users_email')
                ->setCliField('--from-users-email USER-EMAIL')
                ->clearValidationFunctions()
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->or('from_users_id')->isEmail()->setColumnFromQuery('from_users_id', 'SELECT `id` FROM `accounts_users` WHERE `email` = :email AND `status` IS NULL', [':email' => '$from_users_email']);
                }))
            ->addDefinition(DefinitionFactory::getUsersId($this, 'from_users_id')
                ->setCliField('--from-users-id USERS-DATABASE-ID')
                ->setLabel(tr('Leader'))
                ->setHelpGroup(tr('Email meta information'))
                ->setHelpText(tr('The user from whom this email came'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->or('from_users_email')->isDbId()->isQueryResult('SELECT `id` FROM `accounts_users` WHERE `id` = :id AND `status` IS NULL', [':id' => '$from_users_id']);
                }))
            ->addDefinition(DefinitionFactory::getName($this, 'to_name'))
            ->addDefinition(DefinitionFactory::getEmail($this, 'to_email'))
            ->addDefinition(DefinitionFactory::getName($this, 'from_name'))
            ->addDefinition(DefinitionFactory::getEmail($this, 'from_email'))
            ->addDefinition(Definition::new($this, 'subject')
                ->setCliField('--subject "SUBJECT"')
                ->setLabel(tr('Subject'))
                ->setHelpGroup(tr('Email information'))
                ->setHelpText(tr('The subject of this email'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->or('from_users_email')->isDbId()->isQueryResult('SELECT `id` FROM `accounts_users` WHERE `id` = :id AND `status` IS NULL', [':id' => '$from_users_id']);
                }))
            ->addDefinition(DefinitionFactory::getContent($this, 'body')
                ->setCliField('--body "BODY"')
                ->setLabel(tr('Body'))
                ->setHelpGroup(tr('Email information'))
                ->setHelpText(tr('The body text for this email')));
    }
}
