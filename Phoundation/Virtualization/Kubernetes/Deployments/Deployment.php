<?php

namespace Phoundation\Virtualization\Kubernetes\Deployments;

use Phoundation\Cli\Cli;
use Phoundation\Data\Traits\DataName;
use Phoundation\Processes\Enum\ExecuteMethod;
use Phoundation\Processes\Process;
use Phoundation\Virtualization\Kubernetes\KubeCtl\KubeCtl;
use Phoundation\Virtualization\Traits\DataImage;
use Phoundation\Virtualization\Traits\DataReplicas;


/**
 * Class Deployment
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class Deployment extends KubeCtl
{
    use DataName;
    use DataImage;
    use DataReplicas;


    /**
     * Kubernetes deployment id
     *
     * @var string|null $id
     */
    protected ?string $id;

    /**
     * The configuration file for this deployment
     *
     * @var DeploymentFile|null $file
     */
    protected ?DeploymentFile $file = null;

    /**
     * The configuration data for this deployment
     *
     * @var array $data
     */
    protected array $data;


    /**
     * Deployment class constructor
     *
     * @param string|null $id
     */
    public function __construct(?string $id = null)
    {
        $this->id = $id;
        $this->load();
    }


    /**
     * Deployment class constructor
     *
     * @param string|null $id
     * @return Deployment
     */
    public static function new(?string $id = null): static
    {
        return new static($id);
    }


    /**
     * Returns the DeploymentFile for this Kubernetes Deployment object
     *
     * @return DeploymentFile
     */
    public function getDeploymentFile(): DeploymentFile
    {
        if (!$this->file) {
            $this->file = new DeploymentFile($this);
        }

        return $this->file;
    }


    /**
     * Returns the configuration data for this deployment
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * Sets the configuration data for this deployment
     *
     * @param array $data
     * @return static
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }


    /**
     * Save the current deployment data
     *
     * @return static
     */
    public function save(): static
    {
        $this->getDeploymentFile()->save();
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
            ->addArguments(['apply', '-f', $this->getDeploymentFile()->getFile()])
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
        $output = Process::new('kubectl')
            ->addArguments(['get', 'deployment', '-o=yaml', $this->id])
            ->executeReturnString();

        $this->data = yaml_parse($output);
        return $this;
    }
}