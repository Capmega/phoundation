<?php
/*
 * Editors library configuration
 * tinymce jbimages plugin configuration
 */
$_CONFIG['editors']            = array('imageupload'                        => 'session',                                                   // "all" or "session" or "admin",

                                       'images'                             => array('url'                => '/images',                     // Base URL that jbimiages will give to tinymce for all images inserted into the document
                                                                                     'allowed_types'      => 'gif|jpg|png',                 // What file extensions will be recognized by jbimages as being an image
                                                                                     'max_size'           => 0,                             //
                                                                                     'max_width'          => 0,                             //
                                                                                     'max_height'         => 0,                             //
                                                                                     'allow_resize'       => false,                         //
                                                                                     'overwrite'          => false,                         // If set to true, if images names already exist when a new images is being uploaded, it will be overwritten. If set to false, the new image will be assigned a number behind the basename (before the extension) to make it unique
                                                                                     'encrypt_name'       => false));                       // Should filenames retain their original name (false) or should jbimages give it a random character name (true)?
?>