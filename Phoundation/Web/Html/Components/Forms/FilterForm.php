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
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataDateFormat;
use Phoundation\Data\Traits\TraitDataIterator;
use Phoundation\Data\Traits\TraitDataIteratorSource;
use Phoundation\Data\Traits\TraitDataRequestMethod;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Date\DateFormats;
use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Forms\Interfaces\FilterFormInterface;
use Phoundation\Web\Html\Components\Input\InputDateRange;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Http\Url;
use ReturnTypeWillChange;
use Stringable;


class FilterForm extends DataEntryForm implements FilterFormInterface
{
    use TraitDataRequestMethod;
    use TraitDataDateFormat;
    use TraitDataIterator;


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

        $this->defaultRequestMethod()
             ->setFormat(DateFormats::getDefaultPhp());

        // Define possible record states
        if (empty($this->states)) {
            $this->states = [
                'all'     => tr('All'),
                null      => tr('Active'),
                'locked'  => tr('Locked'),
                'deleted' => tr('Deleted'),
            ];
        }

        // Make sure this is a submittable form with GET method
        $this->setId('filters')
             ->useForm(true)
             ->getForm()
                ->setRequestMethod($this->request_method)
                ->setAction(Url::getWww());

        // Set basic definitions
        $this->definitions = Definitions::new()
                                        ->add(Definition::new(null, 'date_range')
                                                        ->setLabel(tr('Date range'))
                                                        ->setSize(4)
                                                        ->setOptional(true)
                                                        ->setElement(EnumElement::select)
                                                        ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                                            if (empty($this->source[$key])) {
                                                                if (empty($this->source['date_range'])) {
                                                                    $source = $this->getDateRangeDefault();
                                                                    $this->source[$key] = $source[0] . ' - ' . $source[1];
                                                                }
                                                            }

                                                            return InputDateRange::new()
                                                                                 ->setName($field_name)
                                                                                 ->useRanges('default')
                                                                                 ->setAutoSubmit(true)
                                                                                 ->setParentSelector($this->date_range_selector)
                                                                                 ->setValue($this->source[$key]);
                                                        })
                                                        ->addValidationFunction(function (ValidatorInterface $validator) {
                                                            $validator->isOptional()->isDateRange()->copyToKey('date_range_split');
                                                        }))

                                        ->add(Definition::new(null, 'date_range_split')
                                                        ->setRender(false)
                                                        ->addValidationFunction(function (ValidatorInterface $validator) {
                                                            $validator->isOptional($this->getDateRangeDefault())->sanitizeForceArray(' - ')->eachField()->isDate();
                                                        }))

                                        ->add(Definition::new(null, 'users_id')
                                                        ->setLabel(tr('User'))
                                                        ->setSize(4)
                                                        ->setOptional(true)
                                                        ->setInputType(EnumInputType::dbid)
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
                                                                               ->setNotSelectedLabel(tr('All'))
                                                                               ->setSelected(isset_get($this->source[$key]));
                                                        }))

                                        ->add(Definition::new(null, 'status')
                                                        ->setLabel(tr('Status'))
                                                        ->setSize(4)
                                                        ->setOptional(true)
                                                        ->setElement(EnumElement::select)
                                                        ->setKey(true, 'auto_submit')
                                                        ->setDataSource($this->states));

        // Auto apply
        $this->applyValidator(self::class);
    }


    /**
     * Sets a default value of GET for the request method of these filter forms
     *
     * @return $this
     */
    protected function defaultRequestMethod(): static
    {
        if ($this->request_method === null) {
            // By default, filter forms submit with GET method
            $this->request_method = EnumHttpRequestMethod::get;
        }

        return $this;
    }


    /**
     * Returns value for the specified key
     *
     * @note This is the standard Iterator::get() call, but here $exception is by default false
     *
     * @note If the form element is not rendering, no value will be returned
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = false): mixed
    {
        $definition = $this->definitions->get($key, false);

        if (!$definition?->getRender()) {
            // Non rendered elements will always return null
            return null;
        }

        return parent::get($key, $exception);
    }


    /**
     * Returns value for the specified key whether the entry rendered or not
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getForce(Stringable|string|float|int $key, bool $exception = false): mixed
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
                DateTime::new('-6 day')->format(DateFormats::getDefaultPhp()),
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
        return $this->get('date_range');
    }


    /**
     * Returns the date range
     *
     * @return array|null
     */
    public function getDateRangeSplit(): ?array
    {
        // Only return the split date range if the date range itself is set too
        if (parent::get('date_range')) {
            return $this->get('date_range_split');
        }

        return null;
    }


    /**
     * Returns the start date, if selected
     *
     * @param string|null $timezone
     *
     * @return DateTimeInterface|null
     */
    public function getStartDate(?string $timezone = 'user'): ?DateTimeInterface
    {
        static $return;

        if (!isset($return)) {
            $range = parent::get('date_range');
            $split = parent::get('date_range_split');

            if ($range and $split) {
                $return = DateTime::getBeginningOfDay($split[0], $timezone);

            } else {
                $return = null;
            }
        }

        return $return;
    }


    /**
     * Returns the stop date, if selected
     *
     * @param string|null $timezone
     *
     * @return DateTimeInterface|null
     */
    public function getStopDate(?string $timezone = 'user'): ?DateTimeInterface
    {
        static $return;

        if (!isset($return)) {
            $range = parent::get('date_range');
            $split = parent::get('date_range_split');

            if ($range and $split) {
                $return = DateTime::getEndOfDay($split[1], $timezone);

            } else {
                $return = null;
            }
        }

        return $return;
    }


    /**
     * Returns the filtered users_id
     *
     * @return int|null
     */
    public function getUsersId(): ?int
    {
        return get_null((int) $this->get('users_id'));
    }


    /**
     * Returns the filtered status
     *
     * @note This method is one of the very few object::getStatus() methods that might return FALSE. The reason for that
     *        is that "not selected" would normally return NULL, but status NULL actually (mostly) means "normal". So
     *        here, FALSE means "don't filter", NULL means "filter on status NULL", and any string means "Filter on this
     *        string"
     *
     * @return string|false|null
     */
    public function getStatus(): string|false|null
    {
        return get_null((string) $this->get('status'));
    }


    /**
     * Selects and returns the Validator object required for the current request method
     *
     * @return ValidatorInterface
     */
    protected function selectValidator(): ValidatorInterface
    {
        switch ($this->request_method) {
            case EnumHttpRequestMethod::get:
                return GetValidator::new();

            case EnumHttpRequestMethod::post:
                return PostValidator::new();

            default:
                throw new OutOfBoundsException(tr('HTTP method ":method" is not supported by the FilterForm class', [
                    ':method' => $this->request_method->value
                ]));
        }
    }


    /**
     * Apply the filters from the Validator
     *
     * @param string $class
     * @param bool   $clear_source
     *
     * @return static
     */
    protected function applyValidator(string $class, bool $clear_source = true): static
    {
        // Auto apply
        if ($class === static::class) {
            $validator = $this->selectValidator()->setDefinitionsObject($this->definitions);

            // Go over each field and let the field definition do the validation since it knows the specs
            foreach ($this->definitions as $column => $definition) {
//if ($column !== 'action') continue;
                $definition->validate($validator, null);
            }

            // Validate buttons too
            if ($this->definitions->hasButtons()) {
                foreach ($this->definitions->getButtons() as $button) {
                    $validator->select($button->getName())
                        ->isOptional()
                        ->hasValue($button->getValue());
                }
            }

            try {
                // Execute the validate method to get the results of the validation
                $this->source = $validator->validate($clear_source);

            } catch (ValidationFailedException $e) {
                // Add the DataEntry object type to the exception message
                throw $e->setMessage('(' . get_class($this) . ') ' . $e->getMessage());
            }
        }

        return $this;
    }


    /**
     * Automatically apply current filters to the query builder
     *
     * @param QueryBuilderInterface $builder
     *
     * @return $this
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $builder): static
    {
        if ($this->get('status') !== 'all') {
            $builder->addWhere(
                SqlQueries::is('`' . $builder->getFromTable() . '`.`status`', $this->get('status'), ':from_status', $builder->getExecuteByReference())
            );
        }

        if ($this->getStartDate()) {
            $builder->addWhere(
                    '`' . $builder->getFromTable() . '`.`created_on` >= :start', [':start' => $this->getStartDate()->format('mysql')]
                );
        }

        if ($this->getStopDate()) {
            $builder->addWhere(
                '`' . $builder->getFromTable() . '`.`created_on` <= :stop', [':stop' => $this->getStopDate()->format('mysql')]
            );
        }

        if ($this->getUsersId()) {
            $builder->addWhere(
                '`' . $builder->getFromTable() . '`.`created_by` = :created_by', [':created_by' => $this->getUsersId()]
            );
        }

        return $this;
    }
}
