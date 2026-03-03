<?php

/**
 * Class DockerFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */


declare(strict_types=1);

namespace Phoundation\Virtualization\Docker;

use Phoundation\Data\Traits\TraitDataObjectDirectory;
use Phoundation\Data\Traits\TraitDataPort;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;
use Phoundation\Os\Processes\Process;
use Phoundation\Virtualization\Traits\TraitDataImage;


class DockerFile
{
    use TraitDataObjectDirectory;
    use TraitDataPort;
    use TraitDataImage;
    use TraitDataRestrictions;

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
        $this->setDirectoryObject($directory);
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
     * Sets the docker file FROM
     *
     * @param string $from
     *
     * @return static
     */
    public function setFrom(string $from): static
    {
        $this->from = $from;

        return $this;
    }


    /**
     * Create configuration files for docker
     *
     * @return static
     */
    public function writeConfig(): static
    {
        // Delete old docker configuration files
        PhoFile::new($this->_directory . '.docker')
            ->setRestrictionsObject($this->_restrictions->getChild('.docker'))
            ->delete();
        PhoFile::new($this->_directory . 'docker-compose.yml')
            ->setRestrictionsObject($this->_restrictions->getChild('docker-compose.yml'))
            ->delete();
        PhoFile::new($this->_directory . '.docker/Dockerfile')
            ->setRestrictionsObject($this->_restrictions->getChild('.docker/Dockerfile'))
            ->create('FROM php:8.2-apache
COPY . /app
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN chown -R www-data:www-data /app && a2enmod rewrite');
        PhoFile::new($this->_directory . '.docker/vhost.conf')
            ->setRestrictionsObject($this->_restrictions->getChild('.docker/vhost.conf'))
            ->create('<VirtualHost *:80>
    DocumentRoot /app/public
    <Directory “/app/public”>
        AllowOverride all
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>');
        PhoFile::new($this->_directory . 'docker-compose.yml')
            ->setRestrictionsObject($this->_restrictions->getChild('docker-compose.yml'))
            ->create('version: ‘3’
services:
  docker-tutorial:
    build:
      context: .
      dockerfile: ' . $this->_directory . '.docker/Dockerfile
    image: ' . $this->image . '
    ports:
      – 8080:80
');

        return $this;
    }


    /**
     * Returns a new object
     *
     * @param string $image
     * @param string $directory
     *
     * @return static
     */
    public static function new(string $image, string $directory = DIRECTORY_ROOT): static
    {
        return new static($image, $directory);
    }


    /**
     * Builds an image from the Dockerfile
     *
     * @param bool $passthru
     *
     * @return array|null
     */
    public function render(bool $passthru = true): ?array
    {
        // Execute the docker build process
        $process = Process::new('docker')
                          ->setSudo(true)
                          ->setTimeout(300)
                          ->setExecutionDirectory($this->_directory)
                          ->appendArguments([
                              'build',
                              '-f',
                              $this->_directory . '.docker/Dockerfile',
                              '-t',
                              $this->image,
                              '.',
                          ]);
        if ($passthru) {
            $process->executePassthru();

            return null;
        }

        return $process->executeReturnArray();
    }
}
