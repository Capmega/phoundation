<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryIpAddress;
use Phoundation\Data\DataEntry\Traits\DataEntryTimezone;
use Phoundation\Data\DataEntry\Traits\DataEntryUserAgent;
use Phoundation\Data\Traits\DataGeoIp;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\GeoIp\GeoIp;
use Phoundation\Geo\Timezones\Timezones;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


/**
 * SignIn class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = false)
    {
        $this->table       = 'accounts_signins';
        $this->entry_name  = 'signin';

        parent::__construct($identifier, $init);
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
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->add(Definition::new('ip_address')
                ->setVisible(false))
            ->add(Definition::new('net_len')
                ->setVisible(false))
            ->add(Definition::new('ip_address_human')
                ->setReadonly(true)
                ->setSize(6)
                ->setMaxlength(48)
                ->setLabel(tr('IP Address')))
            ->add(Definition::new('user_agent')
                ->setOptional(true)
                ->setReadonly(true)
                ->setSize(6)
                ->setMaxlength(2040)
                ->setLabel(tr('User agent')))
            ->add(Definition::new('latitude')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setSize(6)
                ->setMin(-90)
                ->setMax(90)
                ->setStep('any')
                ->setLabel(tr('Latitude')))
            ->add(Definition::new('longitude')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setSize(6)
                ->setMin(-180)
                ->setMax(180)
                ->setStep('any')
                ->setLabel(tr('Longitude')))
            ->add(Definition::new('countries_id')
                ->setOptional(true)
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
            ->add(Definition::new('timezones_id')
                ->setOptional(true)
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