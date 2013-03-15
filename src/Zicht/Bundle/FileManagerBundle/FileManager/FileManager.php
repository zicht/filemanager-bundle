<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\FileManager;

use \Symfony\Component\HttpFoundation\File\File;
use \Symfony\Component\HttpFoundation\File\UploadedFile;
use \Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;

/**
 * Storage layer for files.
 *
 * The save and getFilePath() use a simple mapping based on an entity and field name. The entity name is based on
 * either a string or a classname, where the class name is mapped to the local classname and lowercased.
 */
class FileManager {
    /**
     * Construct the filemanager.
     *
     * @param string $root
     * @param string $httpRoot
     */
    public function __construct($root, $httpRoot)
    {
        $this->root = rtrim($root, '/');
        $this->httpRoot = rtrim($httpRoot, '/');
        $this->preparedPaths = array();
    }


    /**
     * Prepares a file for upload, which means that a stub file is created at the point where the file
     * otherwise would be uploaded.
     *
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @param mixed $entity
     * @param string $field
     * @param bool $noclobber
     * @return string
     */
    public function prepare(File $file, $entity, $field, $noclobber = true)
    {
        $dir = $this->getDir($entity, $field);
        $i = 0;
        do {
            $f = $this->proposeFilename($file, $i ++);
            $pathname = $dir . '/' . $f;
        } while ($noclobber && is_file($pathname));
        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0777 & ~umask(), true);
        }
        touch($pathname);
        $this->preparedPaths[]= $pathname;
        return $pathname;
    }


    /**
     * Save a file to a previously prepared path.
     *
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @param string $preparedPath
     * @return void
     *
     * @throws \RuntimeException
     */
    public function save(File $file, $preparedPath)
    {
        if (false === ($i = array_search($preparedPath, $this->preparedPaths))) {
            throw new \RuntimeException("{$preparedPath} is not prepared by the filemanager");
        }
        unset($this->preparedPaths[$i]);
        @unlink($preparedPath);
        $file->move(dirname($preparedPath), basename($preparedPath));
    }


    /**
     * Removes all prepared paths that weren't used.
     */
    public function __destruct()
    {
        foreach ($this->preparedPaths as $file) {
            unlink($file);
        }
    }


    /**
     * Delete a file path
     *
     * @param string $filePath
     * @return bool
     */
    public function delete($filePath)
    {
        if (is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }


    /**
     * Propose a file name based on the uploaded file name.
     *
     * @param File $file
     * @param string $suffix
     * @return mixed|string
     */
    public function proposeFilename(File $file, $suffix)
    {
        if ($file instanceof UploadedFile) {
            $fileName = $file->getClientOriginalName();
        } else {
            $fileName = $file->getBasename();
        }
        $ret = preg_replace('/[^\w.]+/', '-', strtolower($fileName));
        $ret = preg_replace('/-+/', '-', $ret);
        if ($suffix) {
            $ext = (string)pathinfo($ret, PATHINFO_EXTENSION);
            $fn = (string)pathinfo($ret, PATHINFO_FILENAME);
            $ret = sprintf('%s-%d.%s', trim($fn, '.'), $suffix, $ext);
        }
        return $ret;
    }


    /**
     * Returns the relative path for the specified entity / field combination.
     * By default, the entity's local class name is lowercased and the field is used as-is.
     *
     * @param mixed $entity
     * @param string $field
     * @return string
     */
    public function getRelativePath($entity, $field)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }
        $entity = strtolower(preg_replace('/(.*\\\\|^)([^\\\\]+)$/', '$2', $entity));;

        return $entity . '/' . $field;
    }


    /**
     * Returns the local (filesystem) path to the entities' storage.
     *
     * @param mixed $entity
     * @param mixed $field
     * @return string
     */
    public function getDir($entity, $field)
    {
        return $this->root . '/' . $this->getRelativePath($entity, $field);
    }


    /**
     * Return the url to the file.
     *
     * @param mixed $entity
     * @param string $field
     * @param mixed $fileName
     * @return null|string
     */
    public function getFileUrl($entity, $field, $fileName = null)
    {
        if (func_num_args() < 3) {
            if ($entity) {
                $fileName = PropertyHelper::getValue($entity, $field);
            }
        }

        if ($fileName) {
            return ltrim($this->httpRoot . '/' . $this->getRelativePath($entity, $field) . '/' . $fileName, '/');
        }
        return null;
    }


    /**
     * Returns the file path for the given entity / property combination
     *
     * @param mixed $entity
     * @param string $field
     * @param null $fileName
     * @return null|string
     */
    public function getFilePath($entity, $field, $fileName = null)
    {
        if (func_num_args() < 3) {
            if ($entity) {
                $fileName = PropertyHelper::getValue($entity, $field);
            }
        }

        if ($fileName) {
            return $this->getDir($entity, $field) . '/' . $fileName;
        }
        return null;
    }
}