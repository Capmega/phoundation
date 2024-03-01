<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Docker;

use Phoundation\Data\Traits\DataDirectory;
use Phoundation\Data\Traits\DataPort;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Os\Processes\Process;
use Phoundation\Virtualization\Traits\DataImage;


/**
 * Class DockerFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class DockerFile
{
    use DataDirectory;
    use DataPort;
    use DataImage;
    use DataRestrictions;

    /**
     * Docker file FROM variable
     *
     * @var string|null $from
     */
    protected ?string $from = null;

    /**
     * DockerFile class constructor
     *
     * @param string $image
     * @param string $directory
     */
    public function __construct(string $image, string $directory = DIRECTORY_ROOT)
    {
        if (!$image) {
            throw new OutOfBoundsException(tr('No docker image specified'));
        }

        $this->setImage($image);
        $this->setDirectory($directory);
    }


    /**
     * Returns a new object
     *
     * @param string $image
     * @param string $directory
     * @return static
     */
    public static function new(string $image, string $directory = DIRECTORY_ROOT): static
    {
        return new static($image, $directory);
    }


    /**
     * Sets the docker file FROM
     *
     * @param string $from
     * @return $this
     */
    public function setFrom(string $from): static
    {
        $this->from = $from;
        return $this;
    }


    /**
     * Returns the docker file FROM
     *
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }


    /**
     * Create configuration files for docker
     *
     * @return $this
     */
    public function writeConfig(): static
    {
        // Delete old docker configuration files
        File::new($this->directory . '.docker')->setRestrictions($this->restrictions->getChild('.docker'))->delete();
        File::new($this->directory . 'docker-compose.yml')->setRestrictions($this->restrictions->getChild('docker-compose.yml'))->delete();

        File::new($this->directory . '.docker/Dockerfile')
            ->setRestrictions($this->restrictions->getChild('.docker/Dockerfile'))
            ->create('FROM php:8.2-apache
COPY . /app
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN chown -R www-data:www-data /app && a2enmod rewrite');

        File::new($this->directory . '.docker/vhost.conf')
            ->setRestrictions($this->restrictions->getChild('.docker/vhost.conf'))
            ->create('<VirtualHost *:80>
    DocumentRoot /app/public
    <Directory “/app/public”>
        AllowOverride all
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>');

        File::new($this->directory . 'docker-compose.yml')
            ->setRestrictions($this->restrictions->getChild('docker-compose.yml'))
            ->create('version: ‘3’
services:
  docker-tutorial:
    build:
      context: .
      dockerfile: ' . $this->directory . '.docker/Dockerfile
    image: ' . $this->image . '
    ports:
      – 8080:80
');

        return $this;
    }


    /**
     * Builds an image from the Dockerfile
     *
     * @param bool $passthru
     * @return array|null
     */
    public function build(bool $passthru = true): ?array
    {
        // Execute the docker build process
        $process = Process::new('docker')
            ->setSudo(true)
            ->setTimeout(300)
            ->setRestrictions($this->directory)
            ->setExecutionDirectory($this->directory)
            ->addArguments(['build', '-f', $this->directory . '.docker/Dockerfile', '-t', $this->image, '.']);

        if ($passthru) {
            $process->executePassthru();
            return null;
        }

        return $process->executeReturnArray();
    }
}
