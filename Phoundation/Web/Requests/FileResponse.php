<?php

declare(strict_types=1);

namespace Phoundation\Web\Requests;

use finfo;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\Traits\TraitPathConstructor;
use Phoundation\Utils\Config;
use Phoundation\Web\Http\Exception;
use Stringable;

/**
 * Class FileResponse
 *
 * This class can be used to send files to clients over HTTP
 *
 * To clients this is the "download file X" functionality
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

throw new UnderConstructionException(tr('Rebuild the Web\Http\File class, now extending Filesystem\File'));

class FileResponse extends FsFile
{
    use TraitPathConstructor {
        __construct as protected ___construct;
    }

    /**
     * If true, files will be transferred using compression
     *
     * @var string|bool $compression
     */
    protected string|bool $compression = false;

    /**
     * If true, the file will be transferred as an attachment
     *
     * @var bool
     */
    protected bool $attachment = false;

    /**
     * If true, the process will be terminated once the HTTP file transfer has been completed
     *
     * @var bool
     */
    protected bool $die = true;

    /**
     * The file (locally on this server)
     *
     * @var FsFileInterface|null
     */
    protected ?FsFileInterface $file = null;

    /**
     * The name of the file so that the client knows with what name to save it
     *
     * @var string|null $filename
     */
    protected ?string $filename = null;

    /**
     * Raw data for the file to send, in case no file on disk should be sent
     *
     * @var string|null $data
     */
    protected ?string $data = null;

    /**
     * The mimetype for the file to be sent.
     *
     * @note If the mimetype is specified manually, this will be sent. If no mimetype was specified, the mimetype will
     *       be detected from the file or data
     * @var string|null $this ->mimetype
     */
    protected ?string $mimetype = null;

    /**
     * The size of the file (or data) to be sent
     *
     * @var int|null
     */
    protected ?int $size = null;


    /**
     * FileResponse class constructor
     *
     * @param Stringable|string|null $source            $source
     * @param FsRestrictionsInterface|array|string|null $restrictions
     * @param Stringable|string|bool|null               $absolute_prefix
     */
    public function __construct(Stringable|string|null $source = null, FsRestrictionsInterface|array|string|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false)
    {
        parent::__construct($source, $restrictions, $absolute_prefix);
        $this->compression = Config::get('web.http.download.compression', 'auto');
    }


    /**
     *      * Returns if files will be transferred using compression
     *
     * @return bool
     */
    public function getCompression(): bool
    {
        return $this->compression;
    }


    /**
     * Sets if files are transferred using compression
     *
     * @param bool $compression
     *
     * @return FileResponse
     */
    public function setCompression(string|bool $compression): FileResponse
    {
        if (is_string($compression)) {
            if ($compression !== 'auto') {
                throw new OutOfBoundsException(tr('Unknown value ":value" specified for $compression, please use true, false, or "auto"', [
                    ':value' => $compression,
                ]));
            }
        }
        $this->compression = $compression;

        return $this;
    }


    /**
     * Returns if the process will be terminated once the HTTP file transfer has been completed
     *
     * @return bool
     */
    public function getDie(): bool
    {
        return $this->die;
    }


    /**
     * Sets if the process will be terminated once the HTTP file transfer has been completed
     *
     * @param bool $die
     *
     * @return FileResponse
     */
    public function setDie(bool $die): FileResponse
    {
        $this->die = $die;

        return $this;
    }


    /**
     * Returns if the file will be transferred as an attachment
     *
     * @return bool
     */
    public function getAttachment(): bool
    {
        return $this->attachment;
    }


    /**
     * Sets if the file is transferred as an attachment
     *
     * @param bool $attachment
     *
     * @return FileResponse
     */
    public function setAttachment(bool $attachment): FileResponse
    {
        $this->attachment = $attachment;

        return $this;
    }


    /**
     * Returns the file (locally on this server)
     *
     * @return FsFileInterface
     */
    public function getFile(): FsFileInterface
    {
        return $this->file;
    }


    /**
     * Sets the file (locally on this server)
     *
     * @param FsFileInterface $file
     *
     * @return static
     */
    public function setFile(FsFileInterface $file): static
    {
        if ($this->data) {
            throw new OutOfBoundsException(tr('Cannot set the file property, file data has already been specified'));
        }

        // Ensure the specified file is valid and readable
        FsFile::new($file, $this->restrictions)->checkReadable();

        $this->restrictions->check($file, false);

        $this->file     = $file;
        $this->size     = $file->getSize();
        $this->filename = $file->getBasename();

        return $this;
    }


    /**
     * Returns the name of the file so that the client knows with what name to save it
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }


    /**
     * Sets the name of the file so that the client knows with what name to save it
     *
     * @param string $filename
     *
     * @return static
     */
    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }


    /**
     * Returns the raw data for the file to send, in case no file on disk should be sent
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }


    /**
     * Sets the raw data for the file to send, in case no file on disk should be sent
     *
     * @param string $data
     *
     * @return static
     */
    public function setData(string $data): static
    {
        if ($this->file) {
            throw new OutOfBoundsException(tr('Cannot set the data property, a file has already been specified'));
        }
        $this->data = $data;
        $this->size = strlen($data);

        return $this;
    }


    /**
     * Sets the mimetype for the file to be sent
     *
     * @param string|null $mimetype
     *
     * @return static
     */
    public function setMimetype(?string $mimetype): static
    {
        $this->mimetype = $mimetype;

        return $this;
    }


    /**
     * Send the specified file to the client as a download using the HTTP protocol with correct headers
     *
     * @return never
     * @version 2.5.89: Rewrote function and added documentation
     */
    #[NoReturn] public function send(): never
    {
        // Send the specified file to the client
        Log::action(tr('HTTP sending file ":file" with ":bytes" bytes to client as attachment with filename ":filename"', [
            ':bytes'    => $this->size,
            ':filename' => $this->filename,
            ':file'     => $this->file,
        ]));
        if ($this->data) {
            // Send data directly from memory
            $this->mimetype = (new finfo())->buffer($this->data);
            // Configure compression, send headers and transfer contents
            $this->configureCompression();
            $this->sendHeaders();
            echo $this->data;
        } else {
            // Send a file from disk
            $this->mimetype = mime_content_type($this->file);
            // What file mode will we use?
            if ($this->isBinary()) {
                $mode = 'rb';
            } else {
                $mode = 'r';
            }
            // Configure compression, send headers
            $this->configureCompression();
            $this->sendHeaders();
            throw new UnderConstructionException('Check HTTP headers before continuing!');
//            $mimetype = mime_content_type($target);
//            $bytes    = $target->getSize();
//
//            Log::action(tr('Sending contents of file ":target" with mime-type ":type" directly to client', [
//                ':target' => $target,
//                ':type'   => $mimetype
//            ]));
//
//            header('Content-Type: ' . $mimetype);
//            header('Content-length: ' . $bytes);
//
//            include($target);
            // Open file and transfer contents
            $f = fopen($this->file, $mode);
            ob_end_clean();
            fpassthru($f);
            fclose($f);
        }
        exit();
    }


    /**
     * Checks data and configures compression setting if needed
     *
     * @return void
     */
    protected function configureCompression(): void
    {
        // Do we need compression?
        if ($this->compression === 'auto') {
            // Detect if the file is already compressed. If so, we don't need the server to try to compress the data
            // stream too because it won't do anything (possibly make it even worse)
            $this->compression = !$this->isCompressed();
        }
        if ($this->compression) {
            // Use compression
            if (is_executable('apache_setenv')) {
                apache_setenv('no-gzip', 0);
            }
            ini_set('zlib.output_compression', 'On');
        } else {
            // Do NOT use compression
            if (is_executable('apache_setenv')) {
                apache_setenv('no-gzip', 1);
            }
            ini_set('zlib.output_compression', 'Off');
        }
    }


    /**
     * Sends HTTP headers before transferring the file
     *
     * @return void
     */
    protected function sendHeaders(): void
    {
// :TODO: Are these required?
        //header('Expires: -1');
        //header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: ' . $this->mimetype);
        header('Content-length: ' . $this->size);
        if ($this->attachment) {
            // Instead of sending the file to the browser to display directly, send it as a file attachement that will
            // be downloaded to their disk
            header('Content-Disposition: attachment; filename="' . $this->filename . '"');
        }
    }


    /**
     * Download the specified single file to the specified path
     *
     * If the path is not specified then by default the function will download to the DIRECTORY_TMP directory;
     * DIRECTORY_ROOT/data/tmp
     *
     * @param string        $url      The URL of the file to be downloaded
     * @param callable|null $callback If specified, download will execute this callback with either the filename or file
     *                                contents (depending on $section)
     *
     * @return FsFileInterface|null     The path to the downloaded file or NULL if a callback was specified
     */
    public function download(string $url, callable $callback = null): FsFileInterface|null
    {
        // Set temp file and download data
        $file = FileResponse::getTemporary()->getSource();
        $data = file_get_contents($url);

        // Write data to the temp file
        file_put_contents($file, $data);

        if (!$callback) {
            return FsFile::new($file, $this->restrictions);
        }

        // Execute the callbacks before returning the data, delete the temporary file after
        $file = $callback($file);

        FsFile::new($file, $this->restrictions)->delete();

        return $file;
    }


    /**
     * If the object file is an HTTP, HTTPS, or FTP URL, then get it locally as a temp file
     *
     * @param string $url
     * @param bool   $is_downloaded
     * @param ?array $context
     *
     * @return string
     */
    public function getLocal(string $url, bool &$is_downloaded = false, ?array $context = null): string
    {
        // TODO Replace with function download();
        throw new UnderConstructionException();
        try {
            $context = $this->createStreamContext($context);
            $url     = trim($url);
            if (str_contains($url, 'http:') and str_contains($url, 'https:') and str_contains($url, 'ftp:')) {
                if (!file_exists($url)) {
                    throw new FileNotExistException(tr('Specified file ":file" does not exist', [':file' => $url]));
                }
                if (is_uploaded_file($url)) {
                    $tmp         = file_get_uploaded($url);
                    $this->files = $this->temp($url, null, false);
                    rename($tmp, $this->files);

                    return $this->files;
                }

                return $url;
            }
            // First download the file to a temporary location
            $this->files   = str_replace([
                '://',
                '/',
            ], '-', $url);
            $this->files   = $this->temp();
            $is_downloaded = true;
            $this->directory()
                 ->ensure(dirname($this->files));
            file_put_contents(file_get_contents($url, false, $context));

            return $this->files;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message = strtolower($message);
            if (str_contains($message, '404 not found')) {
                throw new FilesystemException(tr('URL ":file" does not exist', [':file' => $url]));
            }
            if (str_contains($message, '400 bad request')) {
                throw new FilesystemException(tr('URL ":file" is invalid', [':file' => $url]));
            }
            throw new FilesystemException(tr('Failed for file ":file"', [':file' => $url]), $e);
        }
    }
}
