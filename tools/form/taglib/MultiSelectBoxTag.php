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
namespace APF\tools\form\taglib;

use APF\tools\form\FormException;

/**
 * Represents the APF multiselect field.
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 15.01.2007<br />
 * Version 0.2, 07.06.2008 (Reimplemented the transform() method)<br />
 * Version 0.3, 08.06.2008 (Reimplemented the __validate() method)<br />
 * Version 0.3, 12.02.2010 (Introduced attribute black and white listing)<br />
 */
class MultiSelectBoxTag extends SelectBoxTag {

   /**
    * Initializes the known child taglibs, sets the validator style and adds the multiple attribute.
    *
    * @author Christian Schäfer
    * @version
    * Version 0.1, 07.01.2007<br />
    * Version 0.2, 03.03.2007 (Removed the "&" before the "new" operator)<br />
    * Version 0.3, 26.08.2007 (Added the "multiple" attribut)<br />
    * Version 0.4, 28.08.2010 (Added option groups)<br />
    */
   public function __construct() {
      $this->setAttribute('multiple', 'multiple');
      $this->attributeWhiteList[] = 'disabled';
      $this->attributeWhiteList[] = 'name';
      $this->attributeWhiteList[] = 'size';
      $this->attributeWhiteList[] = 'tabindex';
      $this->attributeWhiteList[] = 'multiple';
   }

   /**
    * Parses the child tags and checks the name of the element to contain "[]".
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 15.01.2007<br />
    * Version 0.2, 07.06.2008 (Extended error message)<br />
    * Version 0.3, 15.08.2008 (Extended error message with the name of the control)<br />
    */
   public function onParseTime() {

      // parses the option tags
      $this->extractTagLibTags();

      // check, whether the name of the control has no "[]" defined, to ensure
      // that we can address the element with it's plain name in the template.
      $name = $this->getAttribute('name');
      if (substr_count($name, '[') > 0 || substr_count($name, ']') > 0) {
         $doc = & $this->getParentObject()->getParentObject();
         $docCon = $doc->getDocumentController();
         throw new FormException('[MultiSelectBoxTag::onParseTime()] The attribute "name" of the '
               . '&lt;form:multiselect /&gt; tag with name "' . $this->attributes['name']
               . '" in form "' . $this->getParentObject()->getAttribute('name') . '" and document '
               . 'controller "' . $docCon . '" must not contain brackets! Please ensure, that the '
               . 'appropriate form control has a suitable name. The brackets are automatically '
               . 'generated by the taglib!', E_USER_ERROR);
      }

      $this->presetValue();

   }

   /**
    * Creates the HTML output of the select field.
    *
    * @return string The HTML code of the select field.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 07.06.2008 (Reimplemented the transform() method because of a presetting error)<br />
    * Version 0.2, 02.01.2013 (Introduced form control visibility feature)<br />
    */
   public function transform() {

      // do lazy presetting, in case we are having a field with dynamic options
      if ($this->isDynamicField === true) {
         $this->presetValue();
      }

      if ($this->isVisible) {
         // add brackets for the "name" attribute to ensure multi select capability!
         $name = array('name' => $this->getAttribute('name') . '[]');
         $select = '<select ' . $this->getSanitizedAttributesAsString(array_merge($this->attributes, $name)) . '>';
         $select .= $this->content . '</select>';

         if (count($this->children) > 0) {
            foreach ($this->children as $objectId => $DUMMY) {
               $select = str_replace('<' . $objectId . ' />',
                     $this->children[$objectId]->transform(),
                     $select
               );
            }
         }

         return $select;
      }

      return '';
   }

   /**
    * Returns the selected options.
    *
    * @return SelectBoxOptionTag[] List of the options, that are selected.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 08.06.2008<br />
    */
   public function &getSelectedOptions() {

      // call presetting lazy if we have dynamic field
      if ($this->isDynamicField === true) {
         $this->presetValue();
      }

      $selectedOptions = array();
      foreach ($this->children as $objectId => $DUMMY) {

         if ($this->children[$objectId] instanceof SelectBoxGroupTag) {
            $options = & $this->children[$objectId]->getSelectedOptions();
            foreach ($options as $id => $INNER_DUMMY) {
               $selectedOptions[] = & $options[$id];
            }
         } else {
            if ($this->children[$objectId]->getAttribute('selected') === 'selected') {
               $selectedOptions[] = & $this->children[$objectId];
            }
         }

      }

      return $selectedOptions;

   }

   /**
    * Re-implements the presetting method for the multi-select field.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 15.01.2007<br />
    * Version 0.2, 16.01.2007 (Now checks, if the request param is set)<br />
    */
   protected function presetValue() {
      if (count($this->children) > 0) {
         foreach ($this->getRequestValues() as $value) {
            $this->setOption2Selected($value);
         }
      }
   }

   /**
    * retrieves the selected values from the current request. Returns an
    * empty array, if no options are found.
    *
    * @return string[] The currently selected values contained in th request.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 28.08.2010<br />
    */
   protected function getRequestValues() {
      $values = array();
      $controlName = $this->getAttribute('name');
      if (isset($_REQUEST[$controlName])) {
         $values = $_REQUEST[$controlName];
      }

      return $values;
   }

   /**
    * Re-implements the retrieving of values for multi-select controls.
    *
    * @return SelectBoxOptionTag[] List of the options, that are selected.
    *
    * @since 1.14
    *
    * @author Ralf Schubert
    * @version
    * Version 0.1, 26.07.2011<br />
    */
   public function getValue() {
      return $this->getSelectedOptions();
   }

   /**
    * Let's check if something was selected in form:multiselect.
    *
    * @return bool True in case the control is selected, false otherwise.
    * @since 1.15
    *
    * @author dave
    * @version
    * Version 0.1, 22.09.2011<br />
    */
   public function isSelected() {
      return count($this->getSelectedOptions()) > 0;
   }

   protected function removeSelectedOptions($selectedObjectId) {
      // Do nothing here, since MultiSelectBox allows multiple selected options.
   }

}
