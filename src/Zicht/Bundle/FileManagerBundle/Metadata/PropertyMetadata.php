<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Metadata;

use \Metadata\PropertyMetadata as BasePropertyMetaData;

/**
 * Property meta data implementation for filemanager details.
 */
class PropertyMetadata extends BasePropertyMetaData
{
    public $fileManager = null;
}