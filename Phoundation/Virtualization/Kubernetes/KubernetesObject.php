<?php

namespace Phoundation\Virtualization\Kubernetes;

use Phoundation\Cli\Cli;
use Phoundation\Core\Strings;
use Phoundation\Data\Traits\DataArrayData;
use Phoundation\Data\Traits\DataName;
use Phoundation\Data\Traits\UsesNewName;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Enum\ExecuteMethod;
use Phoundation\Processes\Process;
use Phoundation\Virtualization\Traits\KubeCtl;


/**
 * Class KubernetesObject
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class KubernetesObject
{
    use KubeCtl;
    use UsesNewName;
    use DataArrayData;
    use DataName;


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
            ->addArguments(['delete', 'deployment', $this->id]);

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
     * @param ExecuteMethod $method
     * @return static
     */
    public function apply(ExecuteMethod $method = ExecuteMethod::passthru): static
    {
        Process::new('kubectl')
            ->addArguments(['apply', '-f', $this->getObjectFile()->getFile()])
            ->execute($method);

        return $this;
    }


    /**
     * Creates and returns a CLI table for the data in this entry
     *
     * @param string|null $key_header
     * @param string|null $value_header
     * @return void
     */
    public function getCliForm(?string $key_header = null, ?string $value_header = null): void
    {
        Cli::displayForm($this->data, $key_header, $value_header);
    }


    /**
     * Load the deployment description
     *
     * @return static
     */
    protected function load(): static
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