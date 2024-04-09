<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataBatch;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Plugins\Hardware\Devices\Interfaces\ProfileInterface;
use Plugins\Scanners\Exception\ScannersException;

/**
 * Class ScanImage
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class ScanImage extends Command
{
    use TraitDataBatch;

    /**
     * The command line options used to scan
     *
     * @var array|null $options
     */
    protected ?array $options = null;

    /**
     * The profile with which to scan
     *
     * @var ProfileInterface $profile
     */
    protected ProfileInterface $profile;


    /**
     * Returns a list of all available hardware devices
     *
     * @return array
     */
    public function listDevices(): array
    {
        $output = $this->setCommand('scanimage')
                       ->addArguments([
                           '--formatted-device-list',
                           '%d^^^%v^^^%m^^^%t^^^%i%n',
                       ])
                       ->setTimeout(120)
                       ->executeReturnArray();
        // Parse the output
        $return = [];
        foreach ($output as $line) {
            $line = explode('^^^', $line);
            $return[$line[0]] = [
                'device' => $line[0],
                'vendor' => $line[1],
                'model'  => $line[2],
                'type'   => $line[3],
                'index'  => $line[4],
            ];
        }

        return $return;
    }


    /**
     * Returns a list of all available hardware devices
     *
     * @param string $device
     *
     * @return array
     */
    public function listOptions(string $device): array
    {
        // Load options and parse the output
        $output = $this->loadOptions($device);
        $return = [];
        $entry  = [
            'category'    => null,
            'description' => '',
        ];
        // Parse scanimage options output
        foreach ($output as $line) {
            if (preg_match_all('/^([a-z]+):$/i', $line, $matches)) {
                // This is a category
                $entry['category'] = $matches[1][0];

            } elseif (preg_match_all('/^(-.+)$/', $line, $matches)) {
                // This is a key. If a key was already specified, add it first
                if (isset($entry['key']) and $entry['description']) {
                    // Add the option entry, make a new entry
                    $entry['description'] = trim($entry['description']);
                    $return[] = $entry;
                    $entry = [
                        'category'    => $entry['category'],
                        'description' => '',
                    ];
                }
                $section = $matches[1][0];
                // Parse the options line, first get the key
                preg_match_all('/^(--?[a-z0-9-]+)/', $section, $matches);
                $entry['key'] = $matches[1][0];
                // Remove the key from the section we're working on
                $section = Strings::from($section, $entry['key']);
                $section = trim($section);
                if (preg_match_all('/^([0-9-.]+\.\.[0-9-.]+)([a-z%]+)?\s+/', $section, $matches)) {
                    // This contains a range
                    $entry['range'] = str_replace('..', '...', $matches[1][0]);
                    $entry['units'] = $matches[2][0];
                    $section = Strings::from($section, $matches[0][0]);

                } elseif (preg_match_all('/^\[=\((.+?)\)]/', $section, $matches)) {
                    // This option contains a list of possible values
                    $entry['values'] = str_replace('|', ',', trim($matches[1][0]));
                    $section = Strings::from($section, $matches[0][0]);

                } elseif (preg_match_all('/^([0-9a-z ]+\|?)+/i', $section, $matches)) {
                    // This option contains for list of values
                    $entry['values'] = explode('|', trim($matches[0][0]));
                    foreach ($entry['values'] as &$value) {
                        // Values must be EITHER numeric OR alphanumeric, not both
                        if (!preg_match('/^[0-9.]+$/', $value) and !preg_match('/^[a-z ]+$/i', $value)) {
                            // Okay, they are both, maybe a number with units? If so, split!
                            if (preg_match_all('/^(\d+)\s?(\w+)$/i', $value, $matches)) {
                                $value          = $matches[1][0];
                                $entry['units'] = not_empty($matches[2][0], isset_get($entry['units']));

                            } else {
                                // Wut?
                                $value = null;
                                Log::warning(tr('Failed to parse options section found in line ":line", ignoring', [
                                    ':line' => $line,
                                ]));
                            }
                        }
                    }
                    unset($value);
                    $entry['values'] = implode(',', $entry['values']);
                    $section         = Strings::from($section, $matches[0][0]);

                } else {
                    Log::warning(tr('Unknown options section found in line ":line", ignoring', [
                        ':line' => $line,
                    ]));
                }
                // Remove "advanced" indicator, we don't care
                $section = str_replace('[advanced]', '', $section);
                $section = trim($section);
                // Get comments
                if (preg_match_all('/\(([a-z0-9-_ .,]+)\)/', $section, $matches)) {
                    $entry['comments'] = $matches[1][0];
                }
                // Get default
                preg_match_all('/\[(.+?)]/', $section, $matches);
                $entry['default'] = $matches[1][0];

            } else {
                // Add description line (can be multiple)
                $entry['description'] .= $line . ' ';
            }
        }
        // We may have a last entry left, add it too
        if (isset($entry['key']) and $entry['description']) {
            // Add the option entry, make a new entry
            $entry['description'] = trim($entry['description']);
            $return[]             = $entry;
        }

        return $return;
    }


    /**
     * Loads the options through the scanimage command
     *
     * @param string $device
     * @param int    $tries
     *
     * @return array
     */
    protected function loadOptions(string $device, int $tries = 5): array
    {
        // Try reading the device information multiple times as scanimage is rather finicky
        while (--$tries > 0) {
            $skip   = true;
            $return = [];
            if (TEST) {
                // Get test options
                $output = $this->getTestOptions();

            } else {
                // Get real options
                $output = $this->setCommand('scanimage')
                               ->addArguments([
                                   '--help',
                                   '--device-name',
                                   $device,
                               ])
                               ->setTimeout(120)
                               ->executeReturnArray();
            }
            // Pre-parse output
            foreach ($output as $line) {
                $line = trim($line);
                if (!$line) {
                    continue;
                }
                if (str_contains($line, 'failed: Invalid argument')) {
                    Log::warning(tr('Failed to find device ":device", retrying as "scanimage" sometimes fails to find the device', [
                        ':device' => $device,
                    ]), 4);
                    break;
                }
                if (str_contains($line, 'Options specific to device')) {
                    // Here we start with device options
                    $skip = false;
                    continue;
                }
                if (str_contains($line, 'to get list of all options for DEVICE.')) {
                    // Done!
                    break;
                }
                if ($skip) {
                    continue;
                }
                $return[] = $line;
            }
            if (!$skip) {
                return $return;
            }
        }
        throw new ScannersException(tr('Failed to load device options for scanner device ":device", it may not exist', [
            ':device' => $device,
        ]));
    }


    /**
     * Returns test device options
     *
     * @return array
     */
    protected function getTestOptions(): array
    {
        $output = "Usage: scanimage [OPTION]...

        Start image acquisition on a scanner device and write image data to
        standard output.

        Parameters are separated by a blank from single-character options (e.g.
        -d epson) and by a = from multi-character options (e.g. --device-name=epson).
        -d, --device-name=DEVICE   use a given scanner device (e.g. hp:/dev/scanner)
            --format=pnm|tiff|png|jpeg|pdf  file format of output file
        -i, --icc-profile=PROFILE  include this ICC profile into TIFF file
        -L, --list-devices         show available scanner devices
        -f, --formatted-device-list=FORMAT similar to -L, but the FORMAT of the output
                                   can be specified: %d (device name), %v (vendor),
                                   %m (model), %t (type), %i (index number), and
                                   %n (newline)
        -b, --batch[=FORMAT]       working in batch mode, FORMAT is `out%d.pnm' `out%d.tif'
                                   `out%d.png' or `out%d.jpg' by default depending on --format
                                   This option is incompatible with --output-file.    --batch-start=#        page number to start naming files with
            --batch-count=#        how many pages to scan in batch mode
            --batch-increment=#    increase page number in filename by #
            --batch-double         increment page number by two, same as
                                   --batch-increment=2
            --batch-print          print image filenames to stdout
            --batch-prompt         ask for pressing a key before scanning a page
            --accept-md5-only      only accept authorization requests using md5
        -p, --progress             print progress messages
        -o, --output-file=PATH     save output to the given file instead of stdout.
                                   This option is incompatible with --batch.
        -n, --dont-scan            only set options, don't actually scan
        -T, --test                 test backend thoroughly
        -A, --all-options          list all available backend options
        -h, --help                 display this help message and exit
        -v, --verbose              give even more status messages
        -B, --buffer-size=#        change input buffer size (in kB, default 32)
        -V, --version              print version information
        Output format is not set, using pnm as a default.

        Options specific to device `fujitsu:ScanSnap iX1400:136133':
          Standard:
            --source ADF Front|ADF Back|ADF Duplex [ADF Front]
                Selects the scan source (such as a document-feeder).
            --mode Lineart|Halftone|Gray|Color [Lineart]
                Selects the scan mode (e.g., lineart, monochrome, or color).
            --resolution 50..600dpi (in steps of 1) [600]
                Sets the resolution of the scanned image.
          Geometry:
            --page-width 0..221.121mm (in steps of 0.0211639) [215.872]
                Specifies the width of the media.  Required for automatic centering of
                sheet-fed scans.
            --page-height 0..3012.9mm (in steps of 0.0211639) [279.364]
                Specifies the height of the media.
            -l 0..215.872mm (in steps of 0.0211639) [0]
                Top-left x position of scan area.
            -t 0..279.364mm (in steps of 0.0211639) [0]
                Top-left y position of scan area.
            -x 0..215.872mm (in steps of 0.0211639) [215.872]
                Width of scan-area.
            -y 0..279.364mm (in steps of 0.0211639) [279.364]
                Height of scan-area.
          Enhancement:
            --brightness -127..127 (in steps of 1) [0]
                Controls the brightness of the acquired image.
            --contrast -127..127 (in steps of 1) [0]
                Controls the contrast of the acquired image.
            --threshold 0..255 (in steps of 1) [0]
                Select minimum-brightness to get a white point
            --rif[=(yes|no)] [no]
                Reverse image format
            --ht-type Default|Dither|Diffusion [inactive]
                Control type of halftone filter
            --ht-pattern 0..3 (in steps of 1) [inactive]
                Control pattern of halftone filter
            --emphasis -128..127 (in steps of 1) [0]
                Negative to smooth or positive to sharpen image
            --variance 0..255 (in steps of 1) [0]
                Set SDTC variance rate (sensitivity), 0 equals 127
          Advanced:
            --ald[=(yes|no)] [no] [advanced]
                Scanner detects paper lower edge. May confuse some frontends.
            --df-action Default|Continue|Stop [Default] [advanced]
                Action following double feed error
            --df-skew[=(yes|no)] [inactive]
                Enable double feed error due to skew
            --df-thickness[=(yes|no)] [inactive]
                Enable double feed error due to paper thickness
            --df-length[=(yes|no)] [inactive]
                Enable double feed error due to paper length
            --df-diff Default|10mm|15mm|20mm [inactive]
                Difference in page length to trigger double feed error
            --bgcolor Default|White|Black [Default] [advanced]
                Set color of background for scans. May conflict with overscan option
            --dropoutcolor Default|Red|Green|Blue [Default] [advanced]
                One-pass scanners use only one color during gray or binary scanning,
                useful for colored paper or ink
            --buffermode Default|Off|On [Off] [advanced]
                Request scanner to read pages quickly from ADF into internal memory
            --prepick Default|Off|On [Default] [advanced]
                Request scanner to grab next page from ADF
            --overscan Default|Off|On [Default] [advanced]
                Collect a few mm of background on top side of scan, before paper
                enters ADF, and increase maximum scan area beyond paper size, to allow
                collection on remaining sides. May conflict with bgcolor option
            --sleeptimer 0..60 (in steps of 1) [0] [advanced]
                Time in minutes until the internal power supply switches to sleep mode
            --offtimer 0..960 (in steps of 1) [240] [advanced]
                Time in minutes until the internal power supply switches the scanner
                off. Will be rounded to nearest 15 minutes. Zero means never power off.
            --lowmemory[=(yes|no)] [no] [advanced]
                Limit driver memory usage for use in embedded systems. Causes some
                duplex transfers to alternate sides on each call to sane_read. Value of
                option 'side' can be used to determine correct image. This option
                should only be used with custom front-end software.
            --swdeskew[=(yes|no)] [no] [advanced]
                Request driver to rotate skewed pages digitally.
            --swdespeck 0..9 (in steps of 1) [0]
                Maximum diameter of lone dots to remove from scan.
            --swcrop[=(yes|no)] [no] [advanced]
                Request driver to remove border from pages digitally.
            --swskip 0..100% (in steps of 0.100006) [0]
                Request driver to discard pages with low percentage of dark pixels
          Sensors:";

        return Arrays::force($output, PHP_EOL);
    }


    /**
     * Returns the profile used to scan images
     *
     * @param ProfileInterface $profile
     *
     * @return ProfileInterface|null
     */
    public function getProfile(ProfileInterface $profile): ?ProfileInterface
    {
        return $this->profile;
    }


    /**
     * Applies the specified scanner profile to this ScanImage object so that the scan() call knows what to do
     *
     * @param ProfileInterface $profile
     *
     * @return $this
     */
    public function setProfile(ProfileInterface $profile): static
    {
        $this->profile = $profile;

        return $this;
    }


    /**
     * Returns the command line options
     *
     * @return array|null
     */
    public function getCommandLineOptions(): ?array
    {
        return $this->options;
    }


    /**
     * Sets  the command line options directly instead of through a profile
     *
     *
     * @param array|null $options
     *
     * @return ScanImage
     */
    public function setCommandLineOptions(?array $options): static
    {
        $this->options = $options;

        return $this;
    }


    /**
     * Applies the specified profile
     *
     * @param ProfileInterface $profile
     *
     * @return $this
     */
    public function applyProfile(ProfileInterface $profile): static
    {
        $this->options = [];
        foreach ($profile->getOptions() as $option) {
            if (!$option->get()) {
                // Only apply options that have a value
                continue;
            }
            $this->options[] = $option->getKey();
            $this->options[] = $option->get() . $option->getUnits();
        }
        $this->profile = $profile;

        return $this;
    }


    /**
     * Execute the configured scan
     *
     * @param string                     $path
     * @param EnumExecuteMethodInterface $method
     *
     * @return static
     */
    public function scan(string $path, EnumExecuteMethodInterface $method = EnumExecuteMethod::noReturn): static
    {
        if (empty($this->profile)) {
            throw new ScannersException(tr('Cannot execute document scan, no profile specified'));
        }
        $this->setCommand('scanimage')
             ->addArguments([
                 '-d',
                 $this->profile->getDevice()
                               ->getUrl(),
             ])
             ->addArguments($this->options)
             ->addArguments($this->batch ? ['--batch=' . $path] : [
                 '-o',
                 $path,
             ])
             ->setTimeout(120)
             ->execute($method);

        return $this;
    }
}
