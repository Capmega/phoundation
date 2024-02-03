<?php

declare(strict_types=1);

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Os\Processes\Commands\PhoCommand;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Templates\Template;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;
use PHPMailer\PHPMailer\PHPMailer;


/**
 * Class Email
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Emails
 */
class Email extends DataEntry
{
    protected ?EmailAddress $from = null;


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
    public static function getUniqueColumn(): ?string
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
        if ($background) {
            // Set status to SEND so that it will send as a background process
            $this->setStatus('PENDING-SEND');
            PhoCommand::new('emails/pending/send')->execute(EnumExecuteMethod::background);

        } else {
            $from      = $this->from;
            $phpmailer = new PHPMailer();
            $phpmailer->isSMTP();

            // Setup email host configuration
            $phpmailer->Host       = $from->getSmtpHost();
            $phpmailer->SMTPAuth   = $from->getSmtpAuth();
            $phpmailer->SMTPSecure = $from->getSmtpSecure();
            $phpmailer->Port       = $from->getPort();
            $phpmailer->Username   = $from->getUser();
            $phpmailer->Password   = $from->getPass();

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

            // Send the email
            $phpmailer->send();
            $this->setStatus('SENT');
            return $this;
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
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getUsersEmail($this)
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getUsersId($this)
                ->setVisible(false))
            ->addDefinition(Definition::new($this, 'parents_id')
                ->setVisible(false)
                ->setInputType(InputTypeExtended::dbid)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Ensure the specified parents_id exists
                    $validator->isOptional()->isQueryResult('SELECT `id` FROM `emails` WHERE `id` = :id', [':id' => '$parents_id']);
                }))
            ->addDefinition(Definition::new($this, 'main')
                ->setVisible(false)
                ->setInputType(InputType::checkbox))
            ->addDefinition(Definition::new($this, 'read')
                ->setVisible(false)
                ->setInputType(InputType::checkbox))
            ->addDefinition(Definition::new($this, 'categories_id')
                ->setVisible(false)
                ->setInputType(InputTypeExtended::dbid)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Ensure the specified parents_id exists
                    $validator->isOptional()->isQueryResult('SELECT `id` FROM `categories` WHERE `id` = :id', [':id' => '$categories']);
                }))
            ->addDefinition(Definition::new($this, 'templates_id')
                ->setVisible(false)
                ->setInputType(InputTypeExtended::dbid)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Ensure the specified parents_id exists
                    $validator->isOptional()->isQueryResult('SELECT `id` FROM `storage_pages` WHERE `id` = :id AND `template` = 1', [':id' => '$templates_id']);
                }))
            ->addDefinition(DefinitionFactory::getCode($this)
                ->setSize(3))
            ->addDefinition(Definition::new($this, 'subject')
                ->setSize(3)
                ->setMaxlength(255))
            ->addDefinition(Definition::new($this, 'body')
                ->setSize(3)
                ->setSize(12)
                ->setMaxlength(16_777_215));
    }
}
