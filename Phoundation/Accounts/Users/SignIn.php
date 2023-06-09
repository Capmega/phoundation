<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinition;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryIpAddress;
use Phoundation\Data\DataEntry\Traits\DataEntryTimezone;
use Phoundation\Data\DataEntry\Traits\DataEntryUserAgent;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Traits\DataGeoIp;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\GeoIp\GeoIp;
use Phoundation\Geo\Timezones\Timezones;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;
use Phoundation\Web\Http\Html\Enums\InputType;

/**
 * SignIn class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class SignIn extends DataEntry
{
    use DataEntryUserAgent;
    use DataEntryIpAddress;
    use DataEntryTimezone;
    use DataGeoIp;


    /**
     * SignIn class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name  = 'signin';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_signins';
    }


    /**
     * Detects signin information automatically
     *
     * @return $this
     */
    public static function detect(): static
    {
        return SignIn::new()
            ->setIpAddress($_SERVER['REMOTE_ADDR'])
            ->setUserAgent($_SERVER['HTTP_USER_AGENT'])
            ->setGeoIp(GeoIp::detect($_SERVER['REMOTE_ADDR']));
    }


    /**
     * Sets the available data keys for the User class
     *
     * @return DataEntryFieldDefinitionsInterface
     */
    protected static function setFieldDefinitions(): DataEntryFieldDefinitionsInterface
    {
        return DataEntryFieldDefinitions::new(static::getTable())
            ->add(DataEntryFieldDefinition::new('ip_address')
                ->setVisible(false))
            ->add(DataEntryFieldDefinition::new('net_len')
                ->setVisible(false))
            ->add(DataEntryFieldDefinition::new('ip_address_human')
                ->setReadonly(true)
                ->setSize(6)
                ->setMaxlength(48)
                ->setLabel(tr('IP Address')))
            ->add(DataEntryFieldDefinition::new('user_agent')
                ->setReadonly(true)
                ->setSize(6)
                ->setMaxlength(2040)
                ->setLabel(tr('User agent')))
            ->add(DataEntryFieldDefinition::new('latitude')
                ->setReadonly(true)
                ->setInputType(InputType::numeric)
                ->setSize(6)
                ->setMin(-90)
                ->setMax(90)
                ->setStep('any')
                ->setLabel(tr('Latitude')))
            ->add(DataEntryFieldDefinition::new('longitude')
                ->setReadonly(true)
                ->setInputType(InputType::numeric)
                ->setSize(6)
                ->setMin(-180)
                ->setMax(180)
                ->setStep('any')
                ->setLabel(tr('Longitude')))
            ->add(DataEntryFieldDefinition::new('countries_id')
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::dbid)
                ->setContent(function (string $key, array $data, array $source) {
                    return Countries::getHtmlCountriesSelect($key)
                        ->setDisabled(true)
                        ->setSelected(isset_get($source['countries_id']))
                        ->render();
                })
                ->setSize(6)
                ->setLabel(tr('Country'))
                ->addValidationFunction(function ($validator) {
                    $validator->xor('country')->isQueryColumn('SELECT `name` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':id' => '$countries_id']);
                }))
            ->add(DataEntryFieldDefinition::new('timezones_id')
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::dbid)
                ->setContent(function (string $key, array $data, array $source) {
                    return Timezones::getHtmlSelect($key)
                        ->setDisabled(true)
                        ->setSelected(isset_get($source['timezones_id']))
                        ->render();
                })
                ->setSize(6)
                ->setLabel(tr('Timezone'))
                ->addValidationFunction(function ($validator) {
                    $validator->xor('timezone')->isId()->isQueryColumn('SELECT `id` FROM `geo_timezones` WHERE `id` = :id AND `status` IS NULL', [':id' => '$timezones_id']);
                }));
    }
}