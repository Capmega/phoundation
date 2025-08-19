<?php

/**
 * Class KubernetesObject
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */


declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Traits\TraitDataArrayData;
use Phoundation\Data\Traits\TraitDataArrayOutput;
use Phoundation\Data\Traits\TraitDataStringName;
use Phoundation\Data\Traits\TraitStaticMethodNewWithName;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Strings;
use Phoundation\Virtualization\Kubernetes\Traits\TraitDataAnnotations;
use Phoundation\Virtualization\Kubernetes\Traits\TraitDataLabels;
use Phoundation\Virtualization\Kubernetes\Traits\TraitDataNamespace;
use Phoundation\Virtualization\Kubernetes\Traits\TraitUsesKubeCtl;


class KubernetesObject
{
    use TraitDataArrayOutput;
    use TraitDataAnnotations;
    use TraitDataArrayData;
    use TraitDataLabels;
    use TraitDataStringName;
    use TraitDataNamespace;
    use TraitUsesKubeCtl;
    use TraitStaticMethodNewWithName;

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
        $this->kind        = Strings::fromReverse(static::class, '\\');
        $this->get_command = strtolower($this->kind);
        $this->load();
    }


    /**
     * Load the deployment description
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                      $like
     *
     * @return static
     * @todo Rewrite this using DataIterator::load() method
     */
    public function load(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false): static
    {
        if ($this->getName()) {
            $output = Process::new('kubectl')
                             ->addArguments([
                                 'get',
                                 $this->getCommand(),
                                 '-o=yaml',
                                 $this->getName(),
                             ])
                             ->executeReturnString();
            $this->data = yaml_parse($output);
        }

        return $this;
    }


    /**
     * Save the current deployment data
     *
     * @return static
     */
    public function save(): static
    {
        $this->getObjectFile()->save();

        Log::success(ts('Saved ":kind" object ":name" in file ":file"', [
            ':kind' => $this->getKind(),
            ':name' => $this->getName(),
            ':file' => $this->getObjectFile()->getFileObject(),
        ]));

        return $this;
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
                    ':class' => static::class,
                ]));
            }
            $this->file = new $this->object_file_class($this);
        }

        return $this->file;
    }


    /**
     * Deletes this deployment
     *
     * @return static
     */
    public function delete(): static
    {
        Process::new('kubectl')
               ->addArguments([
                   'delete',
                   'deployment',
                   $this->name,
               ]);
        Log::success(ts('Deleted ":kind" kind object ":secret"', [
            ':kind'   => $this->kind,
            ':secret' => $this->name,
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
               ->addArguments([
                   'create',
                   'deployment',
               ]);

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
                               ->addArguments([
                                   'apply',
                                   '-f',
                                   $this->getObjectFile()
                                        ->getFileObject(),
                               ])
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
}
