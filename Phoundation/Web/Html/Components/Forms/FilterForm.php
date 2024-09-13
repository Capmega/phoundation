<?php

/**
 * Class FilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms;

use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Date\DateFormats;
use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Components\Input\InputDateRange;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Http\Url;
use ReturnTypeWillChange;
use Stringable;


class FilterForm extends DataEntryForm
{
    /**
     * Tracks the default date range
     *
     * @var array $date_range_default
     */
    protected array $date_range_default;

    /**
     * The HTML ID for the date range filter
     *
     * @var string|null $date_range_selector
     */
    protected ?string $date_range_selector = null;

    /**
     * The different status values to filter on
     *
     * @var array $states
     */
    protected array $states;


    /**
     * FilterForm class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);

        // Define possible record states
        if (empty($this->states)) {
            $this->states = [
                'all'     => tr('All'),
                null      => tr('Active'),
                'locked'  => tr('Locked'),
                'deleted' => tr('Deleted'),
            ];
        }

//        if (empty($this->source)) {
//            // Pull all filter data from HTTP GET
//            $this->source = GetValidator::new()
//                ->select('date_range')->isOptional()->copyTo('date_range_split')->doNotValidate()
//                ->select('date_range_split')->isOptional($this->getDateRangeDefault())->sanitizeForceArray(' - ')->each()->isDate()
//                ->select('users_id')->isOptional()->isDbId()
//                ->validate(false);
//        }

        // Make sure this is a submittable form with GET method
        $this->setId('filters')
             ->useForm(true)
             ->getForm()
             ->setRequestMethod(EnumHttpRequestMethod::get)
             ->setAction(Url::getWww());

        // Set basic definitions
        $this->definitions = Definitions::new()
                                        ->add(Definition::new(null, 'date_range_split')
                                                        ->setRender(false)
                                                        ->addValidationFunction(function (ValidatorInterface $validator) {
                                                            $validator->isOptional($this->getDateRangeDefault())->sanitizeForceArray(' - ')->each()->isDate();
                                                        }))

                                        ->add(Definition::new(null, 'date_range')
                                                        ->setLabel(tr('Date range'))
                                                        ->setSize(4)
                                                        ->setOptional(true)
                                                        ->setElement(EnumElement::select)
                                                        ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                                            if (empty($this->source[$key])) {
                                                                $this->source[$key] = $this->source['date_range_split'][0] . ' - ' . $this->source['date_range_split'][1];
                                                            }

                                                            return InputDateRange::new()
                                                                                 ->setName($field_name)
                                                                                 ->useRanges('default')
                                                                                 ->setAutoSubmit(true)
                                                                                 ->setParentSelector($this->date_range_selector)
                                                                                 ->setValue($this->source[$key]);
                                                        })
                                                        ->addValidationFunction(function (ValidatorInterface $validator) {
                                                            if ($validator->getSelectedValue()) {
                                                                $validator->matchesRegex('/^[\d]{2}[-/\s][\d]{2}[-/\s][\d]{4}/]\s-\s[\d]{2}[-/\s][\d]{2}[-/\s][\d]{4}/]$/');

                                                            } else {
                                                                $validator->doNotValidate();
                                                            }
                                                        }))

                                        ->add(Definition::new(null, 'users_id')
                                                        ->setLabel(tr('User'))
                                                        ->setSize(2)
                                                        ->setOptional(true)
                                                        ->setElement(EnumElement::select)
                                                        ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                                            return Users::new()->getHtmlSelect()
                                                                               ->setSourceQuery('SELECT    `accounts_users`.`id`, COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `accounts_users`.`first_names`, `accounts_users`.`last_names`)), ""), `accounts_users`.`nickname`, `accounts_users`.`username`, `accounts_users`.`email`, "' . tr('System') . '") AS `name` 
                                                                                                 FROM      `accounts_users`
                                                                                                 JOIN      `accounts_users_rights` ON `accounts_users_rights`.`users_id` = `accounts_users`.`id` AND `accounts_users_rights`.`name` = "biller"                                        
                                                                                                 LEFT JOIN `accounts_users_rights` AS `exclude` ON `exclude`.`users_id` = `accounts_users`.`id` AND `exclude`.`name` IN (' . implode(',', Arrays::quote(Config::getArray('accounts.rights.test', ['developer', 'test', 'demo']))) . ')
                                                                                                 WHERE     `accounts_users`.`status` IS NULL
                                                                                                   AND     `exclude`.`id` IS NULL
                                                                                                 ORDER BY  `name`')
                                                                               ->setAutoSubmit(true)
                                                                               ->setName($field_name)
                                                                               ->setNotSelectedLabel(tr('Select'))
                                                                               ->setSelected($this->source[$key]);
                                                        }))

                                        ->add(Definition::new(null, 'entry_status')
                                                        ->setLabel(tr('Status'))
                                                        ->setSize(2)
                                                        ->setOptional(true)
                                                        ->setElement(EnumElement::select)
                                                        ->setValue(isset_get($this->source['entry_status']))
                                                        ->setKey(true, 'auto_submit')
                                                        ->setDataSource($this->states));
    }


    /**
     * Apply the filters from the Validator
     *
     * @param bool $clear_source
     *
     * @return static
     */
    public function apply(bool $clear_source = true): static
    {
        $validator = GetValidator::new()
                                 ->setSourceObjectClass(static::class);

        // Go over each field and let the field definition do the validation since it knows the specs
        foreach ($this->definitions as $definition) {
            $definition->validate($validator, null);
        }

        try {
            // Execute the validate method to get the results of the validation
            $this->source = $validator->validate($clear_source);

        } catch (ValidationFailedException $e) {
            // Add the DataEntry object type to the exception message
            throw $e->setMessage('(' . get_class($this) . ') ' . $e->getMessage());
        }

        return $this;
    }


    /**
     * Returns value for the specified key
     *
     * @note This is the standard Iterator::getSourceKey, but here $exception is by default false
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = false): mixed
    {
        return parent::get($key, $exception);
    }


    /**
     * Returns the date range mounting id
     *
     * @return string|null
     */
    public function getDateRangeSelector(): ?string
    {
        return $this->date_range_selector;
    }


    /**
     * Sets the date range mounting id
     *
     * @param string|null $id
     * @return static
     */
    public function setDateRangeSelector(?string $id): static
    {
        $this->date_range_selector = $id;
        return $this;
    }


    /**
     * Returns the date range default value
     *
     * @return array|null
     */
    public function getDateRangeDefault(): ?array
    {
        // Set default date range
        if (empty($this->date_range_default)) {
            $this->date_range_default = [
                DateTime::new('-7 day')->format(DateFormats::getDefaultPhp()),
                DateTime::new()->format(DateFormats::getDefaultPhp())
            ];
        }

        return $this->date_range_default;
    }


    /**
     * Returns the date range default value
     *
     * @param array|string $date_range_default
     *
     * @return static
     */
    public function setDateRangeDefault(array|string $date_range_default): static
    {
        if (is_string($date_range_default)) {
            $date_range_default = explode('-', $date_range_default);

            foreach ($date_range_default as &$date) {
                $date = DateTime::new($date);
            }

            unset($date);
        }

        if (count($date_range_default) != 2) {
            throw new OutOfBoundsException(tr('Specified date range default value should contain 2 date ranges but contains ":value"', [
                ':value' => $date_range_default
            ]));
        }

        foreach ($date_range_default as $date) {
            if (($date instanceof DateTimeInterface) or is_string($date)) {
                $date = DateTime::new($date)->format('m/d/Y');
                continue;
            }

            throw new OutOfBoundsException(tr('Specified date range default value should contain 2 date ranges with DateTimeInterface but contains ":value"', [
                ':value' => $date_range_default
            ]));
        }

        $this->date_range_default = $date_range_default;
        return $this;
    }


    /**
     * Returns the date range
     *
     * @return string|null
     */
    public function getDateRange(): ?string
    {
        return $this->get('date_range_split');
    }


    /**
     * Returns the start date, if selected
     *
     * @return DateTimeInterface|null
     */
    public function getDateStart(): ?DateTimeInterface
    {
        static $return;

        if (!isset($return)) {
            $split = $this->get('date_range_split');

            if ($split) {
                $return = DateTime::new($split[0]);

            } else {
                $return = null;
            }
        }

        return $return;
    }


    /**
     * Returns the stop date, if selected
     *
     * @return DateTimeInterface|null
     */
    public function getDateStop(): ?DateTimeInterface
    {
        static $return;

        if (!isset($return)) {
            $split = $this->get('date_range_split');

            if ($split) {
                $return = DateTime::new($split[1]);

            } else {
                $return = null;
            }
        }

        return $return;
    }
}
