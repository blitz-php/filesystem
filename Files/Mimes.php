<?php

/**
 * This file is part of Blitz PHP framework - Filesystem.
 *
 * (c) 2023 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Filesystem\Files;

/**
 * Mimes
 *
 * Ce fichier contient un tableau de types MIME.
 * Il est utilisé par la classe Upload pour aider à identifier les types de fichiers autorisés.
 *
 * Lorsqu'il existe plusieurs variantes pour une extension (comme jpg, jpeg, etc.)
 * le plus courant doit être le premier dans le tableau pour faciliter les méthodes de devinettes.
 * Il en va de même lorsqu'il existe plus d'un type mime pour une même extension.
 *
 * Lorsque vous travaillez avec des types mime, assurez-vous que l'extension "fileinfo" est activée pour détecter de manière fiable les types de média.
 *
 * @credit <a href="http://codeigniter.com">CodeIgniter 4 - \Config\Mimes</a>
 */
class Mimes
{
    /**
     * Clés de type pour les mappages de type mime pour les types mime connus.
     */
    public const MAP = [
        '3g2' => 'video/3gpp2',
        '3gp' => [
            'video/3gp',
            'video/3gpp',
        ],
        '7z'   => 'application/x-7z-compressed',
        '7zip' => [
            'application/x-compressed',
            'application/x-zip-compressed',
            'application/zip',
            'multipart/x-zip',
        ],
        'aac' => 'audio/x-acc',
        'ac3' => 'audio/ac3',
        'ai'  => [
            'application/pdf',
            'application/postscript',
        ],
        'aif' => [
            'audio/x-aiff',
            'audio/aiff',
        ],
        'aifc' => 'audio/x-aiff',
        'aiff' => [
            'audio/x-aiff',
            'audio/aiff',
        ],
        'ajax'     => 'text/html',
        'amf'      => 'application/x-amf',
        'appcache' => 'text/cache-manifest',
        'asc'      => 'text/plain',
        'atom'     => 'application/atom+xml',
        'au'       => 'audio/x-au',
        'avi'      => [
            'video/x-msvideo',
            'video/msvideo',
            'video/avi',
            'application/x-troff-msvideo',
        ],
        'bcpio' => 'application/x-bcpio',
        'bin'   => [
            'application/macbinary',
            'application/mac-binary',
            'application/octet-stream',
            'application/x-binary',
            'application/x-macbinary',
        ],
        'bmp' => [
            'image/bmp',
            'image/x-bmp',
            'image/x-bitmap',
            'image/x-xbitmap',
            'image/x-win-bitmap',
            'image/x-windows-bmp',
            'image/ms-bmp',
            'image/x-ms-bmp',
            'application/bmp',
            'application/x-bmp',
            'application/x-win-bitmap',
        ],
        'bz2'  => 'application/x-bzip',
        'c'    => 'text/plain',
        'cc'   => 'text/plain',
        'ccad' => 'application/clariscad',
        'cdf'  => 'application/x-netcdf',
        'cdr'  => [
            'application/cdr',
            'application/coreldraw',
            'application/x-cdr',
            'application/x-coreldraw',
            'image/cdr',
            'image/x-cdr',
            'zz-application/zz-winassoc-cdr',
        ],
        'cer' => [
            'application/pkix-cert',
            'application/x-x509-ca-cert',
        ],
        'class' => 'application/octet-stream',
        'cpio'  => 'application/x-cpio',
        'cpt'   => 'application/mac-compactpro',
        'crl'   => [
            'application/pkix-crl',
            'application/pkcs-crl',
        ],
        'crt' => [
            'application/x-x509-ca-cert',
            'application/x-x509-user-cert',
            'application/pkix-cert',
        ],
        'crx' => 'application/x-chrome-extension',
        'csh' => 'application/x-csh',
        'csr' => 'application/octet-stream',
        'css' => [
            'text/css',
            'text/plain',
        ],
        'csv' => [
            'text/csv',
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'application/vnd.ms-excel',
            'application/x-csv',
            'text/x-csv',
            'application/csv',
            'application/excel',
            'application/vnd.msexcel',
            'text/plain',
        ],
        'dcr' => 'application/x-director',
        'der' => 'application/x-x509-ca-cert',
        'dir' => 'application/x-director',
        'dll' => 'application/octet-stream',
        'dms' => 'application/octet-stream',
        'doc' => [
            'application/msword',
            'application/vnd.ms-office',
        ],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/msword',
            'application/x-zip',
        ],
        'dot' => [
            'application/msword',
            'application/vnd.ms-office',
        ],
        'dotx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/msword',
        ],
        'drw' => 'application/drafting',
        'dvi' => 'application/x-dvi',
        'dwg' => 'application/acad',
        'dxf' => 'application/dxf',
        'dxr' => 'application/x-director',
        'eml' => 'message/rfc822',
        'eot' => 'application/vnd.ms-fontobject',
        'eps' => 'application/postscript',
        'etx' => 'text/x-setext',
        'exe' => [
            'application/octet-stream',
            'application/vnd.microsoft.portable-executable',
            'application/x-dosexec',
            'application/x-msdownload',
        ],
        'ez'  => 'application/andrew-inset',
        'f'   => 'text/plain',
        'f4a' => 'audio/mp4',
        'f4b' => 'audio/mp4',
        'f4p' => 'video/mp4',
        'f4v' => [
            'video/mp4',
            'video/x-f4v',
        ],
        'f90'  => 'text/plain',
        'file' => 'multipart/form-data',
        'flac' => 'audio/x-flac',
        'fli'  => 'video/x-fli',
        'flv'  => 'video/x-flv',
        'form' => 'application/x-www-form-urlencoded',
        'gif'  => 'image/gif',
        'gpg'  => 'application/gpg-keys',
        'gtar' => 'application/x-gtar',
        'gz'   => 'application/x-gzip',
        'gzip' => 'application/x-gzip',
        'h'    => 'text/plain',
        'hal'  => [
            'application/hal+xml',
            'application/vnd.hal+xml',
        ],
        'haljson' => [
            'application/hal+json',
            'application/vnd.hal+json',
        ],
        'halxml' => [
            'application/hal+xml',
            'application/vnd.hal+xml',
        ],
        'hdf' => 'application/x-hdf',
        'hh'  => 'text/plain',
        'hqx' => [
            'application/mac-binhex40',
            'application/mac-binhex',
            'application/x-binhex40',
            'application/x-mac-binhex40',
        ],
        'htc' => 'text/x-component',
        'htm' => [
            'text/html',
            'text/plain',
            '*/*',
        ],
        'html' => [
            'text/html',
            'text/plain',
            '*/*',
        ],
        'ical' => 'text/calendar',
        'ice'  => 'x-conference/x-cooltalk',
        'ico'  => [
            'image/x-icon',
            'image/x-ico',
            'image/vnd.microsoft.icon',
        ],
        'ics'  => 'text/calendar',
        'ief'  => 'image/ief',
        'iges' => 'model/iges',
        'igs'  => 'model/iges',
        'ips'  => 'application/x-ipscript',
        'ipx'  => 'application/x-ipix',
        'j2k'  => [
            'image/jp2',
            'video/mj2',
            'image/jpx',
            'image/jpm',
        ],
        'jar' => [
            'application/java-archive',
            'application/x-java-application',
            'application/x-jar',
            'application/x-compressed',
        ],
        'javascript' => 'application/javascript',
        'jp2'        => [
            'image/jp2',
            'video/mj2',
            'image/jpx',
            'image/jpm',
        ],
        'jpe' => [
            'image/jpeg',
            'image/pjpeg',
        ],
        'jpeg' => [
            'image/jpeg',
            'image/pjpeg',
        ],
        'jpf' => [
            'image/jp2',
            'video/mj2',
            'image/jpx',
            'image/jpm',
        ],
        'jpg' => [
            'image/jpeg',
            'image/pjpeg',
        ],
        'jpg2' => [
            'image/jp2',
            'video/mj2',
            'image/jpx',
            'image/jpm',
        ],
        'jpm' => [
            'image/jp2',
            'video/mj2',
            'image/jpx',
            'image/jpm',
        ],
        'jpx' => [
            'image/jp2',
            'video/mj2',
            'image/jpx',
            'image/jpm',
        ],
        'js' => [
            'application/x-javascript',
            'text/plain',
        ],
        'json' => [
            'application/json',
            'text/json',
        ],
        'jsonapi' => 'application/vnd.api+json',
        'jsonld'  => 'application/ld+json',
        'kar'     => 'audio/midi',
        'kdb'     => 'application/octet-stream',
        'kml'     => [
            'application/vnd.google-earth.kml+xml',
            'application/xml',
            'text/xml',
        ],
        'kmz' => [
            'application/vnd.google-earth.kmz',
            'application/zip',
            'application/x-zip',
        ],
        'latex' => 'application/x-latex',
        'lha'   => 'application/octet-stream',
        'log'   => [
            'text/plain',
            'text/x-log',
        ],
        'lsp'      => 'application/x-lisp',
        'lzh'      => 'application/octet-stream',
        'm'        => 'text/plain',
        'm3u'      => 'text/plain',
        'm4a'      => 'audio/x-m4a',
        'm4u'      => 'application/vnd.mpegurl',
        'm4v'      => 'video/mp4',
        'man'      => 'application/x-troff-man',
        'manifest' => 'text/cache-manifest',
        'me'       => 'application/x-troff-me',
        'mesh'     => 'model/mesh',
        'mid'      => 'audio/midi',
        'midi'     => 'audio/midi',
        'mif'      => 'application/vnd.mif',
        'mime'     => 'www/mime',
        'mj2'      => [
            'image/jp2',
            'video/mj2',
            'image/jpx',
            'image/jpm',
        ],
        'mjp2' => [
            'image/jp2',
            'video/mj2',
            'image/jpx',
            'image/jpm',
        ],
        'mkv'   => 'video/x-matroska',
        'mov'   => 'video/quicktime',
        'movie' => 'video/x-sgi-movie',
        'mp2'   => 'audio/mpeg',
        'mp3'   => [
            'audio/mpeg',
            'audio/mpg',
            'audio/mpeg3',
            'audio/mp3',
        ],
        'mp4'  => 'video/mp4',
        'mpe'  => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg'  => 'video/mpeg',
        'mpga' => 'audio/mpeg',
        'ms'   => 'application/x-troff-ms',
        'msh'  => 'model/mesh',
        'nc'   => 'application/x-netcdf',
        'oda'  => 'application/oda',
        'oex'  => 'application/x-opera-extension',
        'oga'  => 'audio/ogg',
        'ogg'  => [
            'audio/ogg',
            'video/ogg',
            'application/ogg',
        ],
        'ogv' => 'video/ogg',
        'otf' => 'font/otf',
        'p10' => [
            'application/x-pkcs10',
            'application/pkcs10',
        ],
        'p12' => 'application/x-pkcs12',
        'p7a' => 'application/x-pkcs7-signature',
        'p7c' => [
            'application/pkcs7-mime',
            'application/x-pkcs7-mime',
        ],
        'p7m' => [
            'application/pkcs7-mime',
            'application/x-pkcs7-mime',
        ],
        'p7r' => 'application/x-pkcs7-certreqresp',
        'p7s' => 'application/pkcs7-signature',
        'pbm' => 'image/x-portable-bitmap',
        'pdb' => 'chemical/x-pdb',
        'pdf' => [
            'application/pdf',
            'application/force-download',
            'application/x-download',
        ],
        'pem' => [
            'application/x-x509-user-cert',
            'application/x-pem-file',
            'application/octet-stream',
        ],
        'pgm' => 'image/x-portable-graymap',
        'pgn' => 'application/x-chess-pgn',
        'pgp' => 'application/pgp',
        'php' => [
            'application/x-php',
            'application/x-httpd-php',
            'application/php',
            'text/php',
            'text/x-php',
            'application/x-httpd-php-source',
        ],
        'php3'   => 'application/x-httpd-php',
        'php4'   => 'application/x-httpd-php',
        'phps'   => 'application/x-httpd-php-source',
        'phtml'  => 'application/x-httpd-php',
        'pkpass' => 'application/vnd.apple.pkpass',
        'png'    => [
            'image/png',
            'image/x-png',
        ],
        'pnm' => 'image/x-portable-anymap',
        'pot' => 'application/vnd.ms-powerpoint',
        'ppm' => 'image/x-portable-pixmap',
        'pps' => 'application/vnd.ms-powerpoint',
        'ppt' => [
            'application/vnd.ms-powerpoint',
            'application/powerpoint',
            'application/vnd.ms-office',
            'application/msword',
        ],
        'pptx' => [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ],
        'ppz' => 'application/vnd.ms-powerpoint',
        'pre' => 'application/x-freelance',
        'prt' => 'application/pro_eng',
        'ps'  => 'application/postscript',
        'psd' => [
            'application/photoshop',
            'application/psd',
            'image/psd',
            'image/x-photoshop',
            'image/photoshop',
            'zz-application/zz-winassoc-psd',
            'application/x-photoshop',
            'image/vnd.adobe.photoshop',
        ],
        'qt'  => 'video/quicktime',
        'ra'  => 'audio/x-realaudio',
        'ram' => 'audio/x-pn-realaudio',
        'rar' => [
            'application/vnd.rar',
            'application/x-rar',
            'application/rar',
            'application/x-rar-compressed',
        ],
        'ras'        => 'image/cmu-raster',
        'rdf'        => 'application/xml',
        'rgb'        => 'image/x-rgb',
        'rm'         => 'audio/x-pn-realaudio',
        'roff'       => 'application/x-troff',
        'rpm'        => 'audio/x-pn-realaudio-plugin',
        'rsa'        => 'application/x-pkcs7',
        'rss'        => 'application/rss+xml',
        'rtf'        => 'text/rtf',
        'rtx'        => 'text/richtext',
        'rv'         => 'video/vnd.rn-realvideo',
        'safariextz' => 'application/octet-stream',
        'scm'        => 'application/x-lotusscreencam',
        'sea'        => 'application/octet-stream',
        'set'        => 'application/set',
        'sgm'        => 'text/sgml',
        'sgml'       => 'text/sgml',
        'sh'         => 'application/x-sh',
        'shar'       => 'application/x-shar',
        'shtml'      => [
            'text/html',
            'text/plain',
        ],
        'silo' => 'model/mesh',
        'sit'  => 'application/x-stuffit',
        'skd'  => 'application/x-koan',
        'skm'  => 'application/x-koan',
        'skp'  => 'application/x-koan',
        'skt'  => 'application/x-koan',
        'smi'  => 'application/smil',
        'smil' => 'application/smil',
        'snd'  => 'audio/basic',
        'so'   => 'application/octet-stream',
        'sol'  => 'application/solids',
        'spl'  => 'application/x-futuresplash',
        'spx'  => 'audio/ogg',
        'src'  => 'application/x-wais-source',
        'srt'  => [
            'text/srt',
            'text/plain',
        ],
        'sst'  => 'application/octet-stream',
        'step' => 'application/STEP',
        'stl'  => [
            'application/sla',
            'application/vnd.ms-pki.stl',
            'application/x-navistyle',
        ],
        'stp'     => 'application/STEP',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc'  => 'application/x-sv4crc',
        'svg'     => [
            'image/svg+xml',
            'image/svg',
            'application/xml',
            'text/xml',
        ],
        'svgz'    => 'image/svg+xml',
        'swf'     => 'application/x-shockwave-flash',
        't'       => 'application/x-troff',
        'tar'     => 'application/x-tar',
        'tcl'     => 'application/x-tcl',
        'tex'     => 'application/x-tex',
        'texi'    => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'text'    => 'text/plain',
        'tgz'     => [
            'application/x-tar',
            'application/x-gzip-compressed',
        ],
        'tif'   => 'image/tiff',
        'tiff'  => 'image/tiff',
        'tpl'   => 'text/template',
        'tr'    => 'application/x-troff',
        'tsi'   => 'audio/TSP-audio',
        'tsp'   => 'application/dsptype',
        'tsv'   => 'text/tab-separated-values',
        'ttc'   => 'font/ttf',
        'ttf'   => 'font/ttf',
        'txt'   => 'text/plain',
        'unv'   => 'application/i-deas',
        'ustar' => 'application/x-ustar',
        'vcd'   => 'application/x-cdlink',
        'vcf'   => 'text/x-vcard',
        'vda'   => 'application/vda',
        'viv'   => 'video/vnd.vivo',
        'vivo'  => 'video/vnd.vivo',
        'vlc'   => 'application/videolan',
        'vrml'  => 'model/vrml',
        'vtt'   => [
            'text/vtt',
            'text/plain',
        ],
        'wap' => [
            'text/vnd.wap.wml',
            'text/vnd.wap.wmlscript',
            'image/vnd.wap.wbmp',
        ],
        'wav' => [
            'audio/x-wav',
            'audio/wave',
            'audio/wav',
        ],
        'wbmp'   => 'image/vnd.wap.wbmp',
        'wbxml'  => 'application/wbxml',
        'webapp' => 'application/x-web-app-manifest+json',
        'webm'   => 'video/webm',
        'webp'   => 'image/webp',
        'wma'    => [
            'audio/x-ms-wma',
            'video/x-ms-asf',
        ],
        'wml'       => 'text/vnd.wap.wml',
        'wmlc'      => 'application/wmlc',
        'wmlscript' => 'text/vnd.wap.wmlscript',
        'wmv'       => [
            'video/x-ms-wmv',
            'video/x-ms-asf',
        ],
        'woff' => 'application/x-font-woff',
        'word' => [
            'application/msword',
            'application/octet-stream',
        ],
        'wrl'   => 'model/vrml',
        'xbm'   => 'image/x-xbitmap',
        'xht'   => 'application/xhtml+xml',
        'xhtml' => [
            'application/xhtml+xml',
            'application/xhtml',
            'text/xhtml',
        ],
        'xhtml-mobile' => 'application/vnd.wap.xhtml+xml',
        'xl'           => 'application/excel',
        'xlc'          => 'application/vnd.ms-excel',
        'xll'          => 'application/vnd.ms-excel',
        'xlm'          => 'application/vnd.ms-excel',
        'xls'          => [
            'application/vnd.ms-excel',
            'application/msexcel',
            'application/x-msexcel',
            'application/x-ms-excel',
            'application/x-excel',
            'application/x-dos_ms_excel',
            'application/xls',
            'application/x-xls',
            'application/excel',
            'application/download',
            'application/vnd.ms-office',
            'application/msword',
        ],
        'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/vnd.ms-excel',
            'application/msword',
            'application/x-zip',
        ],
        'xlw' => 'application/vnd.ms-excel',
        'xml' => [
            'application/xml',
            'text/xml',
            'text/plain',
        ],
        'xpi' => 'application/x-xpinstall',
        'xpm' => 'image/x-xpixmap',
        'xsl' => [
            'application/xml',
            'text/xsl',
            'text/xml',
        ],
        'xspf' => 'application/xspf+xml',
        'xwd'  => 'image/x-xwindowdump',
        'xyz'  => 'chemical/x-pdb',
        'z'    => 'application/x-compress',
        'zip'  => [
            'application/x-zip',
            'application/zip',
            'application/x-zip-compressed',
            'application/s-compressed',
            'multipart/x-zip',
        ],
        'zsh' => 'text/x-scriptzsh',
    ];

    /**
     * Carte des extensions aux types MIME.
     */
    public static array $mimes = self::MAP;

    /**
     * Tente de déterminer le meilleur type mime pour l'extension de fichier donnée.
     *
     * @return string|null Le type mime trouvé, ou aucun si impossible à déterminer.
     */
    public static function guessTypeFromExtension(string $extension): ?string
    {
        $extension = trim(strtolower($extension), '. ');

        if (! array_key_exists($extension, static::$mimes)) {
            return null;
        }

        return is_array(static::$mimes[$extension]) ? static::$mimes[$extension][0] : static::$mimes[$extension];
    }

    /**
     * Tente de déterminer la meilleure extension de fichier pour un type MIME donné.
     *
     * @param string|null $proposedExtension - extension par défaut (au cas où il y en aurait plusieurs avec le même type mime)
     *
     * @return string|null L'extension déterminée, ou null si elle ne correspond pas.
     */
    public static function guessExtensionFromType(string $type, ?string $proposedExtension = null): ?string
    {
        $type = trim(strtolower($type), '. ');

        $proposedExtension = trim(strtolower($proposedExtension ?? ''));

        if (
            $proposedExtension !== ''
            && array_key_exists($proposedExtension, static::$mimes)
            && in_array($type, (array) static::$mimes[$proposedExtension], true)
        ) {
            // Le type mime détecté correspond à l'extension proposée.
            return $proposedExtension;
        }

        // Vérifiez à l'envers la liste des types MIME si aucune extension n'a été proposée.
        // Cette recherche est sensible à l'ordre !
        foreach (static::$mimes as $ext => $types) {
            if (in_array($type, (array) $types, true)) {
                return $ext;
            }
        }

        return null;
    }
}
