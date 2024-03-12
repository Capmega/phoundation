<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataArrayData;
use Phoundation\Data\Traits\TraitDataArrayOutput;
use Phoundation\Data\Traits\TraitDataName;
use Phoundation\Data\Traits\TraitUsesNewName;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Strings;
use Phoundation\Virtualization\Kubernetes\Traits\TraitDataAnnotations;
use Phoundation\Virtualization\Kubernetes\Traits\TraitDataLabels;
use Phoundation\Virtualization\Kubernetes\Traits\TraitDataNamespace;
use Phoundation\Virtualization\Kubernetes\Traits\TraitUsesKubeCtl;


/**
 * Class KubernetesObject
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class KubernetesObject
{
    use TraitDataArrayOutput;
    use TraitDataAnnotations;
    use TraitDataArrayData;
    use TraitDataLabels;
    use TraitDataName;
    use TraitDataNamespace;
    use TraitUsesKubeCtl;
    use TraitUsesNewName;

    /**
     * The configuration file for this object
     *
     * @var ObjectFile|null $file
     */
    protected ?ObjectFile $file = null;

    /**
     * The class for the object file
     *
     * @var string $object_file_class
     */
    protected string $object_file_class;

    /**
     * KubernetesObject class constructor
     *
     * Gets the object list from kubectl right away and stores it in the internal list
     */
    public function __construct(?string $name = null)
    {
        $this->name        = $name;
        $this->kind        = Strings::fromReverse(get_class($this), '\\');
        $this->get_command = strtolower($this->kind);

        $this->load();
    }


    /**
     * Returns the SecretFile for this Kubernetes Secret object
     *
     * @return ObjectFile
     */
    public function getObjectFile(): ObjectFile
    {
        if (!$this->file) {
            if (!isset($this->object_file_class)) {
                throw new OutOfBoundsException(tr('The ":class" class does not support Kubernetes configuration files', [
                    ':class' => get_class($this)
                ]));
            }

            $this->file = new $this->object_file_class($this);
        }

        return $this->file;
    }


    /**
     * Save the current deployment data
     *
     * @return static
     */
    public function save(): static
    {
        $this->getObjectFile()->save();

        Log::success(tr('Saved ":kind" object ":name" in file ":file"', [
            ':kind' => $this->getKind(),
            ':name' => $this->getName(),
            ':file' => $this->getObjectFile()->getFile()
        ]));

        return $this;
    }


    /**
     * Deletes this deployment
     *
     * @return static
     */
    public function delete(): static
    {
        Process::new('kubectl')
            ->addArguments(['delete', 'deployment', $this->name]);

        Log::success(tr('Deleted ":kind" kind object ":secret"', [
            ':kind'   => $this->kind,
            ':secret' => $this->name
        ]));

        return $this;
    }


    /**
     * Creates a Kubernetes deployment from the internal variables
     *
     * @return static
     */
    public function create(): static
    {
        Process::new('kubectl')
            ->addArguments(['create', 'deployment']);

        return $this;
    }


    /**
     * Creates a Kubernetes deployment from the deployment file
     *
     * @return static
     */
    public function apply(): static
    {
        $this->output = Process::new('kubectl')
            ->addArguments(['apply', '-f', $this->getObjectFile()->getFile()])
            ->executeReturnArray();

        Log::success($this->output);

        return $this;
    }


    /**
     * Returns the data in Yaml format
     *
     * @return string
     */
    public function getYaml(): string
    {
        return yaml_emit($this->data);
    }


    /**
     * Load the deployment description
     *
     * @param bool $clear
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static
    {
        if ($this->getName()) {
            $output = Process::new('kubectl')
                ->addArguments(['get', $this->getCommand(), '-o=yaml', $this->getName()])
                ->executeReturnString();

            $this->data = yaml_parse($output);
        }

        return $this;
    }
}
