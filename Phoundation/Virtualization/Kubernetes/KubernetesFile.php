<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes;

use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsFile;

/**
 * Class KubernetesFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */
abstract class KubernetesFile
{
    use TraitDataFile;

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
        $this->file = DIRECTORY_ROOT . 'config/kubernetes/' . $this->kind . '/';
    }


    /**
     * Save the data from this deployment to the yaml configuration file
     *
     * @return static
     */
    public function save(): static
    {
        $data = $this->renderConfiguration();
        $data = yaml_emit($data);
//        $data = Strings::from($data, PHP_EOL);
//        $data = Strings::untilReverse($data, PHP_EOL);
        FsFile::new($this->file)
            ->setRestrictions(DIRECTORY_ROOT . 'config/kubernetes/' . $this->kind . '/', true, 'kubernetes')
            ->create($data);

        return $this;
    }


    /**
     * Builds the configuration data
     *
     * @param array|null $configuration
     *
     * @return array
     */
    protected function renderConfiguration(array $configuration = null): array
    {
        if (!isset($this->kind)) {
            throw new OutOfBoundsException(tr('No Kubernetes file kind specified'));
        }

        return array_merge([
            'apiVersion' => 'apps/v1',
            'kind'       => $this->kind,
        ], $configuration);
    }
}