<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Annotation;

/**
 * Annotation class for the FileManager's @File annotation.
 *
 * @Annotation
 */
class File
{
    public $settings = null;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->settings = $data;
    }
}
