<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package fonts
 * @see https://alternativeto.net/software/font-awesome/
 * @see https://sleeklogos.design/web-app/new-icons/all Sleep logos
 * @see https://fonts.google.com/
 */



/*
 * Verify that the specified provider exist. If no provider was specified, return a list of all available providers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package fonts
 * @version 2.0.5: Added function and documentation
 *
 * @param null string $provider
 * @return mixed If a provider was specified and exist, that provider will be returned. If no provider was specified, an array containing all supported providers will be returned
 */
function fonts_providers($provider = null){
    static $providers = array('fontawesome'       => true,
                              'themify'           => true,
                              'fontello'          => false,
                              'typicons'          => false,
                              'foundation'        => false,
                              'pictonic'          => false,
                              'modern'            => false,
                              'iconfarm'          => false,
                              'icons8'            => false,
                              'lineawesome'       => false,
                              'picons'            => false,
                              'noun'              => false,
                              'iconarchive'       => false,
                              'fontsforweb'       => false,
                              'adobeedge'         => false,
                              'googlefonts'       => false,
                              'fontsquirrel'      => false,
                              'openfont'          => false,
                              'nucleo'            => false,
                              'illustrio'         => false,
                              'iconcrafts'        => false,
                              'vicons'            => false,
                              'swifticons'        => false,
                              'futuramo'          => false,
                              'iconpacks'         => false,
                              'font2web'          => false,
                              'glyphicons'        => false,
                              'shifticons'        => false,
                              'sleeklogos'        => false,
                              'toicon'            => false,
                              'twfonts'           => false,
                              'vectoriconbox'     => false,
                              'smashicons'        => false,
                              'kwippe'            => false,
                              'iconmonstr'        => false,
                              'iconspedia'        => false,
                              'graphicriver'      => false,
                              'icomoon'           => false,
                              'motosha'           => false,
                              'gameicons'         => false,
                              'fontastic'         => false,
                              'iconscout'         => false,
                              'findicons'         => false,
                              'pngify'            => false,
                              'iconsdb'           => false,
                              'iconsfind'         => false,
                              'icongallery'       => false,
                              'easyiconfinder'    => false,
                              'seanau'            => false,
                              'iconseeker'        => false,
                              'fontspace'         => false,
                              'iconssearch'       => false,
                              'typecatcher'       => false,
                              'fontscom'          => false,
                              'brick'             => false,
                              'fontfacegenerator' => false,
                              'veryicon'          => false,
                              'fontdaddy'         => false,
                              'fontsadda'         => false,
                              'kernest'           => false,
                              'webink'            => false,
                              'fontfont'          => false,
                              'fontdeck'          => false,
                              'typefonts'         => false,
                              'typotheque'        => false,
                              'dafont'            => false,
                              'befonts'           => false,
                              'adobefonts'        => false,
                              'webtype'           => false,
                              'fontsrepo'         => false,
                              'getfonts'          => false,
                              'ffonts'            => false,
                              'weeklyfonts'       => false,
                              'fontstore'         => false,
                              'redfonts'          => false,
                              'fontmeme'          => false,
                              'fonts4free'        => false,
                              'betterfontfinder'  => false,
                              'fontviewer'        => false);

    try{
        if($provider){
            if(empty($providers[$provider])){
                throw new CoreException(tr('fonts_providers(): Unknown provider ":provider" specified'), 'unknown');
            }

            return $provider;
        }

        return $providers;

    }catch(Exception $e){
        throw new CoreException('fonts_providers(): Failed', $e);
    }
}



/*
 * Convert ufpdf font to normal ttf font file
 */
function fonts_convert_ufpdf($params){
    try{
under_construction();
        array_ensure($params);
        ensure_key($params, 'font'	 , '');
        ensure_key($params, 'unicode', true);

        /*
         * If multiple fonts have been specified, handle them one by one
         */
        if(is_array($params['font'])){
            foreach($params['font'] as $font){
                $params['font'] = $font;
                fonts_convert_ufpdf($params);
            }

            return true;
        }

        /*
         * If no font was specified we can't continue.
         */
        if(!$params['font']){
            throw(BException(zxc('fonts_convert_ufpdf(): No font specified'), 'fonts'));
        }

        /*
         * Load needed libraries
         */
        lib_load('shell', 'fork,mv,cp');

        if($params['unicode'])	lib_load_ext('ufpdf', 'tools/makefontuni');
        else					lib_load_ext('fpdf' , 'tools/makefontuni');

        /*
         * If a font file was specified, then remove file data
         */
        if(strpos($params['font'], '.ttf')){
            $params['font'] = basename($params['font'], '.ttf');
        }

        /*
         * Create the font file with unicode extension
         */
        if($params['unicode']) sh_cp($kernel->config('paths', 'var').'fonts/'.$params['font'].'.ttf', $kernel->config('paths', 'var').'fonts/'.$params['font'].'_uni.ttf');

        /*
         * Convert ttf font file
         */
        sh_fork('usr/bin/ttf2pt1u', array('-a', '-Ob', $kernel->config('paths', 'var').'fonts/'.$params['font'].($params['unicode'] ? '_uni' : '').'.ttf'));

        /*
         * Run PHP from PHP, okay, we definately can do better than this!! :)
         */
        MakeFont($kernel->config('paths', 'var').'fonts/'.$params['font'].($params['unicode'] ? '_uni' : '').'.ttf', $kernel->config('paths', 'var').'fonts/'.$params['font'].($params['unicode'] ? '_uni' : '').'.ufm');

        /*
         * Move the output files from MakeFont to the fonts directory
         */
        sh_mv($params['font'].($params['unicode'] ? '_uni' : '').'*', $kernel->config('paths', 'var').'fonts/', false);

    }catch(Exception $e){
        throw new CoreException('fonts_convert_ufpdf(): Failed', $e);
    }
}
?>
