<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes;

use Phoundation\Core\Strings;
use Phoundation\Data\Traits\DataFile;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;


/**
 * Class KubernetesFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
abstract class KubernetesFile
{
    use DataFile;

    /**
     * The file where to store this Kubernetes configuration
     *
     * @var string $kind
     */
    protected string $kind;

    /**
     * KubernetesFile class constructor
     */
    public function __construct()
    {
        $this->file = PATH_ROOT . 'config/kubernetes/' . $this->kind . '/';
    }


    /**
     * Save the data from this deployment to the yaml configuration file
     *
     * @return $this
     */
    public function save(): static
    {
        $data = $this->buildConfiguration();
        $data = yaml_emit($data);
//        $data = Strings::from($data, PHP_EOL);
//        $data = Strings::untilReverse($data, PHP_EOL);

        File::new($this->file)
            ->setRestrictions(PATH_ROOT . 'config/kubernetes/' . $this->kind . '/', true, 'kubernetes')
            ->create($data);

        return $this;
    }


    /**
     * Builds the configuration data
     *
     * @param array|null $configuration
     * @return array
     */
    protected function buildConfiguration(array $configuration = null): array
    {
        if (!isset($this->kind)) {
            throw new OutOfBoundsException(tr('No Kubernetes file kind specified'));
        }

        return array_merge([
            'apiVersion' => 'apps/v1',
            'kind'       => $this->kind
        ], $configuration);
    }
}