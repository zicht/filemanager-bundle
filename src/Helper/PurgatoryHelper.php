<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
  */

namespace Zicht\Bundle\FileManagerBundle\Helper;

/**
 * Class PurgatoryHelper
 * Helper to centralize the hashing and postfixes
 *
 * @package Zicht\Bundle\FileManagerBundle\Helper
 *
 * @see FileType
 * @see FileTypeSubscriber
 */
class PurgatoryHelper
{
    /** @const string SUPER_SECRET_SHIZZLE */
    const SUPER_SECRET_SHIZZLE = 'ThisIsASuperSecretKeyUsedForTheHashAndIAmAAAAWSommeee';

    /**
     * Makes a hash from the propertyPath and fileName, so it can be used in the form
     *
     * @param string $propertyPath
     * @param string $fileName
     * @return string
     */
    public static function makeHash($propertyPath, $fileName)
    {
        //TODO, make better hash ^^
        return md5($propertyPath . $fileName . PurgatoryHelper::SUPER_SECRET_SHIZZLE);
    }
}
