<?php
/**
 *  <!--
 *  This file is part of the adventure php framework (APF) published under
 *  http://adventure-php-framework.org.
 *
 *  The APF is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The APF is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with the APF. If not, see http://www.gnu.org/licenses/lgpl-3.0.txt.
 *  -->
 */

/**
 *  @package extensions::jscsspackager::biz
 *  @class JsCssPackager
 *
 *  A packager which can deliver multiple css and js files to client.
 *  Caching and shrinking is supported, but must be configured.
 *
 *  @author Ralf Schubert <ralf.schubert@the-screeze.de>
 *  @version
 *  Version 1.0, 18.03.2010<br />
 */
class JsCssPackager extends APFObject {
    /**
     * @var Configuration Contains the configuration for packages
     */
    protected $__Cfg = null;

    /**
     * Load the configuration for packages.
     *
     * @param <type> $initParam The init-param. (unimportant for this)
     *
     * @author Ralf Schubert
     * @version
     * Version 1.0, 18.03.2010<br />
     */
    public function init($initParam) {
        if($this->__Cfg === null) {
            $this->__Cfg = &$this->__getConfiguration('extensions::jscsspackager::biz','JsCssPackager', true);
        }
    }

    /**
     * Loads the content of all files, included in the package with the given name.
     *
     * @param String $name The package name.
     * @param Bool $gzip Return package compressed with gzip
     *
     * @return String The complete package.
     *
     * @author Ralf Schubert
     * @version
     * Version 1.0, 18.03.2010<br />
     */
    public function getPackage($name, $gzip = false) {
        $cfgPack = $this->__Cfg->getSection($name);

        if($cfgPack['ServerCacheMinutes'] === null) {
            $cfgPack['ServerCacheMinutes'] = 0;
        }

        /* If ServerCacheMinutes is not 0, we use a filecache */
        if((int)$cfgPack['ServerCacheMinutes'] !== 0) {
            $cMF = &$this->__getServiceObject('tools::cache','CacheManagerFabric');
            $cM = &$cMF->getCacheManager('jscsspackager_cache');

            $cacheKey = $name;
            if ($gzip === true) {
                $cacheKey .= '_gzip';
            }

            $cacheContent = $cM->getFromCache($cacheKey);
            /* If package is already in cache, we check if it is not expired and return the cache content */
            if($cacheContent !== null) {
                $cacheExpires = substr($cacheContent, -10);
                if($cacheExpires >= time()) {
                    return substr($cacheContent,0, -10);
                }
                else {
                    /* Cache is expired, delete it */
                    $cM->clearCache($name);
                    $cm->clearCache($name . '_gzip');
                }
            }
            /* Package was not in cache or was expired, we generate a new one, cache and deliver it. */
            $newPackage = $this->__generatepackage($cfgPack, $name);
            $cacheExpires = time()+($cfgPack['ServerCacheMinutes']*60);
            $newPackageGzip = gzencode($newPackage, 9);

            $cM->writeToCache($name, $newPackage.$cacheExpires);
            $cM->writeToCache($name . '_gzip', $newPackageGzip.$cacheExpires);

            if($gzip === true) {
                return $newPackageGzip;
            }
            return $newPackage;
        }

        /* We generate the package new, because we don't use a cache */
        $pack = $this->__generatePackage($cfgPack, $name);
        if($gzip === true) {
            return gzencode($pack, 9);
        }
        return $pack;

    }

    /**
     * Generates a package from it's single files.
     * Will Shrink output, if enabled.
     *
     * @param array $cfgPack The package configuration
     * @param string $name The package name
     * @return string All files put together to one string.
     *
     * @author Ralf Schubert
     * @version
     * Version 1.0, 18.03.2010<br />
     */
    protected function __generatePackage($cfgPack, $name) {
        $output = '';
        foreach($cfgPack['Files'] as $file) {
            $output .= $this->__loadSingleFile($file['Namespace'], $file['Filename'], $cfgPack['PackageType'], $name);
        }

        if($cfgPack['EnableShrinking'] === 'true') {
            switch($cfgPack['PackageType']) {
                case 'js':
                    $output = $this->__shrinkJS($output);
                    break;
                case 'css':
                    $output = $this->__shrinkCSS($output);
                    break;
            }
        }

        return $output;
    }

    /**
     * Shrinks a string containing javascript.
     *
     * @param string $input The javascript which should be shrinked.
     * @return string The minified javascript.
     *
     * @author Ralf Schubert
     * @version
     * Version 1.0, 18.03.2010<br />
     */
    protected function __shrinkJs($input) {
        import('extensions::jscsspackager::biz', 'JSMin');
        return JSMin::minify($input);
    }

    /**
     * Shrinks a string containing css
     *
     * @param string $input The css which should be shrinked.
     * @return string The minified css.
     *
     * @author Ralf Schubert
     * @version
     * Version 1.0, 18.03.2010<br />
     */
    protected function __shrinkCSS($input) {
        $input = preg_replace( '#\s+#', ' ', $input );
        $input = preg_replace( '#/\*.*?\*/#s', '', $input );
        $input = str_replace( '; ', ';', $input );
        $input = str_replace( ': ', ':', $input );
        $input = str_replace( ' {', '{', $input );
        $input = str_replace( '{ ', '{', $input );
        $input = str_replace( ', ', ',', $input );
        $input = str_replace( '} ', '}', $input );
        $input = str_replace( ';}', '}', $input );
        return trim( $input );
    }

    /**
     * Loads the period (in days) the package should be cached by client.
     * Default is 0 (no client caching)
     *
     * @param String $name The package name.
     * @return int The period the package should be cached by client in days.
     *
     * @author Ralf Schubert
     * @version
     * Version 1.0, 18.03.2010<br />
     */
    public function getClientCachePeriod($name) {
        if(($CCP = $this->__Cfg->getValue($name, 'ClientCacheDays')) !== null) {
            return (int) $CCP;
        }
        return 0;
    }

    /**
     * Loads the content of a file.
     *
     * @param string $namespace The namespace of the file.
     * @param string $file The name of the file.
     * @param string $ext The extension of the file.
     * @param string $packageName The name of the package, which contains the file.
     * @return string The content of the file.
     *
     * @author Ralf Schubert
     * @version
     * Version 1.0, 18.03.2010<br />
     */
    protected function __loadSingleFile($namespace, $file, $ext, $packageName) {
        $namespace = str_replace('::','/',$namespace);
        $reg = &Singleton::getInstance('Registry');
        $libBasePath = $reg->retrieve('apf::core','LibPath');
        $filePath = $libBasePath.'/'. $namespace .'/'.$file.'.'.$ext;

        if(file_exists($filePath)) {
            return file_get_contents($filePath);
        }
        throw new IncludeException('[JsCssPackager::__loadSingleFile()] The requested file "'.$file.'.'.$ext
                .'" cannot be found in namespace "'.str_replace('/','::',$namespace).'". Please
                    check the configuration of package "'.$packageName.'"!');
        return '';
    }
}
?>