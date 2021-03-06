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
namespace APF\tests\suites\core\expression;

use APF\core\expression\TemplateCondition;
use InvalidArgumentException;
use ReflectionMethod;

/**
 * Tests template condition expressions.
 */
class TemplateConditionTest extends \PHPUnit_Framework_TestCase {

   public function testGetArgument() {

      $method = new ReflectionMethod(TemplateCondition::class, 'getArgument');
      $method->setAccessible(true);

      $this->assertEquals([], $method->invokeArgs(null, ['foo()']));
      $this->assertEquals([], $method->invokeArgs(null, ['foo( )']));
      $this->assertEquals(['0'], $method->invokeArgs(null, ['foo(0)']));
      $this->assertEquals(['1'], $method->invokeArgs(null, ['foo(1)']));
      $this->assertEquals(['1'], $method->invokeArgs(null, ['foo( 1)']));
      $this->assertEquals(['1'], $method->invokeArgs(null, ['foo( 1 )']));
      $this->assertEquals(['1', '2'], $method->invokeArgs(null, ['foo(1,2)']));
      $this->assertEquals(['1', '2', '3'], $method->invokeArgs(null, ['foo(1,2 , 3 )']));
      $this->assertEquals(['bar', 'baz'], $method->invokeArgs(null, ['foo(\'bar\', \'baz\')']));

   }

   public function testEvaluate1() {

      $this->assertTrue(TemplateCondition::applies('true()', true));
      $this->assertFalse(TemplateCondition::applies('true()', false));

      $this->assertTrue(TemplateCondition::applies('false()', false));
      $this->assertFalse(TemplateCondition::applies('false()', true));

   }

   public function testEvaluate2() {

      $this->assertTrue(TemplateCondition::applies('empty()', null));
      $this->assertTrue(TemplateCondition::applies('empty()', ''));
      $this->assertTrue(TemplateCondition::applies('empty()', ' '));
      $this->assertFalse(TemplateCondition::applies('empty()', 'foo'));

      $this->assertTrue(TemplateCondition::applies('notEmpty()', 'foo'));
      $this->assertFalse(TemplateCondition::applies('notEmpty()', null));
      $this->assertFalse(TemplateCondition::applies('notEmpty()', ''));

   }

   public function testEvaluate3() {
      $this->expectException(InvalidArgumentException::class);
      TemplateCondition::applies('matches()', 'foo');
   }


   public function testEvaluate4() {
      $this->assertTrue(TemplateCondition::applies('matches(\'foo\')', 'foo'));
      $this->assertFalse(TemplateCondition::applies('matches(\'foo\')', 'bar'));
      $this->assertFalse(TemplateCondition::applies('matches(\'foo\')', ''));
   }

   public function testEvaluate5() {
      $this->expectException(InvalidArgumentException::class);
      TemplateCondition::applies('longerThan()', 'foo');
   }

   public function testEvaluate6() {
      $this->assertTrue(TemplateCondition::applies('longerThan(1)', 'foo'));
      $this->assertTrue(TemplateCondition::applies('longerThan(10)', 'foo-foo-foo'));
      $this->assertTrue(TemplateCondition::applies('longerThan(0)', 'f'));
      $this->assertFalse(TemplateCondition::applies('longerThan(0)', ''));
      $this->assertFalse(TemplateCondition::applies('longerThan(0)', null));
   }

   public function testEvaluate7() {
      $this->expectException(InvalidArgumentException::class);
      TemplateCondition::applies('shorterThan()', '');
   }

   public function testEvaluate8() {
      $this->assertTrue(TemplateCondition::applies('shorterThan(1)', ''));
      $this->assertFalse(TemplateCondition::applies('shorterThan(0)', ''));
      $this->assertFalse(TemplateCondition::applies('shorterThan(10)', '1234567890'));
      $this->assertTrue(TemplateCondition::applies('shorterThan(10)', '123456789'));
   }

   public function testEvaluate9() {
      $this->expectException(InvalidArgumentException::class);
      TemplateCondition::applies('length()', '');
   }

   public function testEvaluate10() {
      $this->assertTrue(TemplateCondition::applies('length(10)', '1234567890'));
      $this->assertFalse(TemplateCondition::applies('length(10)', '123456789'));
      $this->assertTrue(TemplateCondition::applies('length(0)', ''));
      $this->assertTrue(TemplateCondition::applies('length(0)', null));
   }

   public function testEvaluate11() {
      $this->expectException(InvalidArgumentException::class);
      TemplateCondition::applies('between()', '');
   }

   public function testEvaluate12() {
      $this->expectException(InvalidArgumentException::class);
      TemplateCondition::applies('between(1)', '');
   }

   public function testEvaluate13() {
      $this->assertTrue(TemplateCondition::applies('between(5,10)', '123456'));
      $this->assertFalse(TemplateCondition::applies('between(5,10)', 'foo'));
      $this->assertFalse(TemplateCondition::applies('between(5,10)', '1234567890123'));
      $this->assertTrue(TemplateCondition::applies('between(5,10)', '12345'));
      $this->assertTrue(TemplateCondition::applies('between(5,10)', '1234567890'));
   }

   public function testEvaluate14() {
      $this->expectException(InvalidArgumentException::class);
      TemplateCondition::applies('contains()', '');
   }

   public function testEvaluate15() {
      $this->assertTrue(TemplateCondition::applies('contains(foo)', '--foo-bar--'));
      $this->assertTrue(TemplateCondition::applies('contains(b)', 'b'));
      $this->assertFalse(TemplateCondition::applies('contains(test)', 'foo-foo-foo'));
      $this->assertFalse(TemplateCondition::applies('contains(10)', '1234567890'));
      $this->assertTrue(TemplateCondition::applies('contains(10)', '2345671089'));
   }

   public function testUnknownCondition() {
      $this->assertFalse(TemplateCondition::applies('foo()', null));
   }

}
