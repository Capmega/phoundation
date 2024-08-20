<?php

/**
 * Class UploadHandler
 *
 * This request subclass handles upload functionalities
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Uploads;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataMimetype;
use Phoundation\Data\Traits\TraitDataUrl;
use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\FileValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsUploadedFile;
use Phoundation\Filesystem\Interfaces\FsUploadedFileInterface;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Uploads\Interfaces\UploadHandlerInterface;


class UploadHandler implements UploadHandlerInterface
{
    use TraitStaticMethodNew;
    use TraitDataUrl;
    use TraitDataMimetype;


    /**
     * The selector to use for the drag/drop component
     *
     * @var string|null
     */
    protected string|null $selector;

    /**
     * The handler code for this file
     *
     * @var mixed|null
     */
    protected mixed $function = null;

    /**
     * Validations to execute to ensure
     */
    protected array $validations = [];

    /**
     * Contains a list of all HTML element id's that can receive drag/drop file uploads as keys and callback methods as
     * the handlers
     *
     * @var IteratorInterface
     */
    protected IteratorInterface $handlers;

    /**
     * The maximum number of files that may be uploaded
     *
     * @var int $max_files
     */
    protected int $max_files = 1;

    /**
     * Tracks if the file in this handler has been validated or not
     *
     * @var bool $validated
     */
    protected bool $validated = false;


    /**
     * Returns a list of all upload handlers
     *
     * @return IteratorInterface
     */
    public function getValidatorObject(): IteratorInterface
    {
        return $this->handlers;
    }


    /**
     * UploadHandler class constructor
     */
    public function __construct(?string $mimetype = null)
    {
        $this->setUrl(Url::getWww())
            ->setMimetype($mimetype);
    }


    /**
     * Returns a new UploadHandler class
     */
    public static function new(?string $mimetype = null)
    {
        return new static($mimetype);
    }


    /**
     * Returns the maximum number of files that will be allowed to be uploaded
     *
     * @return int
     */
    public function getMaxFiles(): int
    {
        return $this->max_files;
    }


    /**
     * Sets the maximum number of files that will be allowed to be uploaded
     *
     * @param int $max_files
     *
     * @return static
     */
    public function setMaxFiles(int $max_files): static
    {
        if ($max_files < 1) {
            throw new OutOfBoundsException(tr('The max_files parameter cannot be lower than 1'));
        }

        $this->max_files = $max_files;

        return $this;
    }


    /**
     * Returns the handler function for this file
     *
     * @return callable
     */
    public function getFunction(): callable
    {
        return $this->function;
    }


    /**
     * Sets the handler function for this file
     *
     * @param callable $function
     *
     * @return static
     */
    public function setFunction(callable $function): static
    {
        $this->function = $function;

        return $this;
    }


    /**
     * Returns the HTML selector to which the drag/drop will be attached
     *
     * @return string|null
     */
    public function getSelector(): ?string
    {
        return $this->selector;
    }


    /**
     * Sets the HTML selector to which the drag/drop will be attached
     *
     * @param string|null $selector
     *
     * @return static
     */
    public function setSelector(?string $selector): static
    {
        $this->selector = $selector;

        return $this;
    }


    /**
     * Clears all currently existing validation functions for this definition
     *
     * @return static
     */
    public function clearValidationFunctions(): static
    {
        $this->validations = [];

        return $this;
    }


    /**
     * Adds the specified validation function to the validation functions list for this definition
     *
     * @param callable $function
     *
     * @return static
     */
    public function addValidationFunction(callable $function): static
    {
        $this->validations[] = $function;

        return $this;
    }


    /**
     * Renders the drag/drop code for this handler, if needed
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return Response::addScript('$("' . $this->selector . '").dropzone({ url: "' . $this->url . '" });');
    }


    /**
     * Have this handler process the specified uploaded file
     *
     * @param FsUploadedFileInterface $file
     *
     * @return FsUploadedFileInterface
     */
    public function process(FsUploadedFileInterface $file): FsUploadedFileInterface
    {
        $this->validate($file);

        if (!$this->hasBeenValidated()) {
            throw new ValidationFailedException(tr('Cannot start processing for file ":file" with mimetype ":mimetype", the file has not been validated', [
                ':file'     => $file->getSource(),
                ':mimetype' => $file->getMimetype()
            ]));
        }

        $function = $this->function;
        $function($file);

        return $file;
    }


    /**
     * Returns true if the file in this handler has been validated
     *
     * @return bool
     */
    public function hasBeenValidated(): bool
    {
        return $this->validated;
    }


    /**
     * Have this handler process the specified uploaded file
     *
     * @param FsUploadedFileInterface $file
     *
     * @return void
     */
    protected function validate(FsUploadedFileInterface $file): void
    {
        $file->ensureExtensionMatchesMimetype();

//        $validator = FileValidator::new($file);
//
//        foreach ($this->validations as $function) {
//            $function($validator);
//        }
//
//        $validator->validate();

        $this->validated = true;
    }
}
