<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Annotation;

/**
 * @Annotation
 */
class File
{
    public $settings = null;

    function __construct(array $data)
    {
        $this->settings = $data;
    }
}