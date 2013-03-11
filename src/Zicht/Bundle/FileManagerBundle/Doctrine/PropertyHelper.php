<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Doctrine;

use Symfony\Component\Form\Util\PropertyPath;

// TODO do proper inflection and detection of setters/getters
// (PropertyAccess / Symfony 2.2?)
class PropertyHelper
{
    public static function camelize($name)
    {
        return preg_replace_callback('/_([a-z])/', function($m) { return ucfirst($m[1]); }, $name);
    }


    static function setValue($entity, $field, $value)
    {
        $entity->{'set' . ucfirst(self::camelize($field))}($value);
    }


    static function getValue($entity, $field)
    {
        return $entity->{'get' . ucfirst(self::camelize($field))}();
    }
}