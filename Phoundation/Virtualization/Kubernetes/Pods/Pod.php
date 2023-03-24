<?php

namespace Phoundation\Virtualization\Kubernetes\Pods;

use Phoundation\Data\Traits\DataName;
use Phoundation\Processes\Process;
use Phoundation\Virtualization\Kubernetes\Files\PodFile;
use Phoundation\Virtualization\Kubernetes\KubeCtl\KubeCtl;
use Phoundation\Virtualization\Traits\DataImage;
use Phoundation\Virtualization\Traits\DataReplicas;


/**
 * Class Pod
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
class Pod extends KubeCtl
{
    use DataName;
    use DataImage;
    use DataReplicas;


    /**
     * Kubernetes pod id
     *
     * @var string|null $id
     */
    protected ?string $id;

    /**
     * The configuration file for this pod
     *
     * @var PodFile|null $file
     */
    protected ?PodFile $file = null;


    /**
     * Pod class constructor
     *
     * @param string|null $id
     */
    public function __construct(?string $id = null)
    {
        $this->id = $id;
    }


    /**
     * Pod class constructor
     *
     * @param string|null $id
     * @return Pod
     */
    public static function new(?string $id = null): static
    {
        return new static($id);
    }


    /**
     * Returns the PodFile for this Kubernetes Pod object
     *
     * @return PodFile
     */
    public function getFile(): PodFile
    {
        if (!$this->file) {
            $this->file = new PodFile($this);
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
        $this->getFile()->save();
        return $this;
    }


    /**
     * Deletes this pod
     *
     * @return static
     */
    public function delete(): static
    {
        Process::new('kubectl')
            ->addArguments(['delete', 'pod', $this->id]);

        return $this;
    }


    /**
     * Creates a Kubernetes pod from the internal variables
     *
     * @return static
     */
    public function create(): static
    {
        Process::new('kubectl')
            ->addArguments(['create', 'pod']);

        return $this;
    }


    /**
     * Creates a Kubernetes pod from the pod file
     *
     * @return static
     */
    public function apply(): static
    {
        Process::new('kubectl')
            ->addArguments(['apply', '-f', $this->getFile()->getFile()]);

        return $this;
    }
}