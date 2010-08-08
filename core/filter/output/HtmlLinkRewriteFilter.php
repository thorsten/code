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
    * @package core::filter::output
    * @class HtmlLinkRewriteFilter
    *
    * Implements a URL rewriting output filter for HTML source code. Rewriting can be adjusted
    * using the <em>linkrewrite</em> attribute. If it is set to "true" or not present, links are
    * rewritten, "false" introduces the filter to not rewrite the link. Further, "mailto:" links
    * are not rewritten, too.
    *
    * @author Christian Schäfer
    * @version
    * Version 0.1, 05.05.2007 (First version of the link rewrite filter)<br />
    * Version 0.2, 08.05.2007 (Refactoring as a filter)<br />
    * Version 0.3, 17.06.2007 (Added form action rewriting)<br />
    */
   class HtmlLinkRewriteFilter extends AbstractFilter {

      public function HtmlLinkRewriteFilter(){
      }

      /**
       * @public
       *
       * Implements the filter methods for rewriting HTML links and form actions.
       *
       * @param string $input The HTML content to rewrite.
       * @return string The rewritten HTML content.
       *
       * @author Christian Schäfer
       * @version
       * Version 0.1, 05.05.2007<br />
       * Version 0.2, 08.05.2007 (Refactored to a real filter)<br />
       * Version 0.3, 08.08.2010 (Bug 380: added empty sign to distinguish between real a tags and others)<br />
       */
      public function filter($input){

         // invoke timer
         $t = &Singleton::getInstance('BenchmarkTimer');
         $t->start('GenericOutputFilter::filter()');

         // filter links
         $input = $this->filterHtml($input,'<a ','>','href');

         // filter actions
         $input = $this->filterHtml($input,'<form ','>','action');

         $t->stop('GenericOutputFilter::filter()');
         return $input;

       // end function
      }

      /**
       * @private
       *
       * Generic filter method, that can filter various HTML tags/attributes.
       * <p/>
       * Html start tags must be passed with an trailing blank!
       *
       * @param string $htmlContent The HTML source code.
       * @param string $startToken The start token of the tag to parse.
       * @param string $endToken The end token of the tag to parse.
       * @param string $attributeToken The name of the attribute to rewrite.
       * @return string The rewritten HTML content.
       *
       * @author Christian Schäfer
       * @version
       * Version 0.1, 17.07.2007 (Refactored the filter method to be able to filter links and forms with the same method)<br />
       * Version 0.2, 11.12.2008 (Made the benchmark ids more explicit)<br />
       * Version 0.3, 13.12.2008 (Removed the benchmarker)<br />
       * Version 0.4, 13.07.2009 (Now "mailto:" links are not rewrited by default!)<br />
       * Version 0.5, 08.08.2010 (Bug 380: removed extra blank when re-assembling the tag)<br />
       */
      private function filterHtml($htmlContent,$startToken = '<a ',$endToken = '>',$attributeToken = 'href'){

         $searchOffset = 0;
         $tokenFound = true;
         $startTokenLength = strlen($startToken);
         $endTokenLength = strlen($endToken);
         
         while($tokenFound == true){

            // we have to add an ugly @ sign, because sometimes with PHP5, an
            // error/warning is generated! :/
            $currentLinkStartPos = @strpos($htmlContent,$startToken,$searchOffset);

            if($currentLinkStartPos !== false){

               $currentLinkEndPos = strpos($htmlContent,$endToken,$currentLinkStartPos);

               $currentLinkString = substr($htmlContent,
                  $currentLinkStartPos + $startTokenLength,
                  $currentLinkEndPos - $currentLinkStartPos - $startTokenLength);

               $currentLinkAttributes = XmlParser::getAttributesFromString($currentLinkString);

               // rewrite link if desired
               if(isset($currentLinkAttributes[$attributeToken])){

                  // check for "linkrewrite" attribute
                  if(isset($currentLinkAttributes['linkrewrite']) && $currentLinkAttributes['linkrewrite'] == 'false'){
                   // end if
                  }
                  elseif(substr_count($currentLinkAttributes[$attributeToken],'mailto:') > 0){
                   // end elseif
                  }
                  else{
                     $currentLinkAttributes[$attributeToken] = $this->replaceURISeparators($currentLinkAttributes[$attributeToken]);
                   // end else
                  }

                // end if
               }

               // remove the linkrewrite attribute and generate the link tag
               unset($currentLinkAttributes['linkrewrite']);
               $currentReplacedLinkString = $startToken // no empty sign must be added, due we have already one from the tag definition!
                  .$this->__getAttributesAsString($currentLinkAttributes).'>';
               $htmlContent = substr_replace($htmlContent,
                  $currentReplacedLinkString,
                  $currentLinkStartPos,
                  $currentLinkEndPos - $currentLinkStartPos + $endTokenLength);
               $searchOffset = $currentLinkEndPos + $endTokenLength;

             // end if
            }
            else{
               $tokenFound = false;
             // end else
            }

          // end while
         }

         return $htmlContent;

       // end function
      }

      /**
       * @private
       *
       * Ersetzt in URLs �bliche Request-Strings durch Slashes.<br />
       *
       * @param string $String; URL-Teil
       * @return string $String; Ersetzter URL-Teil
       *
       * @author Christian Schäfer
       * @version
       * Version 0.1, 14.03.2006<br />
       * Version 0.2, 16.04.2006<br />
       * Version 0.3, 27.07.2006 (Bug beim Replacen behoben ('./?' statt '/?'))<br />
       * Version 0.4, 01.08.2006 (Bug behoben, dass eine URI http://localhost/?Seite=123 falsch rewritet wurde)<br />
       * Version 0.5, 02.06.2007 (Encoded ampersands werden nun auch ersetzte)<br />
       * Version 0.6, 08.06.2007 (von "Page" nach "htmlLinkRewriteFilter" umgezogen)<br />
       */
      private function replaceURISeparators($string){

         $replace = array('/?' => '/',
                          './?' => '/',
                          '=' => '/',
                          '&' => '/',
                          '&amp;' => '/',
                          '?' => '/'
                         );
         return strtr($string,$replace);

       // end function
      }

    // end class
   }
?>