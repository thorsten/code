<?php
/**
 * <!--
 * This file is part of the adventure php framework (APF) published under
 * http://adventure-php-framework.org.
 *
 * The APF is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The APF is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with the APF. If not, see http://www.gnu.org/licenses/lgpl-3.0.txt.
 * -->
 */
namespace APF\tools\html\taglib;

use APF\core\loader\RootClassLoader;
use APF\core\pagecontroller\ImportTemplateTag;
use Exception;

/**
 * This is an extension of the core:importdesign taglib. With this taglib you are
 * able to use language-dependent incparams. The names of these language-dependent
 * parameters have to be stored in a config file.
 * <p/>
 * If the called template does not exist the default template is loaded.
 * <p/>
 * To use this tag, the following attributes must be involved
 * <p/>
 * <pre>&lt;lang:importdesign
 *             namespace=""
 *             template=""
 *             incparam=""
 *             [context=""]
 *             [language=""]
 *             [config=""]
 *             [configNamespace=""]
 *             [deactivateConfigException="false"]
 *             [prefix=""]
 * /&gt;</pre>
 * <p/>
 * <ul>
 *   <li>namespace: template namespace</li>
 *   <li>template: template identification (language-independent)</li>
 *   <li>incparam: name of the parameter which will be looked up in the config and replaced afterwards. (default: incparam)</li>
 *   <li>config: name of the config file which shout be used. (default: requestparameter.ini)
 *   <li>configNamespace: namespace which should be used for the config file.</li>
 *   <li>context: change context for current node (default: current context, value: Has to be a valid context)</li>
 *   <li>language: change language for current node (default: current language, value: Has to be a valid language)</li>
 *   <li>deactivateConfigException: By setting its value to "true" you will not get any exceptions if the config file is not found</li>
 *   <li>prefix: prefix which is used to build up the template file name.
 * </ul>
 * <p/>
 * Template filename scheme:
 * If prefix!=''
 * {prefix}_{lang}_{Identification}_{Name}.html
 * otherwise:
 * {lang}_{Identification}_{Name}.html
 *
 * @author Werner Liemberger wpublicmail [AT] gmail DOT com
 * @version
 * Version 0.1, 28.7.2011<br />
 */
class LanguageDependentIncParamImportTemplateTag extends ImportTemplateTag {

   public function onParseTime() {

      $templateID = $this->getRequiredAttribute('template');
      $namespace = $this->getRequiredAttribute('namespace');

      $incParam = $this->getAttribute('incparam', 'incparam');
      $prefix = $this->getAttribute('prefix', '');
      $context = $this->getAttribute('context', null);

      if ($context !== null) {
         $this->setContext($context);
      }

      $Language = $this->getAttribute('language', null);
      if ($Language !== null) {
         $this->setLanguage($Language);
      }

      $configFile = $this->getAttribute('config', 'requestparameter.ini');
      $configNamespace = $this->getAttribute('configNamespace', 'APF\tools\html');

      // load Config
      try {
         $config = $this->getConfiguration($configNamespace, $configFile);

         // get language-dependent parameters form config
         if ($config->hasSection($this->getLanguage())) {
            $incParamName = $config->getSection($this->getLanguage())->getValue($incParam);
         }
      } catch (Exception $e) {
         if ($this->getAttribute('deactivateConfigException', 'false') != "true") {
            throw $e;
         }
      }
      if (!isset($incParamName) || $incParamName === null) {
         $incParamName = $incParam;
      }


      // Check if parameter name is in Url and load template afterwards.
      // If requested template does not exist, load default.
      $template = '';

      $rootPath = $this->getRootPath();
      $basePath = $rootPath . '/' . str_replace('\\', '/', $namespace);
      $value = $this->getRequest()->getParameter($incParamName);
      if ($value !== null) {
         $templateToTest = $this->getFileName($prefix, '', $value);
         $files = glob($basePath . '/' . $templateToTest);
         if (count($files) >= 1) {
            $template = substr(str_replace($basePath, '', $files[0]), 0, -5);
         }
      }

      if ($template == '') {
         // load default
         $templateToTest = $this->getFileName($prefix, $templateID);
         $files = glob($basePath . '/' . $templateToTest);
         if (count($files) >= 1) {
            $template = substr(str_replace($basePath, '', $files[0]), 0, -5);
         }
      }

      $this->loadContentFromFile($namespace, $template);
      $this->extractDocumentController();
      $this->extractTagLibTags();
   }

   private function getRootPath() {
      return RootClassLoader::getLoaderByVendor('APF')->getRootPath();
   }

   /**
    * Function builds up full name of the template. If some information are missing
    * they will be replaced by "*". This is necessary to find the files with the glob method.
    * <p/>
    * Scheme:
    * <p/>
    * If prefix!=''
    * {prefix}_{lang}_{Identification}_{Name}.html
    * otherwise:
    * {lang}_{Identification}_{Name}.html
    *
    * @author Werner Liemberger wpublicmail [AT] gmail DOT com
    * @version
    * Version 0.1, 28.7.2011<br />
    *
    * @param string $prefix
    * @param string $identification Language-independent template ID (needed to find related templates in different languages.)
    * @param string $name Language-dependent name
    * @param string $lang Language which should be used.
    *
    * @return string Template file name.
    */
   private function getFileName($prefix = '', $identification = '', $name = '', $lang = null) {
      if ($lang === null) {
         $lang = $this->getLanguage();
      }
      if ($identification == '') {
         $identification = '*';
      }
      if ($name == '') {
         $name = '*';
      }
      if ($prefix != '') {
         $prefix .= '_';
      }

      return $prefix . $lang . '_' . $identification . '_' . $name . '.html';
   }

}
