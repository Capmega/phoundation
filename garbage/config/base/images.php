<?php
/*
 * Imagemagic configuration
 */
$_CONFIG['images'] = array('viewer'      => '/usr/bin/feh',                                                          // The image viewer that should be used

                           'imagemagick' => array('convert'          => '/usr/bin/convert',                          // The location of the imagemagic "convert" command
                                                  'identify'         => '/usr/bin/identify',                         // The location of the imagemagic "identify" command
                                                  'nice'             => 11,                                          // imagemagick process "convert" nice level
                                                  'defaults'         => false,                                       // Use the following configuration options for every operation as default values or not?
                                                  'strip'            => true,                                        // Should exif information be stripped or not
                                                  'blur'             => '1x3',                                       // gaussian blur of % of image size to reduce jpeg image size
                                                  'interlace'        => 'auto-plane',                                // Type of interlace to apply, use one of none, gif, png, jpeg, line, partition, plane, empty (default, plane), auto-*. auto will use the * (* must be one of none, gif, png, jpg, plane, partition, or empty) on files > 10KB, and no interleave on files < 10KB.
                                                  'sampling_factor'  => '4:2:0',                                     // This option specifies the sampling factors to be used by the JPEG encoder for chroma downsampling. Current setting reduces the chroma channel's resolution to half, without messing with the luminance resolution that your eyes latch onto
                                                  'quality'          => 70,                                          // JPEG image quality to apply
                                                  'keep_aspectratio' => true,                                        // If set to true, if width and / or height was omitted, the image aspect ratio will be preserved while doing resizes
                                                  'defines'          => array('jpeg:dct-method=float'),              // use the more accurate floating point discrete cosine transform, rather than the default fast integer version

                                                  'limit'            => array('memory' => 32,                        // Memory limit (in MB)
                                                                              'map'    => 32)),                      // Map limit (in MB)

                           'webp'        => array('alpha_compression' => 1,                                           // Encode the alpha plane: 0 = none, 1 = compressed. Set null for default.
                                                  'alpha_filtering'   => null,                                        // Predictive filtering method for alpha plane: 0=none, 1=fast, 2=best. Set null for default.
                                                  'alpha_quality'     => null,                                        // The compression value for alpha compression between 0 and 100. Lossless compression of alpha is achieved using a value of 100, while the lower values result in a lossy compression. The default is 100. Set null for default.
                                                  'auto_filter'       => true,                                        // When enabled, the algorithm spends additional time optimizing the filtering strength to reach a well-balanced quality. Set null for default.
                                                  'emulate_jpeg_size' => true,                                        // Return a similar compression to that of JPEG but with less degradation. Set null for default.
                                                  'filter_sharpness'  => null,                                        // Filter sharpness. 0 - 100 or null for default.
                                                  'filter-strength'   => null,                                        // The strength of the deblocking filter, between 0 (no filtering) and 100 (maximum filtering). A value of 0 turns off any filtering. Higher values increase the strength of the filtering process applied after decoding the image. The higher the value, the smoother the image appears. Typical values are usually in the range of 20 to 50. Set false to use default.
                                                  'filter_type'       => 0,                                           // Filter type: 0 = simple, 1 = strong. Set null for default.
                                                  'image_hint'        => null,                                        // The hint about the image type. Possible values are default, "photo", "picture", "graph", and false to use default.
                                                  'lossless'          => false,                                       // If set to true, encode the image without any loss. Set null for default.
                                                  'low_memory'        => false,                                       // If set to true, reduce memory usage, may be at the cost of extra CPU or speed. Set null for default.
                                                  'webp_method'       => null,                                        // The compression method to use. It controls the trade off between encoding speed and the compressed file size and quality. Possible values range from 0 to 6. Default value is 4. When higher values are utilized, the encoder spends more time inspecting additional encoding possibilities and decide on the quality gain. Lower value might result in faster processing time at the expense of larger file size and lower compression quality. Set null for default.
                                                  'preprocessing'     => null,                                        // Choose from: 0=none, 1=segment-smooth, 2=pseudo-random dithering or null for default.
                                                  'partitions'        => null,                                        // Progressive decoding: choose 0 to 3. Set null for default.
                                                  'partition_limit'   => null,                                        // Choose 0 for no quality degradation and 100 for maximum degradation. Set null for default.
                                                  'pass'              => null,                                        // Maximum number of passes to target compression size or PSNR. Set null for default.
                                                  'segment'           => null,                                        // Choose from 1 to 4, the maximum number of segments to use. Set null for default.
                                                  'show_compressed'   => null,                                        // If set true.... ? Set null for default.
                                                  'sns_strength'      => '',                                          // The amplitude of the spatial noise shaping. Spatial noise shaping (SNS) refers to a general collection of built-in algorithms used to decide which area of the picture should use relatively less bits, and where else to better transfer these bits. The possible range goes from 0 (algorithm is off) to 100 (the maximal effect). The default value is 80. Set null for default.
                                                  'target_size'       => null,                                        // Desired target size in % from the original. Set null to ignore.
                                                  'target_psnr'       => 0,                                           // Desired minimal distortion. Set null for default.
                                                  'thread_level'      => true,                                        // Enable / disable multi-threaded encoding. Set null for default.
                                                  'use_sharp_yuv'     => false));                                     // If needed, use sharp (and slow) RGB->YUV conversion. Set null for default.
