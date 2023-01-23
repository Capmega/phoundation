<?php

namespace Phoundation\Versioning;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Process;

class Git extends Versioning
{
    /**
     * The path on which this git object is working
     *
     * @var string $path
     */
    protected string $path;



    /**
     * Git constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = realpath($path);

        if (!$this->path) {
            if (!file_exists($path)) {
                throw new OutOfBoundsException(tr('The specified path ":path" does not exist', [
                    ':path' => $path
                ]));
            }
        }
    }



    /**
     * Generates and returns a new Git object
     *
     * @param string $path
     * @return Git
     */
    public static function new(string $path): Git
    {
        return new Git($path);
    }



    /**
     * @return bool
     */
    public function getChanges(): bool
    {
        return (bool) $this->hasChanges();
    }



    /**
     * Returns an array with the git changes in the objects path
     *
     * @return array
     */
    public function hasChanges(): array
    {
        $return = [];
        $files  = Process::new('git')
            ->addArgument('status')
            ->addArgument($this->path)
            ->executeReturnArray();

        // Parse output
        foreach ($files as $file) {

        }

        return $return;
    }
}