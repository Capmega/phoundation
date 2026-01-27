<?php

/**
 * Class FilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\Definitions;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataBooleanUseForm;
use Phoundation\Data\Traits\TraitDataDateFormat;
use Phoundation\Data\Traits\TraitDataRedirectUrlObject;
use Phoundation\Data\Traits\TraitDataRequestMethod;
use Phoundation\Data\Traits\TraitMethodsGetTypesafe;
use Phoundation\Data\Traits\TraitMethodsVirtualColumns;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\PhoDateTimeFormats;
use Phoundation\Date\PhoDateTime;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
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
    use TraitMethodsGetTypesafe;
    use TraitMethodsVirtualColumns;
    use TraitDataBooleanUseForm;
    use TraitDataRedirectUrlObject;


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
     * Returns the filters that (still) have to be applied
     *
     * @var IteratorInterface $o_applied_filters
     */
    protected IteratorInterface $o_applied_filters;

    /**
     * Tracks if special users should be filtered out
     *
     * @var bool $filter_special_users
     */
    protected bool $filter_special_users = true;

    /**
     * Tracks whether by default this filter form requires a clean source
     *
     * @var bool $require_clean_source
     */
    protected bool $require_clean_source = false;


    /**
     * FilterForm class constructor
     *
     * @param string|null $source
     */
    public function __construct(?string $source = null)
    {
        parent::__construct($source);

        $this->setUseForm(true)
             ->defaultRequestMethod()
             ->setFormat(PhoDateTimeFormats::getDefaultDateFormatPhp());

        // Define possible record states
        if (empty($this->states)) {
            $this->states = [
                'all'     => tr('All'),
                null      => tr('Active'),
                'locked'  => tr('Locked'),
                'deleted' => tr('Deleted'),
            ];
        }

        // Set ID for these filters
        $this->setId('filters');

        // Set basic definitions
        $this->o_definitions = Definitions::new()
                                          ->setReadonly($this->getReadonly())
                                          ->setDisabled($this->getDisabled())
                                          ->add(Definition::new('date_range')
                                                          ->setLabel(tr('Date range'))
                                                          ->setSize(4)
                                                          ->setOptional(true)
                                                          ->setAutoSubmit(true)
                                                          ->setElement(EnumElement::select)
                                                          ->setOutput(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
                                                              if (empty($this->source[$key])) {
                                                                  if (empty($this->source['date_range'])) {
                                                                      $source = $this->getDateRangeDefault();
                                                                      $this->source[$key] = PhoDateTime::new($source[0])->format(EnumDateFormat::user_date) . ' - ' . PhoDateTime::new($source[1])->format(EnumDateFormat::user_date);
                                                                  }
                                                              }

                                                              return InputDateRange::new()
                                                                                   ->setName($field_name)
                                                                                   ->useRanges('default')
                                                                                   ->setAutoSubmit(true)
                                                                                   ->setMinimumDateObject(PhoDateTime::new('-1 year'))
                                                                                   ->setMaximumDateObject(PhoDateTime::newToday())
                                                                                   ->setParentSelector($this->date_range_selector)
                                                                                   ->setValue($this->source[$key]);
                                                          })
                                                          ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                              if (empty($o_validator->getSelectedValue())) {
                                                                  $source = $this->getDateRangeDefault();
                                                                  $o_validator->setSelectedValue(PhoDateTime::new($source[0])->format(EnumDateFormat::user_date) . ' - ' . PhoDateTime::new($source[1])->format(EnumDateFormat::user_date));
                                                              }

                                                              $o_validator->isOptional()->isDateRange()->copyToKey('date_range_split');
                                                          }))

                                          ->add(Definition::new('date_range_split')
                                                          ->setRender(false)
                                                          ->setForceValidations(true)
                                                          ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                              $o_validator->isOptional()->sanitizeForceArray('-')->forEachField()->sanitizeTrim()->isDate();
                                                          }))

                                          ->add(Definition::new('users_id')
                                                          ->setLabel(tr('User'))
                                                          ->setSize(4)
                                                          ->setOptional(true)
                                                          ->setInputType(EnumInputType::dbid)
                                                          ->setOutput(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
                                                              return Users::new()->getHtmlSelectOld()
                                                                                 ->setSourceQuery('SELECT    `accounts_users`.`id`, COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `accounts_users`.`first_names`, `accounts_users`.`last_names`)), ""), `accounts_users`.`nickname`, `accounts_users`.`username`, `accounts_users`.`email`, "' . tr('System') . '") AS `name` 
                                                                                                   FROM      `accounts_users`
                                                                                                   JOIN      `accounts_users_rights` ON `accounts_users_rights`.`users_id` = `accounts_users`.`id` AND `accounts_users_rights`.`name` = "biller"                                        
                                                                                                   ' . ($this->filter_special_users ? ' LEFT JOIN `accounts_users_rights` AS `exclude` ON `exclude`.`users_id` = `accounts_users`.`id` AND `exclude`.`name` IN (' . implode(',', Arrays::quote(config()->getArray('accounts.rights.test', ['developer', 'test', 'demo']))) . ') ' : null) . '
                                                                                                   WHERE     `accounts_users`.`status` IS NULL
                                                                                                   ' . ($this->filter_special_users ? '  AND     `exclude`.`id` IS NULL' : null) . '
                                                                                                   ORDER BY  `name`')
                                                                                 ->setAutoSubmit(true)
                                                                                 ->setName($field_name)
                                                                                 ->setNotSelectedLabel(tr('All'))
                                                                                 ->setSelected(isset_get($this->source[$key]));
                                                          }))

                                          ->add(Definition::new('status')
                                                          ->setLabel(tr('Status'))
                                                          ->setSize(4)
                                                          ->setOptional(true)
                                                          ->setElement(EnumElement::select)
                                                          ->setKey(true, 'auto_submit')
                                                          ->setSource($this->states));

        // Auto apply
        $this->applyValidator(self::class);
    }


    /**
     * Renders and returns HTML string content for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Make sure this is a submittable form with GET method
        if ($this->use_form) {
            $this->useForm(true)
                 ->getFormObject()
                    ->setRequestMethod($this->request_method)
                    ->setAction(Url::newCurrent());
        }

        return parent::render();
    }


    /**
     * Returns if special users are filtered
     *
     * @return bool
     */
    public function getFilterSpecialUsers(): bool
    {
        return $this->filter_special_users;
    }


    /**
     * Sets if special users are filtered
     *
     * @param bool $filter
     *
     * @return static
     */
    public function setFilterSpecialUsers(bool $filter): static
    {
        $this->filter_special_users = $filter;
        return $this;
    }


    /**
     * Sets a default value of GET for the request method of these filter forms
     *
     * @return static
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
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): mixed
    {
        $o_definition = $this->o_definitions->get($key, exception: $exception ?? $this->exception_on_get);

        if (!$o_definition?->getRender()) {
            // NOTE: Non-rendered elements will always return null
            return null;
        }

        return parent::get($key, $default, exception: false);
    }


    /**
     * Returns value for the specified key whether the entry rendered or not
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool                        $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getForce(Stringable|string|float|int $key, mixed $default = null, bool $exception = false): mixed
    {
        return parent::get($key, $default, $exception);
    }


    /**
     * Returns whether by default this filter form requires a clean source
     *
     * @return bool|null
     */
    public function getRequireCleanSource(): ?bool
    {
        return $this->require_clean_source;
    }


    /**
     * Sets whether by default this filter form requires a clean source
     *
     * @param bool|null $require_clean_source
     *
     * @return static
     */
    public function setRequireCleanSource(?bool $require_clean_source): static
    {
        $this->require_clean_source = $require_clean_source;
        return $this;
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
                PhoDateTime::new('-6 day')->format(PhoDateTimeFormats::getDefaultDateFormatPhp()),
                PhoDateTime::new()->format(PhoDateTimeFormats::getDefaultDateFormatPhp())
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
                $date = PhoDateTime::new($date);
            }

            unset($date);
        }

        if (count($date_range_default) != 2) {
            throw new OutOfBoundsException(tr('Specified date range default value should contain 2 date ranges but contains ":value"', [
                ':value' => $date_range_default
            ]));
        }

        foreach ($date_range_default as $date) {
            if (($date instanceof PhoDateTimeInterface) or is_string($date)) {
                $date = PhoDateTime::new($date)->format('m/d/Y');
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
     * @param bool $force
     *
     * @return string|null
     */
    public function getDateRange(bool $force = false): ?string
    {
        if ($force) {
            return $this->getForce('date_range');
        }

        return $this->get('date_range');
    }


    /**
     * Returns the date range
     *
     * @note This method defaults $force to true to ensure date_range_split (by default) is always visible
     *
     * @param bool $force
     *
     * @return array|null
     */
    public function getDateRangeSplit(bool $force = true): ?array
    {
        if ($force) {
            // Only return the split date range if the date range itself is set too
            if ($this::getForce('date_range')) {
                return $this->getForce('date_range_split');
            }

        } else {
            // Only return the split date range if the date range itself is set too
            if (parent::get('date_range', exception: false)) {
                return $this->get('date_range_split');
            }
        }

        return null;
    }


    /**
     * Returns the start date, if available
     *
     * @return string|null
     */
    public function getStartDate(): ?string
    {
        static $return;

        if (!isset($return)) {
            $return = parent::get('date_range_split', false);
            $return = ($return ? $return[0] : null);
        }

        return $return;
    }


    /**
     * Returns the start date, if available
     *
     * @param string|null $timezone
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStartDateObject(?string $timezone = 'user'): ?PhoDateTimeInterface
    {
        static $return;

        if (!isset($return)) {
            $return = parent::get('date_range_split', false);
            $return = ($return ? PhoDateTime::new($return[0], $timezone)->getBeginningOfDay() : null);
        }

        return $return;
    }


    /**
     * Returns the stop date, if available
     *
     * @return string|null
     */
    public function getStopDate(): ?string
    {
        static $return;

        if (!isset($return)) {
            $return = parent::get('date_range_split', false);
            $return = ($return ? $return[1] : null);
        }

        return $return;
    }


    /**
     * Returns the stop date object, if available
     *
     * @param string|null $timezone
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStopDateObject(?string $timezone = 'user'): ?PhoDateTimeInterface
    {
        static $return;

        if (!isset($return)) {
            $return = parent::get('date_range_split', false);
            $return = ($return ? PhoDateTime::new($return[1], $timezone)->getEndOfDay() : null);
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
     * Returns the filtered user object
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface
    {
        return User::new()->loadNull($this->getUsersId());
    }


    /**
     * Returns the filtered status
     *
     * @note This method is one of the very few object::getStatus() methods that might return FALSE. The reason for that
     *        is that "not selected" would normally return NULL, but status NULL actually (mostly) means "normal". So
     *        here, FALSE means "do not filter", NULL means "filter on status NULL", and any string means "Filter on this
     *        string"
     *
     * @return string|false|null
     */
    public function getStatus(): string|false|null
    {
        $status = $this->get('status');

        if ($status === false) {
            return false;
        }

        return get_null((string) $status);
    }


    /**
     * Selects and returns the Validator object required for the current request method
     *
     * @return ValidatorInterface
     */
    protected function selectValidator(): ValidatorInterface
    {
        return match ($this->request_method) {
            EnumHttpRequestMethod::get  => GetValidator::new(),
            EnumHttpRequestMethod::post => PostValidator::new(),
            default                     => throw new OutOfBoundsException(tr('HTTP method ":method" is not supported by the FilterForm class', [
                ':method' => $this->request_method->value
            ])),
        };
    }


    /**
     * Returns the filters that will be applied
     *
     * @return Iterator|null
     */
    public function getAppliedFiltersObject(): ?IteratorInterface
    {
        if (empty($this->o_applied_filters)) {
            return null;
        }

        return $this->o_applied_filters;
    }


    /**
     * Apply the filters from the Validator
     *
     * @param string $class
     * @param bool   $require_clean_source
     *
     * @return static
     */
    protected function applyValidator(string $class, ?bool $require_clean_source = null): static
    {
        // Local objects for faster lookups
        $o_definitions        = $this->o_definitions;
        $require_clean_source = $require_clean_source ?? $this->require_clean_source;

        // Auto apply
        if ($class === static::class) {
            $o_validator = $this->selectValidator()->setDefinitionsObject($o_definitions);

            // Go over each field and let the field definition do the validation since it knows the specs
            foreach ($o_definitions as $column => $o_definition) {
//if ($column !== 'action') continue;
                $o_definition->validate($o_validator, null);

                if ($o_definition->getDefault()) {
                    $o_validator->set($o_definition->getDefault(), $column);
                }
            }

            // Validate buttons too
            if ($o_definitions->hasButtons()) {
                foreach ($o_definitions->getButtonsObject() as $button) {
                    $o_validator->select($button->getName())->isOptional()->hasValue($button->getValue());
                }
            }

            try {
                // Execute the validate method to get the results of the validation
                $this->source = $o_validator->validate($require_clean_source);

            } catch (ValidationFailedException $e) {
                // Add the DataEntry object type to the exception message
                throw $e->setMessage('(' . static::class . ') ' . $e->getMessage());
            }

            // Generate a list of all available filters so that we can tick them off one by one when we apply them later
            $this->o_applied_filters = new Iterator($o_definitions->getKeyIndices());
        }

        return $this;
    }


    /**
     * Automatically apply current filters to the query builder
     *
     * @param QueryBuilderInterface $o_builder
     *
     * @return static
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $o_builder): static
    {
        // Local objects for faster lookups
        $o_definitions     = $this->o_definitions;
        $o_applied_filters = $this->o_applied_filters;

        if ($o_applied_filters->keyExists('status') and $o_definitions->isRendered('status', false)) {
            // Is the status filter rendered and available?
            if ($this->getStatus() !== false) {
                // Is the status filter not set to "All"?
                if ($this->getStatus() !== 'all') {
                    $o_builder->addWhere(
                        SqlQueries::is('`' . $o_builder->getFrom() . '`.`status`', $this->getStatus(), ':from_status', $o_builder->getExecuteByReference())
                    );
                }
            }
        }

        if ($o_applied_filters->keyExists('date_range') and $o_definitions->isRendered('date_range', false)) {
            if ($this->getStartDateObject()) {
                $o_builder->addWhere(
                    '`' . $o_builder->getFrom() . '`.`created_on` >= :start', [':start' => $this->getStartDateObject()->format(EnumDateFormat::mysql_datetime)]
                );
            }

            if ($this->getStopDateObject()) {
                $o_builder->addWhere(
                    '`' . $o_builder->getFrom() . '`.`created_on` <= :stop', [':stop' => $this->getStopDateObject()->format(EnumDateFormat::mysql_datetime)]
                );
            }
        }

        if ($o_applied_filters->keyExists('users_id') and $o_definitions->isRendered('users_id', false)) {
            if ($this->getUsersId()) {
                $o_builder->addWhere(
                    '`' . $o_builder->getFrom() . '`.`created_by` = :created_by', [':created_by' => $this->getUsersId()]
                );
            }
        }

        $o_applied_filters->removeKeys([
            'status',
            'date_range',
            'users_id',
        ]);

        return $this;
    }
}
