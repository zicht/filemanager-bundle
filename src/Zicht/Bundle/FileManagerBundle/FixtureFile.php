<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * A "fixture" file acts as an uploaded file, but it does not move the file to the moved location,
 * but rather copies it, so the original fixture stays in tact.
 */
class FixtureFile extends \Symfony\Component\HttpFoundation\File\File
{
    /**
     * Overrides default behaviour only to copy the file in stead of moving it.
     *
     * @param string $directory
     * @param null $name
     * @return \Symfony\Component\HttpFoundation\File\File
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    public function move($directory, $name = null) {
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true)) {
                throw new FileException(sprintf('Unable to create the "%s" directory', $directory));
            }
        } elseif (!is_writable($directory)) {
            throw new FileException(sprintf('Unable to write in the "%s" directory', $directory));
        }

        $target = $directory.DIRECTORY_SEPARATOR.(null === $name ? $this->getBasename() : basename($name));

        if (!@copy($this->getPathname(), $target)) {
            $error = error_get_last();
            throw new FileException(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error['message'])));
        }

        @chmod($target, 0666 & ~umask());

        return new File($target);
    }
}