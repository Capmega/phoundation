<?php

declare(strict_types=1);

namespace Phoundation\Content\Images;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FileBasics;
use Phoundation\Processes\Process;

/**
 * Class Resize
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Content
 */
class Resize extends FileBasics
{
    /**
     * If the resize command should be executed in the background or not
     *
     * @var bool $background
     */
    protected bool $background = false;

    /**
     * Contains the output for each resize operation
     *
     * @var array|null $output
     */
    protected array|null $output = null;

    /**
     * Method to be used for the resize operations
     *
     * @var string $method
     */
    protected string $method = 'resize';


    /**
     * Returns the method used to perform the resize operations
     *
     * @param string $method
     * @return Resize
     */
    public function setMethod(#[ExpectedValues('resize', 'sample', 'scale')] string $method): Resize
    {
        switch ($method) {
            case 'resize':
                // no-break
            case 'sample':
            // no-break
            case 'scale':
                break;

            default:
                throw new OutOfBoundsException(tr('Unknown method ":method" specified, please use one of "resize", "scale", or "sample"', [
                    ':method' => $method
                ]));
        }

        $this->method = $method;
        return $this;
    }


    /**
     * Returns the method used to perform the resize operations
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }


    /**
     * Returns if the resize command should be executed in the background or not
     *
     * @return bool
     */
    public function getBackground(): bool
    {
        return $this->background;
    }


    /**
     * Sets if the resize command should be executed in the background or not
     *
     * @param bool $background
     * @return Resize
     */
    public function setBackground(bool $background): Resize
    {
        $this->background = $background;
        return $this;
    }


    /**
     * Resize the image to the specified absolute width and height
     *
     * @param int $width
     * @param int $height
     * @return void
     */
    public function absolute(int $width, int $height): void
    {
        $process = $this->convert()
            ->addArgument($this->file)
            ->addArgument('-' . $this->method)
            ->addArgument($width . 'x' . $height)
            ->addArgument($this->getTarget());

        if ($this->background) {
            // Resize in background
            $process->executeBackground(true);
        }

        // Resize in foreground and store results
        $this->output = $process->executeReturnArray();
    }


    /**
     * Resize the image to the specified absolute width and height, ignore aspect ratio
     *
     * @param int $width
     * @param int $height
     * @return void
     */
    public function absoluteKeepAspectration(int $width, int $height): void
    {
        $process = $this->convert()
            ->addArgument($this->file)
            ->addArgument('-' . $this->method)
            ->addArgument($width . 'x' . $height . '\\!')
            ->addArgument($this->target);

        if ($this->background) {
            // Resize in background
            $process->executeBackground(true);
        }

        // Resize in foreground and store results
        $this->output = $process->executeReturnArray();
    }


    /**
     * Resize image to the specified absolute width and height only when larger than the specified width x height
     *
     * @param int $width
     * @param int $height
     * @return void
     */
    public function shrinkOnlyLarger(int $width, int $height): void
    {
        $process = $this->convert()
            ->addArgument($this->file)
            ->addArgument('-' . $this->method)
            ->addArgument($width . 'x' . $height . '\\>')
            ->addArgument($this->target);

        if ($this->background) {
            // Resize in background
            $process->executeBackground(true);
        }

        // Resize in foreground and store results
        $this->output = $process->executeReturnArray();
    }


    /**
     * Resize image to the specified absolute width and height only when smaller than the specified width x height
     *
     * @param int $width
     * @param int $height
     * @return void
     */
    public function enlargeOnlySmaller(int $width, int $height): void
    {
        $process = $this->convert()
            ->addArgument($this->file)
            ->addArgument('-' . $this->method)
            ->addArgument($width . 'x' . $height . '\\>')
            ->addArgument($this->target);

        if ($this->background) {
            // Resize in background
            $process->executeBackground(true);
        }

        // Resize in foreground and store results
        $this->output = $process->executeReturnArray();
    }


    /**
     * Resize image to the specified percentage
     *
     * @param float $percentage
     * @return void
     */
    public function percentage(float $percentage): void
    {
        $process = $this->convert()
            ->addArgument($this->file)
            ->addArgument('-' . $this->method)
            ->addArgument($percentage . '%')
            ->addArgument($this->target);

        if ($this->background) {
            // Resize in background
            $process->executeBackground(true);
        }

        // Resize in foreground and store results
        $this->output = $process->executeReturnArray();
    }


    /**
     * Resize image to the specified amount of pixels, irrespective of the width or height
     *
     * @param int $pixel_count
     * @return void
     */
    public function pixelCount(int $pixel_count): void
    {
        $process = $this->convert()
            ->addArgument($this->file)
            ->addArgument('-' . $this->method)
            ->addArgument($pixel_count . '@')
            ->addArgument($this->target);

        if ($this->background) {
            // Resize in background
            $process->executeBackground(true);
        }

        // Resize in foreground and store results
        $this->output = $process->executeReturnArray();
    }


    /**
     * Returns a "convert" process
     *
     * @return Process
     */
    protected function convert(): Process
    {
        return Process::new('convert', $this->restrictions);
    }
}