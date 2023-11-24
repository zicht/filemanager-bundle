<?php

namespace Zicht\Bundle\FileManagerBundle\Annotation;

/**
 * Annotation class for the FileManager's @File annotation.
 *
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class File
{
    public ?array $settings = null;

    /**
     * @param array $data
     */
    public function __construct(?array $data = null)
    {
        $this->settings = $data;
    }
}
