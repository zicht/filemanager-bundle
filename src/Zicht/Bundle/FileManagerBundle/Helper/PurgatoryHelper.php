<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
  */

namespace Zicht\Bundle\FileManagerBundle\Helper;

/**
 * Class PurgatoryHelper
 * @package Zicht\Bundle\FileManagerBundle\Helper
 *
 * Helper to centralize the hashing and postfixes
 * @see FileType
 * @see FileTypeSubscriber
 */
class PurgatoryHelper
{
    public static function makeHash($entity, $field, $fileName)
    {
        //TODO, make better hash ^^
        return md5($entity . $field . $fileName);
    }

    public static function makePostFix($entity, $field)
    {
        return md5($entity . $field);
    }
} 