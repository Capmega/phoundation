<?php

/**
 * Class DataFakerPerson
 *
 * This class will generate information about a fake person
 *
 * @see https://github.com/fzaninotto/Faker
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataFaker;

use Phoundation\Utils\Numbers;


class DataFakerPerson extends DataFaker
{
    /**
     * The gender of this person
     *
     * @var string
     */
    protected string $gender;

    /**
     * The first name of this person. Will be set when gender is set
     *
     * @var string
     */
    protected string $first_name;

    /**
     * The last name of this person
     *
     * @var string
     */
    protected string $last_name;

    /**
     * The middle name of this person
     *
     * @var string|null
     */
    protected ?string $middle_names;

    /**
     * The address of this person
     *
     * @var string
     */
    protected string $address;

    /**
     * The ssn of this person
     *
     * @var string
     */
    protected string $ssn;

    /**
     * The birthdate of this person
     *
     * @var string
     */
    protected string $birthdate;

    /**
     * The home_phone of this person
     *
     * @var string
     */
    protected string $home_phone;

    /**
     * The work_phone of this person
     *
     * @var string
     */
    protected string $work_phone;


    /**
     * DataFaker class constructor
     *
     * @param string|null $locale   The locale to be used for data generation
     */
    public function __construct(?string $locale = null)
    {
        parent::__construct($locale);

        // Settings gender will automatically set the first name
        $this->setGender($this->faker->randomElement(['male', 'female']));

        $this->middle_names = Numbers::getRandomInt(0, 1) ? substr($this->faker->firstName, 0, 1) : null;
        $this->last_name    = $this->faker->lastName;
        $this->ssn          = '0' . Numbers::getRandomInt(10000000, 99999999);
        $street_number      = Numbers::getRandomInt(0, 99999);
        $street_name        = $this->faker->streetName;
        $city               = $this->faker->city;
        $province           = $this->faker->randomElement(['BC', 'AB', 'SK', 'MB', 'ON', 'QC', 'NB', 'NF', 'PE']);
        $postal_code        = chr(Numbers::getRandomInt(65, 90)) . Numbers::getRandomInt(0, 9) .
                              chr(Numbers::getRandomInt(65, 90)) . Numbers::getRandomInt(0, 9) .
                              chr(Numbers::getRandomInt(65, 90)) . Numbers::getRandomInt(0, 9);
        $this->address      = $street_number . ' ' . $street_name . ', ' . $city . ', ' . $province . ', ' . $postal_code;
        $this->birthdate    = $this->faker->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d');
        $this->home_phone   = (string) Numbers::getRandomInt(1000000000, 9999999999);
        $this->work_phone   = substr($this->home_phone, 0, 6) . Numbers::getRandomInt(1000, 9999);

        return $this;
    }


    /**
     * Returns a new DataFaker object
     *
     * @param string|null $locale
     *
     * @return static
     */
    public static function new(?string $locale = null): static
    {
        return new static($locale);
    }


    /**
     * Returns the first name of this fake person
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->first_name;
    }

    /**
     * Returns the middle names of this fake person
     *
     * @return string|null
     */
    public function getMiddleNames(): ?string
    {
        return $this->middle_names;
    }


    /**
     * Returns the last name of this fake person
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->last_name;
    }


    /**
     * Returns the gender of this fake person
     *
     * @return string
     */
    public function getGender(): string
    {
        return $this->gender;
    }


    /**
     * sets the gender of this fake person
     *
     * @param string $gender
     *
     * @return static
     */
    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        $this->first_name =  match ($gender) {
            'female' =>  $this->faker->firstNameFemale,
            'male'   =>  $this->faker->firstNameMale,
            default  =>  $this->faker->firstName,
        };

        return $this;
    }


    /**
     * Returns the address of this fake person
     * Address is separated by comma
     *
     * Currently, only Canadian Address is supported
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Returns the ssn of this fake person
     *
     * @return string
     */
    public function getSsn(): string
    {
        return $this->ssn;
    }


    /**
     * Returns the birthdate of this fake person
     *
     * @return string
     */
    public function getBirthdate(): string
    {
        return $this->birthdate;
    }


    /**
     * Returns the home_phone of this fake person
     *
     * @return string
     */
    public function getHomePhone(): string
    {
        return $this->home_phone;
    }


    /**
     * Returns the work_phone of this fake person
     *
     * @return string
     */
    public function getWorkPhone(): string
    {
        return $this->work_phone;
    }
}
