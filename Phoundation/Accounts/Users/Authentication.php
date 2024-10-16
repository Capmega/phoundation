<?php

/**
 * Class Authentication
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Enums\EnumAuthenticationAction;
use Phoundation\Accounts\Users\Interfaces\AuthenticationInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryAlreadySavedException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCity;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCountry;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryIpAddress;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryLongLat;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryMethod;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryPlatform;
use Phoundation\Data\DataEntry\Traits\TraitDataEntrySetCreatedBy;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryState;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryTimezone;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUserAgent;
use Phoundation\Geo\GeoIp\Exception\GeoIpException;
use Phoundation\Geo\GeoIp\GeoIp;
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
    use TraitDataEntrySetCreatedBy;


    /**
     * Authentication class constructor
     *
     * @param array|int|string|DataEntryInterface|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|int|string|DataEntryInterface|null $identifier = null, ?bool $meta_enabled = null, bool $init = true)
    {
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

        parent::__construct($identifier, $meta_enabled, $init);

        if ($this->isNew()) {
            try {
                $city = GeoIp::detect(Session::getIpAddress())->getCity();

            } catch (GeoIpException $e) {
                Log::warning(tr('Geo IP lookup failed for address ":ip", no GEO IP data will be available', [
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
    public static function getDataEntryName(): string
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
     * @return string|null
     */
    public function getAccount(): ?string
    {
        return $this->getTypesafe('string', 'account');
    }


    /**
     * Sets the account for this authentication
     *
     * @param string|null $user_agent
     *
     * @return static
     */
    public function setAccount(?string $user_agent): static
    {
        return $this->set($user_agent, 'account');
    }


    /**
     * Returns the captcha_required for this authentication
     *
     * @return int|bool|null
     */
    public function getCaptchaRequired(): int|bool|null
    {
        return $this->getTypesafe('string', 'captcha_required');
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
        return $this->set($user_agent, 'captcha_required');
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
     * @return $this
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        if (!$this->isNew()) {
            throw new DataEntryAlreadySavedException(tr('Cannot save Authentication DataEntry, has already been saved before'));
        }

        return parent::save($force, $skip_validation, $comments);
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
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        // Ensure status will be limited to the defined possible states
        $definitions->get('status')->setDataSource(static::getStatuses());

        $definitions->add(DefinitionFactory::newCreatedBy($this))

                    ->add(Definition::new($this, 'account')
                                    ->setLabel(tr('Used user account'))
                                    ->setDisabled(true)
                                    ->setMaxlength(128)
                                    ->setSize(6))

                    ->add(DefinitionFactory::newNumber($this, 'ip_address_binary')
                                           ->setRender(false))

                    ->add(DefinitionFactory::newNumber($this, 'net_len')
                                           ->setDefault(0)
                                           ->setRender(false))

                    ->add(DefinitionFactory::newIpAddress($this, 'ip_address')
                                           ->setLabel(tr('IP address'))
                                           ->setDisabled(true)
                                           ->setOptional(true)
                                           ->setSize(4))

                    ->add(Definition::new($this, 'user_agent')
                                    ->setLabel(tr('User agent'))
                                    ->setDisabled(true)
                                    ->setOptional(true)
                                    ->setMaxlength(2040)
                                    ->setSize(8))

                    ->add(Definition::new($this, 'action')
                                    ->setLabel(tr('Action'))
                                    ->setDisabled(true)
                                    ->setOptional(true)
                                    ->setSize(4)
                                    ->setDataSource(static::getActions()))

                    ->add(Definition::new($this, 'platform')
                                    ->setLabel(tr('Platform'))
                                    ->setDisabled(true)
                                    ->setOptional(true)
                                    ->setSize(4)
                                    ->setDataSource(static::getPlatforms()))

                    ->add(Definition::new($this, 'method')
                                    ->setLabel(tr('Method'))
                                    ->setDisabled(true)
                                    ->setOptional(true)
                                    ->setSize(4)
                                    ->setDataSource(static::getMethods()))

                    ->add(DefinitionFactory::newNumber($this, 'latitude')
                                           ->setLabel(tr('Latitude'))
                                           ->setDisabled(true)
                                           ->setOptional(true)
                                           ->setSize(6))

                    ->add(DefinitionFactory::newNumber($this, 'longitude')
                                    ->setLabel(tr('Longitude'))
                                    ->setDisabled(true)
                                    ->setOptional(true)
                                    ->setSize(6))

                    ->add(DefinitionFactory::newDatabaseId($this, 'timezones_id')
                                           ->setLabel(tr('Timezone'))
                                           ->setDisabled(true)
                                           ->setOptional(true)
                                           ->setSize(3))

                    ->add(DefinitionFactory::newDatabaseId($this, 'countries_id')
                                           ->setLabel(tr('Country'))
                                           ->setDisabled(true)
                                           ->setOptional(true)
                                           ->setSize(3))

                    ->add(DefinitionFactory::newDatabaseId($this, 'states_id')
                                           ->setLabel(tr('State'))
                                           ->setDisabled(true)
                                           ->setOptional(true)
                                           ->setSize(3))

                    ->add(DefinitionFactory::newDatabaseId($this, 'cities_id')
                                           ->setLabel(tr('City'))
                                           ->setDisabled(true)
                                           ->setOptional(true)
                                           ->setSize(3))

                    ->add(DefinitionFactory::newBoolean($this, 'captcha_required')
                                           ->setLabel(tr('Required CAPTCHA'))
                                           ->setDisabled(true)
                                           ->setOptional(true, false)
                                           ->setSize(2))

                    ->add(Definition::new($this, 'failed_reason')
                                    ->setLabel(tr('Reason why failed'))
                                    ->setDisabled(true)
                                    ->setOptional(true)
                                    ->setMaxlength(4090)
                                    ->setSize(10));
    }
}
