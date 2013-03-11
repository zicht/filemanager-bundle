<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Doctrine;

use \Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;

class Entity
{
    public $the_property = null;

    public function setMyFile($value)
    {
        $this->the_property = $value;
    }


    public function getMyFile()
    {
        return $this->the_property;
    }
}
 
class PropertyHelperTest extends \PHPUnit_Framework_TestCase
{
    function testSetter()
    {
        $o = new Entity;

        $value = rand(1, 100);
        PropertyHelper::setValue($o, 'my_file', $value);
        $this->assertEquals($value, $o->the_property);
        $this->assertEquals($value, PropertyHelper::getValue($o, 'my_file'));
    }
}