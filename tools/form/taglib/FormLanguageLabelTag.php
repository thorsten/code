<?php
namespace APF\tools\form\taglib;

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
use APF\core\pagecontroller\LanguageLabelTag;

/**
 * @package tools::form::taglib
 * @class FormLanguageLabelTag
 *
 * Extends the LanguageLabelTag class with the methods, that need to be implemented
 * for the new form taglibs. Directly uses the functionality of the base class.
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 17.09.2009<br />
 */
class FormLanguageLabelTag extends LanguageLabelTag implements FormControl {

   public function isValid() {
      return true;
   }

   public function isSent() {
      return false;
   }

}
