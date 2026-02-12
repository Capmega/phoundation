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

use Endroid\QrCode\Exception\ValidationException;
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
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\PhoDateTimeFormats;
use Phoundation\Date\PhoDateTime;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Forms\Interfaces\FilterFormInterface;
use Phoundation\Web\Html\Components\Input\InputDateRange;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;
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
     * Tracks whether the source data has been validated or not
     *
     * @var bool $is_validated
     */
    protected bool $is_validated = false;

    /**
     * Tracks if data_view value "Percentages" should be available
     *
     * @var bool $data_view_percentage
     */
    protected bool $data_view_percentage = true;

    /**
     * Tracks the column to group on
     *
     * @var ?string $grouping_column
     */
    protected ?string $grouping_column = null;


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
                'active'  => tr('Active'),
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
                                                          ->setOptional(true, 'active')
                                                          ->setElement(EnumElement::select)
                                                          ->setKey(true, 'auto_submit')
                                                          ->setSource($this->states))

                                          ->add(Definition::new('data_view')
                                                         ->setLabel(tr('Data view'))
                                                         ->setSize(3)
                                                         ->setOptional(true, 'minutes')
                                                         ->setRender(false)
                                                         ->setInputType(EnumInputType::text)
                                                         ->setOutput(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
                                                             if ($this->data_view_percentage) {
                                                                 $source = [
                                                                     'minutes' => tr('Minutes'),
                                                                     'human'   => tr('Human readable'),
                                                                     'percent' => tr('Percentages'),
                                                                 ];

                                                             } else {
                                                                 $source = [
                                                                     'minutes' => tr('Minutes'),
                                                                     'human'   => tr('Human readable'),
                                                                 ];
                                                             }

                                                             return InputSelect::new()
                                                                               ->setSource($source)
                                                                               ->setName($field_name)
                                                                               ->setSelected($this->source[$key])
                                                                               ->setAutoSubmit(true);
                                                         }))

                                         ->add(Definition::new('grouping')
                                                         ->setLabel(tr('Grouping'))
                                                         ->setSize(3)
                                                         ->setOptional(true, 'days')
                                                         ->setRender(false)
                                                         ->setInputType(EnumInputType::text)
                                                         ->setOutput(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
                                                             return InputSelect::new()
                                                                               ->setSource([
                                                                                   'none'      => tr('None'),
                                                                                   'days'      => tr('Days'),
                                                                                   'weeks'     => tr('Weeks'),
                                                                                   'periods'   => tr('Payment periods'),
                                                                                   'months'    => tr('Months'),
                                                                                   'quarters'  => tr('Quarters'),
                                                                                   'semesters' => tr('Semesters'),
                                                                                   'years'     => tr('Years'),
                                                                               ])
                                                                               ->setName($field_name)
                                                                               ->setSelected($this->source[$key])
                                                                               ->setAutoSubmit(true);
                                                             }));
   }


    /**
     * Throws a ValidationException if the data has already been validated
     *
     * @param string $action
     *
     * @return $this
     * @throws ValidationException
     */
    protected function checkNotValidated(string $action): static
    {
        if ($this->isValidated()) {
            throw new ValidationException(ts('Cannot execute action ":action" on FilterForm object ":class", the source data has already been validated', [
                ':action' => $action,
                ':class' => get_class($this),
            ]));
        }

        return $this;
    }


    /**
     * Throws a ValidationException if the data has not yet been validated
     *
     * @param string $action
     *
     * @return $this
     * @throws ValidationException
     */
    protected function checkValidated(string $action): static
    {
        if ($this->isValidated()) {
            return $this;
        }

        throw new ValidationException(ts('Cannot execute action ":action" on FilterForm object ":class", the source data has not yet been validated', [
            ':action' => $action,
            ':class' => get_class($this),
        ]));
    }


    /**
     * Returns if data_view value "Percentages" should be available
     *
     * @return bool
     */
    public function getDataViewPercentage(): bool
    {
        return $this->data_view_percentage;
    }


    /**
     * Sets if data_view value "Percentages" should be available
     *
     * @param bool $view If true, will display the "Percentages" option in the data_view selector
     *
     * @return \Plugins\Medinet\Timesheets\FilterForm
     */
    public function setDataViewPercentage(bool $view): static
    {
        $this->data_view_percentage = $view;
        return $this;
    }


    /**
     * Returns the value for the data view
     *
     * @return string|null
     */
    public function getDataView(): ?string
    {
        return $this->get('data_view');
    }


    /**
     * Returns true if the current data_view value is the same as the specified data_view value
     *
     * @param string|null $view
     *
     * @return bool
     */
    public function hasDataView(?string $view): bool
    {
        return $this->getDataView() === $view;
    }


    /**
     * Returns true if the current data_view value is "minute"
     *
     * @return bool
     */
    public function hasDataViewMinute(): bool
    {
        return $this->hasDataView('minute');
    }


    /**
     * Returns true if the current data_view value is "human"
     *
     * @return bool
     */
    public function hasDataViewHumanReadable(): bool
    {
        return $this->hasDataView('human');
    }


    /**
     * Returns true if the current data_view value is "percent"
     *
     * @return bool
     */
    public function hasDataViewPercent(): bool
    {
        return $this->hasDataView('percent');
    }


    /**
     * Returns true if the grouping filter has the specified value
     *
     * @param string $grouping
     *
     * @return bool
     */
    public function hasGrouping(string $grouping): bool
    {
        return $this->getGrouping() === $grouping;
    }


    /**
     * Returns the value for the grouping
     *
     * @return string|null
     */
    public function getGrouping(): ?string
    {
        return $this->get('grouping');
    }


    /**
     * Sets the value for the grouping
     *
     * @param string|null $grouping
     *
     * @return FilterForm
     */
    public function setGrouping(?string $grouping): static
    {
        return $this->get($grouping, 'grouping');
    }


    /**
     * Returns the value for the grouping_column
     *
     * @return string|null
     */
    public function getGroupingColumn(): ?string
    {
        return $this->get('grouping_column');
    }


    /**
     * Sets the value for the grouping_column
     *
     * @param string|null $column
     *
     * @return FilterForm
     */
    public function setGroupingColumn(?string $column): static
    {
        return $this->set($column, 'grouping_column');
    }


    /**
     * Sets all render definitions in one go
     *
     * @param array $definitions
     *
     * @return static
     * @throws ValidationException | OutOfBoundsException
     */
    public function setRenderDefinitions(array $definitions): static
    {
        $this->checkNotValidated('FilterForm::setRenderDefinitions()');

        $_definitions = $this->getDefinitionsObject();

        foreach ($definitions as $key => $value) {
            $_definitions->get($key)->setRender($value);
        }

        return $this;
    }


    /**
     * Sets all disabled definitions in one go
     *
     * @param array $definitions
     *
     * @return static
     * @throws ValidationException | OutOfBoundsException
     */
    public function setDisabledDefinitions(array $definitions): static
    {
        $this->checkNotValidated('FilterForm::setDisabledDefinitions()');

        $_definitions = $this->getDefinitionsObject();

        foreach ($definitions as $key => $value) {
            if (is_bool($value)) {
                $_definitions->get($key)->setDisplay($value);
                continue;
            }

            throw OutOfBoundsException::new(ts('Cannot set disabled definition ":value" for ":class" class column ":column", the value must be an boolean', [
                ':value'  => $value,
                ':class'  => static::class,
                ':column' => $key,
            ]));
        }

        return $this;
    }


    /**
     * Sets all display definitions in one go
     *
     * @param array $definitions
     *
     * @return static
     * @throws ValidationException | OutOfBoundsException
     */
    public function setDisplayDefinitions(array $definitions): static
    {
        $this->checkNotValidated('FilterForm::setDisplayDefinitions()');

        $_definitions = $this->getDefinitionsObject();

        foreach ($definitions as $key => $value) {
            if (is_bool($value)) {
                $_definitions->get($key)->setDisplay($value);
                continue;
            }

            throw OutOfBoundsException::new(ts('Cannot set display definition ":value" for ":class" class column ":column", the value must be an boolean', [
                ':value'  => $value,
                ':class'  => static::class,
                ':column' => $key,
            ]));
        }

        return $this;
    }


    /**
     * Sets all readonly definitions in one go
     *
     * @param array $definitions
     *
     * @return static
     * @throws ValidationException | OutOfBoundsException
     */
    public function setReadonlyDefinitions(array $definitions): static
    {
        $this->checkNotValidated('FilterForm::setReadonlyDefinitions()');

        $_definitions = $this->getDefinitionsObject();

        foreach ($definitions as $key => $value) {
            if (is_bool($value)) {
                $_definitions->get($key)->setReadonly($value);
                continue;
            }

            throw OutOfBoundsException::new(ts('Cannot set readonly definition ":value" for ":class" class column ":column", the value must be an boolean', [
                ':value'  => $value,
                ':class'  => static::class,
                ':column' => $key,
            ]));
        }

        return $this;
    }


    /**
     * Sets all size definitions in one go
     *
     * @param array $definitions
     *
     * @return static
     * @throws ValidationException | OutOfBoundsException
     */
    public function setSizeDefinitions(array $definitions): static
    {
        $this->checkNotValidated('FilterForm::setSizeDefinitions()');

        $_definitions = $this->getDefinitionsObject();

        foreach ($definitions as $key => $value) {
            if (is_numeric_integer($value) and ($value >= 1) and ($value <= 12)) {
                $_definitions->get($key)->setSize($value);
                continue;
            }

            throw OutOfBoundsException::new(ts('Cannot set size definition ":value" for ":class" class column ":column", the value must be an integer value between 1 and 12', [
                ':value'  => $value,
                ':class'  => static::class,
                ':column' => $key,
            ]));
        }

        return $this;
    }


    /**
     * Ensures that the start and stop dates are both on correct dates for the currently selected period
     *
     * This method ensures that the current start-date is always on the right start (first day of the period) and stop date (last day of the period) for the
     * selected period. When, for example, the selected period is weeks, the start day should always be a sunday, and the stop day should always be a saturday.
     * When the selected period is a payment period, the date range should always start on the 1st or the 16th, and end on the 15th, or the 28th, 29th, 30th, or
     * 31st, depending on the month. A year period would require to start on January the 1st of the year and end on December 31st.
     *
     * When $single_period is true, it will also ensure that start and stop dates are such, that the selection covers exactly a single period.
     *
     * This method will cause a redirect to the same page with a date range correctly on a period
     *
     * @param bool $single_period [true] If true, only allows a single period. If false, can span multiple periods over multiple months
     *
     * @return static
     */
    public function ensureGroupedPeriodDateRange(bool $single_period = true): static
    {
        $this->checkValidated('FilterForm::ensureGroupedPeriodDateRange()');

        switch ($this->getGrouping()) {
            case 'none':
                // no break

            case 'days':
                // These groupings do not require special dates
                break;

            case 'weeks':
                return $this->ensureWeeksDateRange($single_period);

            case 'periods':
                return $this->ensurePaymentPeriodsDateRange($single_period);

            case 'months':
                return $this->ensureMonthsDateRange($single_period);

            case 'quarters':
                return $this->ensureQuartersDateRange($single_period);

            case 'semesters':
                return $this->ensureSemestersDateRange($single_period);

            case 'years':
                return $this->ensureYearsDateRange($single_period);

            default:
                throw new OutOfBoundsException(ts('Unknown grouping ":grouping" specified', [
                    'grouping' => $this->getGrouping(),
                ]));
        }

        return $this;
    }


    /**
     * Ensures that the start and stop dates are both on period days
     *
     * This method ensures that the current start-date is always on the 1st or 16th and the stop-date is always on the 15th, or whatever the last day of the
     * month is.
     *
     * When $single_period is true, it will also ensure that only a single period is selected
     *
     * This method will cause a redirect to the same page with a date range correctly on a period
     *
     * @param bool $single_period [true] If true, only allows a single period. If false, can span multiple periods over multiple months
     *
     * @return static
     */
    protected function ensureYearsDateRange(bool $single_period = true): static
    {
        // The stop date that we will use in case we have to redirect
        $_stop = null;

        // Stop must be after start
        if ($this->getStopDateObject() > $this->getStartDateObject()) {
            // Either we have a multi period or start/stop month and year must match
            if (!$single_period or ($this->getStartDateObject()->format('y-W') === $this->getStopDateObject()->format('y-W'))) {
                // The start date must be on one of 01-01, 07-01
                if ($this->getStartDateObject()->isYearBegin()) {
                    // The stop date must be on one of 06-30, 12-31
                    if ($this->getStopDateObject()->isYearEnd()) {
                        return $this;
                    }
                }

            } else {
                // We have multiple months, redirect end month to start month
                $_stop = $this->getStartDateObject();
            }
        }

        // Nope, we are not on a week date range. Redirect to fix.
        $_stop = $_stop ?? $this->getStopDateObject();

        // Adjust start date, expand it to the current (if current is period start day) or previous period start day
        if (!$this->getStartDateObject()->isYearBegin()) {
            $this->getStartDateObject()->makePreviousYearBegin();
        }

        // Adjust stop date, expand it to the current (if current is period stop day) or next period stop day
        if (!$this->getStopDateObject()->isYearEnd()) {
            $this->getStopDateObject()->makeNextYearEnd();
        }

        // Redirect
        Response::redirect(Url::newCurrent()
                              ->removeQueryKeys('date')
                              ->addQueries('date_range=' . $this->getStartDateObject()->format('Y/m/d')
                                           . ' - '
                                           . $_stop->format('Y/m/d')), 301);
    }


    /**
     * Ensures that the start and stop dates are both on period days
     *
     * This method ensures that the current start-date is always on the 1st or 16th and the stop-date is always on the 15th, or whatever the last day of the
     * month is.
     *
     * When $single_period is true, it will also ensure that only a single period is selected
     *
     * This method will cause a redirect to the same page with a date range correctly on a period
     *
     * @param bool $single_period [true] If true, only allows a single period. If false, can span multiple periods over multiple months
     *
     * @return static
     */
    protected function ensureSemestersDateRange(bool $single_period = true): static
    {
        // The stop date that we will use in case we have to redirect
        $_stop = null;

        // Stop must be after start
        if ($this->getStopDateObject() > $this->getStartDateObject()) {
            // Either we have a multi period or start/stop month and year must match
            if (!$single_period or ($this->getStartDateObject()->format('y-W') === $this->getStopDateObject()->format('y-W'))) {
                // The start date must be on one of 01-01, 07-01
                if ($this->getStartDateObject()->isSemesterBegin()) {
                    // The stop date must be on one of 06-30, 12-31
                    if ($this->getStopDateObject()->isSemesterEnd()) {
                        return $this;
                    }
                }

            } else {
                // We have multiple months, redirect end month to start month
                $_stop = $this->getStartDateObject();
            }
        }

        // Nope, we are not on a week date range. Redirect to fix.
        $_stop = $_stop ?? $this->getStopDateObject();

        // Adjust start date, expand it to the current (if current is period start day) or previous period start day
        if (!$this->getStartDateObject()->isSemesterBegin()) {
            $this->getStartDateObject()->makePreviousSemesterBegin();
        }

        // Adjust stop date, expand it to the current (if current is period stop day) or next period stop day
        if (!$this->getStopDateObject()->isSemesterEnd()) {
            $this->getStopDateObject()->makeNextSemesterEnd();
        }

        // Redirect
        Response::redirect(Url::newCurrent()
                              ->removeQueryKeys('date')
                              ->addQueries('date_range=' . $this->getStartDateObject()->format('Y/m/d')
                                           . ' - '
                                           . $_stop->format('Y/m/d')), 301);
    }


    /**
     * Ensures that the start and stop dates are both on quarter begin / end days
     *
     * This method ensures that the current start-date is always on one of 01-01, 04-01, 07-01, 10-01, and the end-date is always on one of 01-01, 04-01, 07-01,
     * 10-01
     *
     * When $single_period is true, it will also ensure that only a single period is selected
     *
     * This method will cause a redirect to the same page with a date range correctly on a period
     *
     * @param bool $single_period [true] If true, only allows a single period. If false, can span multiple periods over multiple months
     *
     * @return static
     */
    protected function ensureQuartersDateRange(bool $single_period = true): static
    {
        // The stop date that we will use in case we have to redirect
        $_stop = null;

        // Stop must be after start
        if ($this->getStopDateObject() > $this->getStartDateObject()) {
            // Either we have a multi period or start/stop month and year must match
            if (!$single_period or ($this->getStartDateObject()->format('y-W') === $this->getStopDateObject()->format('y-W'))) {
                // The start date must be on one of 01-01, 04-01, 07-01, 10-01
                if ($this->getStartDateObject()->isQuarterBegin()) {
                    // The stop date must be on one of 01-01, 04-01, 07-01, 10-01
                    if ($this->getStopDateObject()->isQuarterEnd()) {
                        return $this;
                    }
                }

            } else {
                // We have multiple months, redirect end month to start month
                $_stop = $this->getStartDateObject();
            }
        }

        // Nope, we are not on a week date range. Redirect to fix.
        $_stop = $_stop ?? $this->getStopDateObject();

        // Adjust start date, expand it to the current (if current is period start day) or previous period start day
        if (!$this->getStartDateObject()->isMonthBegin()) {
            $this->getStartDateObject()->makePreviousQuarterBegin();
        }

        // Adjust stop date, expand it to the current (if current is period stop day) or next period stop day
        if (!$this->getStopDateObject()->isMonthEnd()) {
            $this->getStopDateObject()->makeNextQuarterEnd();
        }

        // Redirect
        Response::redirect(Url::newCurrent()
                              ->removeQueryKeys('date')
                              ->addQueries('date_range=' . $this->getStartDateObject()->format('Y/m/d')
                                           . ' - '
                                           . $_stop->format('Y/m/d')), 301);
    }


    /**
     * Ensures that the start and stop dates are both on period days
     *
     * This method ensures that the current start-date is always on the 1st or 16th and the stop-date is always on the 15th, or whatever the last day of the
     * month is.
     *
     * When $single_period is true, it will also ensure that only a single period is selected
     *
     * This method will cause a redirect to the same page with a date range correctly on a period
     *
     * @param bool $single_period [true] If true, only allows a single period. If false, can span multiple periods over multiple months
     *
     * @return static
     */
    protected function ensureMonthsDateRange(bool $single_period = true): static
    {
        // The stop date that we will use in case we have to redirect
        $_stop = null;

        // Stop must be after start
        if ($this->getStopDateObject() > $this->getStartDateObject()) {
            // Either we have a multi period or start/stop month and year must match
            if (!$single_period or ($this->getStartDateObject()->format('y-W') === $this->getStopDateObject()->format('y-W'))) {
                // The start date must be the first day of the month
                if ($this->getStartDateObject()->isMonthBegin()) {
                    // The stop date must be either the 28th, 29th, 30th, 31st, depending on the month
                    if ($this->getStopDateObject()->isMonthEnd()) {
                        return $this;
                    }
                }

            } else {
                // We have multiple months, redirect end month to start month
                $_stop = $this->getStartDateObject();
            }
        }

        // Nope, we are not on a week date range. Redirect to fix.
        $_stop = $_stop ?? $this->getStopDateObject();

        // Adjust start date, expand it to the current (if current is period start day) or previous period start day
        if (!$this->getStartDateObject()->isMonthBegin()) {
            $this->getStartDateObject()->setDay(1);
        }

        // Adjust stop date, expand it to the current (if current is period stop day) or next period stop day
        if (!$this->getStopDateObject()->isMonthEnd()) {
            $this->getStopDateObject()->setDay($this->getStopDateObject()->getDaysInMonth());
        }

        // Redirect
        Response::redirect(Url::newCurrent()
                              ->removeQueryKeys('date')
                              ->addQueries('date_range=' . $this->getStartDateObject()->format('Y/m/d')
                                           . ' - '
                                           . $_stop->format('Y/m/d')), 301);
    }


    /**
     * Ensures that the start and stop dates are both on week-begin and end days
     *
     * This method ensures that the current start-date is always on the (system or user) configured start of the week, while the stop date is on the (system or
     * user) configured end of the week
     *
     * When $single_period is true, it will also ensure that only a single period is selected
     *
     * This method will cause a redirect to the same page with a date range correctly on a period
     *
     * @param bool $single_period [true] If true, only allows a single period. If false, can span multiple periods over multiple months
     *
     * @return static
     */
    protected function ensureWeeksDateRange(bool $single_period = true): static
    {
        // The stop date that we will use in case we have to redirect
        $_stop = null;

        // Stop must be after start
        if ($this->getStopDateObject() > $this->getStartDateObject()) {
            // Either we have a multi period or start/stop month and year must match
            if (!$single_period or ($this->getStartDateObject()->format('y-W') === $this->getStopDateObject()->format('y-W'))) {
                // The start date must be either Sunday or Monday, depending on system / user configuration
                if ($this->getStartDateObject()->isWeekBegin()) {
                    // The stop date must be either Saturday or Sunday, depending on system / user configuration
                    if ($this->getStopDateObject()->isWeekEnd()) {
                        return $this;
                    }
                }

            } else {
                // We have multiple months, redirect end month to start month
                $_stop = $this->getStartDateObject();
            }
        }

        // Nope, we are not on a week date range. Redirect to fix.
        $_stop = $_stop ?? $this->getStopDateObject();

        // Adjust start date, expand it to the current (if current is period start day) or previous period start day
        if (!$this->getStartDateObject()->isWeekBegin()) {
            $this->getStartDateObject()->modify('last ' . $this->getStartDateObject()->getWeekBeginDayName());
        }

        // Adjust stop date, expand it to the current (if current is period stop day) or next period stop day
        if (!$this->getStopDateObject()->isWeekEnd()) {
            $this->getStopDateObject()->modify('next ' . $this->getStopDateObject()->getWeekEndDayName());
        }

        // Redirect
        Response::redirect(Url::newCurrent()
                              ->removeQueryKeys('date')
                              ->addQueries('date_range=' . $this->getStartDateObject()->format('Y/m/d')
                                           . ' - '
                                           . $_stop->format('Y/m/d')), 301);
    }


    /**
     * Ensures that the start and stop dates are both on period days
     *
     * This method ensures that the current start-date is always on the 1st or 16th and the stop-date is always on the 15th, or whatever the last day of the
     * month is.
     *
     * When $single_period is true, it will also ensure that only a single period is selected
     *
     * This method will cause a redirect to the same page with a date range correctly on a period
     *
     * @param bool $single_period [true] If true, only allows a single period. If false, can span multiple periods over multiple months
     *
     * @return static
     */
    protected function ensurePaymentPeriodsDateRange(bool $single_period = true): static
    {
        // The stop date that we will use in case we have to redirect
        $_stop = null;

        // Stop must be after start
        if ($this->getStopDateObject() > $this->getStartDateObject()) {
            // Either we have a multi period or start/stop month and year must match
            if (!$single_period or ($this->getStartDateObject()->format('y-m') === $this->getStopDateObject()->format('y-m'))) {
                // Start date must be either the 1st or 16th
                if ($this->getStartDateObject()->isPeriodBegin()) {
                    // Stop date must be either the 15th, 28th, 29th, 30th, 31st, depending on the month
                    if ($this->getStopDateObject()->isPeriodEnd()) {
                        return $this;
                    }
                }

            } else {
                // We have multiple months, redirect end month to start month
                $_stop = $this->getStartDateObject();
            }
        }

        // Nope, we are not on a period date range. Redirect to fix.
        $_stop = $_stop ?? $this->getStopDateObject();

        // Adjust the start date, expand it to the current (if current is period start day) or previous period start day
        if ($this->getStartDateObject()->getDay() > 16) {
            $this->getStartDateObject()->setDay(16);

        } else {
            $this->getStartDateObject()->setDay(1);
        }

        // Adjust the stop date, expand it to the current (if current is period stop day) or next period stop day
        if ($_stop->getDay() > 16) {
            $_stop->setDay($_stop->getLastDayOfMonth()->getDay());

        } else {
            $_stop->setDay(15);
        }

        // Redirect
        Response::redirect(Url::newCurrent()
                              ->removeQueryKeys('date')
                              ->addQueries('date_range=' . $this->getStartDateObject()->format('Y/m/d')
                                           . ' - '
                                           . $_stop->format('Y/m/d')), 301);
    }


    /**
     * Renders and returns HTML string content for this object
     *
     * @return string|null
     * @throws ValidationException
     */
    public function render(): ?string
    {
        // Auto apply validation before rendering
        $this->checkValidated('FilterForm::render()')->validate();

        // Make sure this is a submittable form with GET method
        if ($this->use_form) {
            $this->useForm(true)
                 ->getFormObject()->setRequestMethod($this->request_method)
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
     * Returns if the source data is validated or not
     *
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->is_validated;
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
     * @param string|null $selector
     *
     * @return static
     */
    public function setDateRangeSelector(?string $selector): static
    {
        $this->date_range_selector = $selector;
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
     * Validates the source data of this filter form
     *
     * @param bool $require_clean_source [null]  If true, will require that the source contains no values beyond the ones defined for this FilterForm object
     * @param bool $force                [false] If true, will always validate, even if the data has already been validated
     *
     * @return static
     * @todo Check if the $class variable still really is necessary now that the applied values are tracked
     */
    public function validate(?bool $require_clean_source = null, bool $force = false): static
    {
        if (!$this->isValidated() or $force) {
            // Local objects for faster lookups
            $o_definitions        = $this->o_definitions;
            $require_clean_source = $require_clean_source ?? $this->require_clean_source;
            $o_validator          = $this->selectValidator()->setDefinitionsObject($o_definitions);

            // Go over each field and let the field definition do the validation since it knows the specs
            foreach ($o_definitions as $column => $o_definition) {
                $o_definition->validate($o_validator, null);

// TODO The following code will ALWAYS overwrite valid values with the default value, if it exists. That makes little sense. Should this code be here at all? The validator should take care of that anyways...
//                if ($o_definition->getDefault()) {
//                    $o_validator->set($o_definition->getDefault(), $column);
//                }
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

        $this->is_validated = true;
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
                switch ($this->getStatus()) {
                    case 'all':
                        // Filter nothing
                        break;

                    case 'active':
                        // Filter for status NULL
                        $o_builder->addWhere(
                            QueryBuilder::is('`' . $o_builder->getFrom() . '`.`status`', null, ':from_status', $o_builder->getExecuteByReference())
                        );
                        break;

                    default:
                        // Filter for the specified status
                        $o_builder->addWhere(
                            QueryBuilder::is('`' . $o_builder->getFrom() . '`.`status`', $this->getStatus(), ':from_status', $o_builder->getExecuteByReference())
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
