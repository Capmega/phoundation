<?php

/**
 * Class Authentication
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Enums\EnumAuthenticationAction;
use Phoundation\Accounts\Users\Interfaces\AuthenticationInterface;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Exception\CoreReadonlyException;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryAlreadySavedException;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCity;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCountry;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCreatedBy;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryIpAddress;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryLongLat;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryMethod;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPlatform;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryState;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryTimezone;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUserAgent;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Geo\GeoIp\Exception\GeoIpException;
use Phoundation\Geo\GeoIp\GeoIp;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;


class Authentication extends DataEntry implements AuthenticationInterface
{
    use TraitDataEntryCity;
    use TraitDataEntryCountry;
    use TraitDataEntryIpAddress;
    use TraitDataEntryLongLat;
    use TraitDataEntryMethod;
    use TraitDataEntryPlatform;
    use TraitDataEntryState;
    use TraitDataEntryTimezone;
    use TraitDataEntryUserAgent;
    use TraitDataEntryCreatedBy;


    /**
     * Authentication class constructor
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = false)
    {
        $this->initializeVirtualConfiguration([
            'timezones' => ['id'],
            'countries' => ['id'],
            'states'    => ['id'],
            'cities'    => ['id'],
        ]);

        if (!isset($this->meta_columns)) {
            // By default, the Authentication object has created_by NOT meta so that it can set it manually
            $this->meta_columns = [
                'id',
                'created_on',
                'meta_id',
                'status',
                'meta_state',
            ];
        }

        parent::__construct($identifier);

        if ($this->isNew()) {
            try {
                $city = GeoIp::detect(Session::getIpAddress())->getCity();

            } catch (GeoIpException $e) {
                Log::warning(ts('Geo IP lookup failed for address ":ip", no GEO IP data will be available', [
                    ':ip' => Session::getIpAddress()
                ]));

                Log::exception($e);
                $city = null;
            }

            // Initialize variables
            // TODO Add support for Geo library cities, states, countries, timezones
            $this->setPlatform(match (Request::getRequestType()) {
                    EnumRequestTypes::cli  => 'cli',
                    EnumRequestTypes::api  => 'api',
                    EnumRequestTypes::ajax => 'ajax',
                    EnumRequestTypes::html => 'html',
                    default                => 'other'
                 })
                 ->setUserAgent(Request::getUserAgent())
                 ->setIpAddress(Session::getIpAddress())
                 ->setLongitude($city?->location?->longitude)
                 ->setLatitude($city?->location?->latitude);
        }
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'accounts_authentications';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Account authentication');
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
     * Returns the account for this authentication
     *
     * @return array|string|null
     */
    public function getAccount(): array|string|null
    {
        return Json::encode($this->getTypesafe('array', 'account'));
    }


    /**
     * Sets the account for this authentication
     *
     * @param array|string|null $account
     *
     * @return static
     */
    public function setAccount(array|string|null $account): static
    {
        if (is_string($account)) {
            $account = Json::decode($account);
        }

        return $this->set($account, 'account');
    }


    /**
     * Returns the captcha_required for this authentication
     *
     * @return bool|null
     */
    public function getCaptchaRequired(): bool|null
    {
        return (bool) $this->getTypesafe('bool', 'captcha_required');
    }


    /**
     * Sets the captcha_required for this authentication
     *
     * @param int|bool|null $user_agent
     *
     * @return static
     */
    public function setCaptchaRequired(int|bool|null $user_agent): static
    {
        return $this->set((bool) $user_agent, 'captcha_required');
    }


    /**
     * Returns the failed_reason for this authentication
     *
     * @return string|null
     */
    public function getFailedReason(): ?string
    {
        return $this->getTypesafe('string', 'failed_reason');
    }


    /**
     * Sets the failed_reason for this authentication
     *
     * @param string|null $user_agent
     *
     * @return static
     */
    public function setFailedReason(?string $user_agent): static
    {
        return $this->set($user_agent, 'failed_reason');
    }


    /**
     * Returns the action for this object
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->getTypesafe('string', 'action');
    }


    /**
     * Sets the action for this object
     *
     * @param EnumAuthenticationAction|string|null $action
     *
     * @return static
     */
    public function setAction(EnumAuthenticationAction|string|null $action): static
    {
        if ($action instanceof EnumAuthenticationAction) {
            $action = $action->value;

        } elseif ($action) {
            $action = EnumAuthenticationAction::from($action)->value;
        }

        return $this->set($action, 'action');
    }


    /**
     * Will save the data from this data entry to the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        try {
            if (!$this->isNew()) {
                throw new DataEntryAlreadySavedException(tr('Cannot save Authentication DataEntry, has already been saved before'));
            }

            return parent::save($force, $skip_validation, $comments);

        } catch (CoreReadonlyException) {
            // Core is readonly we can't write to the database!
            Log::warning(ts('Cannot save Authentication object for Session ":session" for user ":user" from IP ":ip", core is readonly', [
                ':session' => Session::getId(),
                ':user'    => Session::getUserObject()->getLogId(),
                ':ip'      => Session::getIpAddress(),
            ]));

            return $this;
        }
    }


    /**
     * Returns an array with all possible actions on which can be authenticated
     *
     * The key will contain the action identifier, the value will contain the user-friendly label
     *
     * @return array
     */
    public static function getActions(): array
    {
        static $actions;

        if (empty($actions)) {
            $actions = [
                'authentication'     => tr('Authentication'),
                'signin'             => tr('Sign in'),
                'signout'            => tr('Sign out'),
                'startimpersonation' => tr('Start impersonation'),
                'stopimpersonation'  => tr('Stop impersonation'),
                'other'              => tr('Other')
            ];
        }

        return $actions;
    }


    /**
     * Returns an array with all possible methods on which can be authenticated
     *
     * The key will contain the method identifier, the value will contain the user-friendly label
     *
     * @return array
     */
    public static function getMethods(): array
    {
        static $methods;

        if (empty($methods)) {
            $methods = [
                'password' => tr('Password'),
                'magic'    => tr('Magic'),
                'sso'      => tr('SSO'),
                'google'   => tr('Google'),
                'facebook' => tr('Facebook'),
                'other'    => tr('Other')
            ];
        }

        return $methods;
    }


    /**
     * Returns an array with all possible platforms on which can be authenticated
     *
     * The key will contain the platform identifier, the value will contain the user-friendly label
     *
     * @return array
     */
    public static function getPlatforms(): array
    {
        static $platforms;

        if (empty($platforms)) {
            $platforms = [
                'cli'   => tr('CLI'),
                'api'   => tr('API'),
                'ajax'  => tr('AJAX'),
                'html'  => tr('HTML'),
                'other' => tr('Other')
            ];
        }

        return $platforms;
    }


    /**
     * Returns all possible actions for filtering
     *
     * @return array
     */
    public static function getFilterActions(): array
    {
        static $actions;

        if (empty($actions)) {
            $actions = array_replace([
                '' => tr('All'),
            ], static::getActions());
        }

        return $actions;
    }


    /**
     * Returns a human-readable status for the specified status value
     *
     * @param string|null $status
     *
     * @return string
     */
    public static function getHumanReadableStatus(?string $status): string
    {
        static $statuses;

        if (empty($statuses)) {
            $statuses = static::getStatuses();
        }

        if (array_key_exists($status, $statuses)) {
            return $statuses[$status];
        }

        return tr('Unknown');
    }


    /**
     * Returns all possible statuses
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        static $statuses;

        if (empty($statuses)) {
            $statuses = [
                null                 => tr('Ok'),
                'deleted'            => tr('Deleted'),
                'no-hook-data'       => tr('No hook data'),
                'bad-hook-data'      => tr('Invalid hook data'),
                'user-not-exist'     => tr('User does not exist'),
                'password-incorrect' => tr('Incorrect password'),
            ];
        }

        return $statuses;
    }


    /**
     * Returns all possible statuses
     *
     * @return array
     */
    public static function getFilterStatuses(): array
    {
        static $statuses;

        if (empty($statuses)) {
            $statuses = array_replace([
                'all' => tr('All'),
            ], static::getStatuses());
        }

        return $statuses;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        // Ensure status will be limited to the defined possible states
        $o_definitions->removeKeys('meta_divider')
                      ->get('status')->setSource(static::getStatuses());

        $o_definitions->add(DefinitionFactory::newCreatedBy()
                                             ->setOptional(true))

                      ->add(DefinitionFactory::newDivider('meta_divider'))

                      ->add(DefinitionFactory::newData('account')
                                             ->setLabel(tr('Used user account'))
                                             ->setOptional(true)
                                             ->setDisabled(true)
                                             ->setMaxLength(128)
                                             ->setSize(3)
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->sanitizeDecodeJson()->hasField('email')->forEachField()->isEmail();
                                             }))

                      ->add(DefinitionFactory::newNumber('ip_address_binary')
                                             ->setRender(false))

                      ->add(DefinitionFactory::newNumber('net_len')
                                             ->setDefault(0)
                                             ->setRender(false))

                      ->add(DefinitionFactory::newIpAddress('ip_address')
                                             ->setLabel(tr('IP address'))
                                             ->setDisabled(true)
                                             ->setOptional(true)
                                             ->setSize(3))

                      ->add(Definition::new('user_agent')
                                      ->setLabel(tr('User agent'))
                                      ->setDisabled(true)
                                      ->setOptional(true)
                                      ->setMaxLength(2040)
                                      ->setSize(6))

                      ->add(Definition::new('action')
                                      ->setLabel(tr('Action'))
                                      ->setDisabled(true)
                                      ->setOptional(true)
                                      ->setSize(4)
                                      ->setSource(static::getActions()))

                      ->add(Definition::new('platform')
                                      ->setLabel(tr('Platform'))
                                      ->setDisabled(true)
                                      ->setOptional(true)
                                      ->setSize(4)
                                      ->setSource(static::getPlatforms()))

                      ->add(Definition::new('method')
                                      ->setLabel(tr('Method'))
                                      ->setDisabled(true)
                                      ->setOptional(true)
                                      ->setSize(4)
                                      ->setSource(static::getMethods()))

                      ->add(DefinitionFactory::newDatabaseId('timezones_id')
                                             ->setLabel(tr('Timezone'))
                                             ->setDisabled(true)
                                             ->setOptional(true)
                                             ->setSize(3))

                      ->add(DefinitionFactory::newDatabaseId('countries_id')
                                             ->setLabel(tr('Country'))
                                             ->setDisabled(true)
                                             ->setOptional(true)
                                             ->setSize(3))

                      ->add(DefinitionFactory::newDatabaseId('states_id')
                                             ->setLabel(tr('State'))
                                             ->setDisabled(true)
                                             ->setOptional(true)
                                             ->setSize(3))

                      ->add(DefinitionFactory::newDatabaseId('cities_id')
                                             ->setLabel(tr('City'))
                                             ->setDisabled(true)
                                             ->setOptional(true)
                                             ->setSize(3))

                      ->add(DefinitionFactory::newLatitude()
                                             ->setDisabled(true)
                                             ->setHelpText(tr('The latitude location for this authentication')))

                      ->add(DefinitionFactory::newLongitude()
                                             ->setDisabled(true)
                                             ->setHelpText(tr('The longitude location for this authentication')))

                      ->add(DefinitionFactory::newBoolean('captcha_required')
                                             ->setLabel(tr('Required CAPTCHA'))
                                             ->setDisabled(true)
                                             ->setOptional(true, false)
                                             ->setSize(2))

                      ->add(Definition::new('failed_reason')
                                      ->setLabel(tr('Reason why failed'))
                                      ->setDisabled(true)
                                      ->setOptional(true)
                                      ->setMaxLength(4090)
                                      ->setSize(4));
        return $this;
    }
}
