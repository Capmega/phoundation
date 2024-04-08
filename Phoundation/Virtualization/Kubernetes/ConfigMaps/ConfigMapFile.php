<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\ConfigMaps;

use Phoundation\Virtualization\Kubernetes\ObjectFile;

/**
 * Class ConfigMapFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 *
 * @example
 * apiVersion: v1
 * kind: ConfigMap
 * metadata:
 * name: game-demo
 * data:
 * # property-like keys; each key maps to a simple value
 * player_initial_lives: "3"
 * ui_properties_file_name: "user-interface.properties"
 *
 * # file-like keys
 * game.properties: |
 * enemy.types=aliens,monsters
 * player.maximum-lives=5
 * user-interface.properties: |
 * color.good=purple
 * color.bad=yellow
 * allow.textmode=true
 *
 */
class ConfigMapFile extends ObjectFile
{
    /**
     * ConfigMapFile class constructor
     */
    public function __construct(ConfigMap $configmap)
    {
        parent::__construct($configmap);
    }


    /**
     * Returns the kubernetes configmap data object for this configmap file
     *
     * @return ConfigMap
     */
    public function getConfigMap(): ConfigMap
    {
        return $this->object;
    }


    /**
     * Builds the data string for this configmap file from the ConfigMap object
     *
     * @param array|null $configuration
     *
     * @return array
     */
    protected function renderConfiguration(?array $configuration = null): array
    {
        return parent::renderConfiguration([
            'data' => $this->object->getData(),
        ]);
    }
}