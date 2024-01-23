<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


/**
 * Class ScanImage
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class ScanImage extends Command
{
    /**
     * Returns a list of all available hardware devices
     *
     * @return array
     */
    public function listDevices(): array
    {
        $output = $this
            ->setCommand('scanimage')
            ->addArguments(['--formatted-device-list', '%d^^^%v^^^%m^^^%t^^^%i%n'])
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
     * @return array
     */
    public function listOptions(string $device): array
    {
//        $output = $this
//            ->setCommand('scanimage')
//            ->addArguments(['--help', '--device-name', $device])
//            ->setTimeout(120)
//            ->executeReturnArray();

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
$output = Arrays::force($output, PHP_EOL);

        // Parse the output
        $skip   = true;
        $return = [];
        $entry  = [
            'category'    => null,
            'description' => ''
        ];

        foreach ($output as $line) {
            if (str_contains($line, 'Options specific to device')) {
                $skip = false;
                continue;
            }

            if ($skip) {
                continue;
            }

            if (str_contains($line, 'to get list of all options ')) {
                // We're done with the options
                break;
            }

            $line = trim($line);

            if (preg_match_all('/^([a-z]+):$/i', $line, $matches)) {
                $entry['category'] = $matches[1][0];

            } elseif (preg_match_all('/^(-.+)$/', $line, $matches)) {
                $section = $matches[1][0];

                // Parse the options line
                preg_match_all('/^(--?[a-z0-9-]+)/', $section, $matches);
                $entry['key'] = $matches[1][0];

                $section = Strings::from($section, $entry['key']);
                $section = trim($section);

                if (preg_match_all('/^([a-z ]+\|?)+/i', $section, $matches)) {
                    $entry['values'] = trim($matches[0][0]);

                    $section = Strings::from($section, $matches[0][0]);

                } elseif (preg_match_all('/^([0-9-.]+\.\.[0-9-.]+)([a-z%]+)?\s+/', $section, $matches)) {
                    $entry['range'] = $matches[1][0];
                    $entry['units'] = $matches[2][0];

                    $section = Strings::from($section, $matches[0][0]);

                } elseif (preg_match_all('/^\[=\((.+?)\)]/', $section, $matches)) {
                    $entry['values'] = trim($matches[1][0]);

                    $section = Strings::from($section, $matches[0][0]);

                } else {
                    Log::warning(tr('Unknown options section found in line ":line", ignoring', [
                        ':line' => $line
                    ]));
                }

                $section = str_replace('[advanced]', '', $section);
                $section = trim($section);

                preg_match_all('/\[(.+?)]/', $section, $matches);
                $entry['default'] = $matches[1][0];

            } else {
                $entry['description'] .= $line . ' ';
            }

            if (isset($entry['category']) and isset($entry['key']) and $entry['description']) {
                // Add the option entry, make a new entry
                $entry['description'] = trim($entry['description']);

                $return[] = $entry;

                $entry = [
                    'category'    => $entry['category'],
                    'description' => ''
                ];
            }
        }

        return $return;
    }


    /**
     * Execute the configured scan
     *
     * @param EnumExecuteMethodInterface $method
     * @return string|int|bool|array|null
     */
    public function scan(EnumExecuteMethodInterface $method = EnumExecuteMethod::noReturn): string|int|bool|array|null
    {

    }
}
