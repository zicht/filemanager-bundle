<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Integrity;

/**
 * Common interface for integrity checker
 */
interface CheckerInterface
{
    /**
     * Set a logger callback for verbose output
     *
     * @param callable $callable
     * @return mixed
     */
    public function setLoggingCallback($callable);


    /**
     * Controls whether to purge the found integrity violations
     *
     * @param bool $purge
     * @return mixed
     */
    public function setPurge($purge);


    /**
     * Perform the check on the specified entity
     *
     * @param string $entityClass
     * @return mixed
     */
    public function check($entityClass);
}