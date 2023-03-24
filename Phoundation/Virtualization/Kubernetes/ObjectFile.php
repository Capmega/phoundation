<?php

namespace Phoundation\Virtualization\Kubernetes;

use Phoundation\Core\Strings;
use Phoundation\Data\Traits\DataFile;
use Phoundation\Data\Traits\DataStringData;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Process;
use Phoundation\Virtualization\Kubernetes\Secrets\Secret;


/**
 * Class KubernetesFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
abstract class ObjectFile
{
    use DataFile;
    use DataStringData;


    /**
     * The kubernetes object for this configuration file
     *
     * @var KubernetesObject $object
     */
    protected KubernetesObject $object;


    /**
     * KubernetesFile class constructor
     */
    public function __construct(KubernetesObject $object)
    {
        $this->object = $object;
        $this->file   = PATH_ROOT . 'config/kubernetes/' . strtolower($object->getKind()) . '/' . $this->object->getName() . '.yml';
    }


    /**
     * Save the data from this deployment to the yaml configuration file
     *
     * @return static
     */
    public function save(): static
    {
        $data = $this->buildConfiguration();
        $data = yaml_emit($data);

        File::new($this->file)
            ->setRestrictions(PATH_ROOT . 'config/kubernetes/' . strtolower($this->object->getKind()) . '/', true, 'kubernetes')
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
        return array_merge([
            'apiVersion' => 'apps/v1',
            'kind'       => $this->object->getKind()
        ], $configuration);
    }
}