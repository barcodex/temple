<?php

use Temple\ScalarModifier;

class ScalarModifierTest extends PHPUnit_Framework_TestCase
{
    public function testUppercase()
    {
        $modified = ScalarModifier::calculateValue('uppercase', array(), 'dobby RT harryPotter N0W');
        $this->assertEquals('DOBBY RT HARRYPOTTER N0W', $modified);
    }

    public function testLowercase()
    {
        $modified = ScalarModifier::calculateValue('lowercase', array(), 'Title low UPPER');
        $this->assertEquals('title low upper', $modified);
    }

    public function testIfTrue()
    {
        $modified = ScalarModifier::calculateValue('iftrue', array(), (1 == 2));
        $this->assertEquals('', $modified);
        $modified = ScalarModifier::calculateValue('iftrue', array(), 'non-empty string');
        $this->assertNotEquals('', $modified);
        $modified = ScalarModifier::calculateValue('iftrue', array(), 10);
        $this->assertNotEquals('', $modified);
        $modified = ScalarModifier::calculateValue('iftrue', array(), 0); // should convert to false
        $this->assertEquals(false, $modified);
        $modified = ScalarModifier::calculateValue('iftrue', array(), null); // should convert to false
        $this->assertEquals(false, $modified);
    }

    public function testIfFalse()
    {
        $modified = ScalarModifier::calculateValue('iffalse', array(), (1 == 2));
        $this->assertEquals(false, $modified);
        $modified = ScalarModifier::calculateValue('iffalse', array(), 'non-empty string');
        $this->assertEquals('', $modified);
        $modified = ScalarModifier::calculateValue('iffalse', array(), 10);
        $this->assertEquals('', $modified);
        $modified = ScalarModifier::calculateValue('iffalse', array(), 0); // should convert to false
        $this->assertEquals(false, $modified);
        $modified = ScalarModifier::calculateValue('iffalse', array(), null); // should convert to false
        $this->assertEquals(false, $modified);
    }

    public function testIfNull()
    {
        $modified = ScalarModifier::calculateValue('ifnull', array(), null);
        $this->assertNull($modified);
        $modified = ScalarModifier::calculateValue('ifnull', array(), 'non-empty string');
        $this->assertEquals('', $modified);
        $modified = ScalarModifier::calculateValue('ifnull', array(), 10);
        $this->assertEquals('', $modified);
        $modified = ScalarModifier::calculateValue('ifnull', array(), 0);
        $this->assertEquals('', $modified);
        $testObject = new ScalarModifier();
        $modified = ScalarModifier::calculateValue('ifnull', array(), $testObject);
        $this->assertEquals('', $modified);
        $testObject = null;
        $modified = ScalarModifier::calculateValue('ifnull', array(), null);
        $this->assertNull($modified);
    }
}