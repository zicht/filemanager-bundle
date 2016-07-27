<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Doctrine;

use Symfony\Component\Form\Util\PropertyPath;
use Zicht\Util\Str;

// TODO do proper inflection and detection of setters/getters
// (PropertyAccess / Symfony 2.2?)
/**
 * Utility classes to access properties on an entity
 */
class PropertyHelper
{
    /**
     * Calls a setter in the entity with the specified value
     *
     * @param object $entity
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public static function setValue($entity, $field, $value)
    {
        $entity->{'set' . ucfirst(Str::camel($field))}($value);
    }


    /**
     * Calls a getter in the entity
     *
     * @param object $entity
     * @param string $field
     * @return mixed
     */
    public static function getValue($entity, $field)
    {
        return $entity->{'get' . ucfirst(Str::camel($field))}();
    }
}
