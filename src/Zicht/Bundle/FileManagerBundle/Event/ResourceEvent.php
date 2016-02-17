<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Event;

/**
 * Represents an event that happened to a file.
 *
 * TODO This event may be moved to a more generic location as it will also be usable for different types of changes
 */
class ResourceEvent
{
    const CREATED = 'resource.created';
    const REPLACED = 'resource.replaced';
    const DELETED = 'resource.deleted';

    private $relativePath;
    private $webRoot;
    private $localRoot;

    /**
     * An event that happened to a resource
     *
     * @param string $relativePath
     * @param string $webRoot
     * @param string $localRoot
     */
    public function __construct($relativePath, $webRoot, $localRoot)
    {
        $this->relativePath = $relativePath;
        $this->webRoot = $webRoot;
        $this->localRoot = $localRoot;
    }

    /**
     * Returns the web path of the file (relative to the applications web root)
     *
     * @return string
     */
    public function getWebPath()
    {
        return $this->webRoot . $this->relativePath;
    }

    /**
     * Returns the absolute local file path of the file
     *
     * @return string
     */
    public function getLocalPath()
    {
        return $this->localRoot . $this->relativePath;
    }
}