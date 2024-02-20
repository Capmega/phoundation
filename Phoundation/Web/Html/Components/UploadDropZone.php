<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use PDO;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataCallbacks;
use Phoundation\Data\Traits\DataSelector;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataTitle;
use Phoundation\Data\Traits\DataUrl;
use Phoundation\Date\DateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\InputCheckbox;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\Interfaces\EnumTableIdColumnInterface;
use Phoundation\Web\Html\Enums\Interfaces\EnumTableRowTypeInterface;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Html\Enums\EnumTableRowType;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Stringable;


/**
 * Class UploadDropZone
 *
 * This class can create various HTML tables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class UploadDropZone extends ResourceElement
{
    use DataSelector;
    use DataUrl;


    /**
     * Start upload button
     *
     * @var string|null
     */
    protected ?string $start_button_selector = null;

    /**
     * Add upload button
     *
     * @var string|null
     */
    protected ?string $add_button_selector = null;

    /**
     * Add upload button
     *
     * @var string|null
     */
    protected ?string $cancel_button_selector = null;

    /**
     *
     *
     * @var string|null $total_progress_selector
     */
    protected ?string $total_progress_selector = null;

    /**
     *
     *
     * @var string|null $preview_container_selector
     */
    protected ?string $preview_container_selector = null;

    /**
     *
     *
     * @var string|null $actions_container_selector
     */
    protected ?string $actions_container_selector = null;

    /**
     *
     *
     * @var string|null $progress_bar_selector
     */
    protected ?string $progress_bar_selector = null;


    /**
     * UploadDropZone class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);

        $this->selector                   = 'document.body';
        $this->total_progress_selector    = '#total-progress';
        $this->progress_bar_selector      = '.progress-bar';
        $this->preview_container_selector = '#previews';
        $this->actions_container_selector = '#actions';
        $this->start_button_selector      = '.start';
        $this->cancel_button_selector     = '.cancel';
    }


    /**
     * Sets the start upload button selector
     *
     * @param string|null $selector
     * @return $this
     */
    public function setStartButtonSelector(?string $selector): static
    {
        $this->start_button_selector = $selector;
        return $this;
    }


    /**
     * Returns the start upload button selector
     *
     * @return string|null
     */
    public function getStartButtonSelector(): ?string
    {
        return $this->start_button_selector;
    }


    /**
     * Sets the add upload button selector
     *
     * @param string|null $selector
     * @return $this
     */
    public function setAddButtonSelector(?string $selector): static
    {
        $this->add_button_selector = $selector;
        return $this;
    }


    /**
     * Returns the add upload button selector
     *
     * @return string|null
     */
    public function getAddButtonSelector(): ?string
    {
        return $this->add_button_selector;
    }


    /**
     * @inheritDoc
     */
    public function renderBody(): ?string
    {
        Page::loadCss('plugins/dropzone/min/dropzone');
        Page::loadJavascript('plugins/dropzone/min/dropzone');

        return Script::new()
            ->setJavascriptWrapper(EnumJavascriptWrappers::window)
            ->setContent('
              var myDropzone = new Dropzone(' . $this->selector . ', {
                url: "' . not_empty($this->url, UrlBuilder::getWww()) . '",
                thumbnailWidth: 80,
                thumbnailHeight: 80,
                parallelUploads: 20,
                previewTemplate: previewTemplate,
                autoQueue: false, // Make sure the files aren\'t queued until manually added
                previewsContainer: "' . $this->preview_container_selector . ' ' . $this->progress_bar_selector . '", // Define the container to display the previews
                clickable: ".fileinput-button" // Define the element that should be used as click trigger to select files.
              })

              myDropzone.on("addedfile", function(file) {
                // Hookup the start button
                file.previewElement.querySelector("' . $this->start_button_selector . '").onclick = function() { myDropzone.enqueueFile(file) }
              })

              // Update the total progress bar
              myDropzone.on("totaluploadprogress", function(progress) {
                document.querySelector("' . $this->total_progress_selector . '").style.width = progress + "%"
              })

              myDropzone.on("sending", function(file) {
                // Show the total progress bar when upload starts
                document.querySelector("' . $this->total_progress_selector . '").style.opacity = "1"
                // And disable the start button
                file.previewElement.querySelector("' . $this->start_button_selector . '").setAttribute("disabled", "disabled")
              })

              // Hide the total progress bar when nothing\'s uploading anymore
              myDropzone.on("queuecomplete", function(progress) {
                  document.querySelector("' . $this->total_progress_selector . '").style.opacity = "0"
              })

              // Setup the buttons for all transfers
              // The "add files" button doesn\'t need to be setup because the config
              // `clickable` has already been specified.
              document.querySelector("' . $this->actions_container_selector . ' ' . $this->start_button_selector . '").onclick = function() {
                  myDropzone.enqueueFiles(myDropzone.getFilesWithStatus(Dropzone.ADDED))
              }
              document.querySelector("' . $this->actions_container_selector . ' ' . $this->cancel_button_selector . '").onclick = function() {
                  myDropzone.removeAllFiles(true)
              }
            ')
            ->render();
    }
}
