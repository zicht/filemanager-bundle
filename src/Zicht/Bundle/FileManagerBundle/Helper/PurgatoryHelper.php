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
    const SUPER_SECRET_SHIZZLE = 'ThisIsASuperSecretKeyUsedForTheHashAndIAmAAAAWSommeee';

    public static function makeHash($propertyPath, $fileName)
    {
        //TODO, make better hash ^^
        return md5($propertyPath . $fileName . PurgatoryHelper::SUPER_SECRET_SHIZZLE);
    }
}