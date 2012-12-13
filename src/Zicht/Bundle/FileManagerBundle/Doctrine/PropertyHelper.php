<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Doctrine;

// TODO do proper inflection and detection of setters/getters
class PropertyHelper
{
    static function setValue($entity, $field, $value)
    {
        $entity->{'set' . ucfirst($field)}($value);
    }


    static function getValue($entity, $field)
    {
        return $entity->{'get' . ucfirst($field)}();
    }
}