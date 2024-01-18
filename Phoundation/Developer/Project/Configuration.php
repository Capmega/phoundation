<?php

declare(strict_types=1);

namespace Phoundation\Developer\Project;


/**
 * Configuration class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
 */
class Configuration
{
    /**
     * The database configuration
     *
     * @var Database $database
     */
    protected Database $database;

    /**
     * The administrator email address
     *
     * @var string $email
     */
    protected string $email;

    /**
     * The administrator password
     *
     * @var string $password
     */
    protected string $password;

    /**
     * The domain for this project
     *
     * @var string $domain
     */
    protected string $domain;

    /**
     * The project name
     *
     * @var string $project
     */
    protected string $project;


    /**
     * Configuration class constructor
     *
     * @param string $project
     */
    public function __construct(string $project) {
        $this->project  = $project;
        $this->database = new Database();
    }


    /**
     * Returns the database configuration for this environment
     *
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }


    /**
     * Returns the administrator email address
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }


    /**
     * Sets the administrator email address
     *
     * @param string $email
     * @return static
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }


    /**
     * Returns the administrator password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }


    /**
     * Sets the administrator password
     *
     * @param string $password
     * @return static
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }


    /**
     * Returns the project name
     *
     * @return string
     */
    public function getProject(): string
    {
        return $this->project;
    }


    /**
     * Sets the project name
     *
     * @param string $project
     * @return static
     */
    public function setProject(string $project): static
    {
        $this->project = $project;
        return $this;
    }


    /**
     * Returns the project domain name
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }


    /**
     * Sets the project domain name
     *
     * @param string $domain
     * @return static
     */
    public function setDomain(string $domain): static
    {
        $this->domain = $domain;
        return $this;
    }
}