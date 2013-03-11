<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\FileManager;

use Symfony\Component\HttpFoundation\File\File;
use Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;

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
     * @param $root
     */
    function __construct($root, $httpRoot) {
        $this->root = rtrim($root, '/');
        $this->httpRoot = rtrim($httpRoot, '/');
        $this->preparedPaths = array();
    }


    function prepare(File $file, $entity, $name, $noclobber = true)
    {
        $dir = $this->getDir($entity, $name);
        $i = 0;
        do {
            $f = $this->proposeFilename($file, $i ++);
            $pathname = $dir . '/' . $f;
        } while($noclobber && is_file($pathname));
        touch($pathname);
        $this->preparedPaths[]= $pathname;
        return $pathname;
    }


    function save(File $file, $preparedPath)
    {
        if (false === ($i = array_search($preparedPath, $this->preparedPaths))) {
            throw new \RuntimeException("{$preparedPath} is not prepared by the filemanager");
        }
        unset($this->preparedPaths[$i]);
        @unlink($preparedPath);
        $file->move(dirname($preparedPath), basename($preparedPath));
    }


    public function __destruct()
    {
        $this->flush();
    }



    public function flush()
    {
        foreach ($this->preparedPaths as $file) {
            unlink($file);
        }
    }

    public function delete($filePath)
    {
        if (is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }


    function remove($entity, $name)
    {
        return $this->delete($this->getFilePath($entity, $name));
    }


    /**
     * Propose a file name based on the uploaded file name.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param $suffix
     * @return mixed|string
     */
    function proposeFilename(File $file, $suffix) {
        if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            $fileName = $file->getClientOriginalName();
        } else {
            $fileName = $file->getBasename();
        }
        $ret = preg_replace('/[^\w.]+/', '-', strtolower($fileName));
        $ret = preg_replace('/-+/', '-', $ret);
        if($suffix) {
            $ext = (string)pathinfo($ret, PATHINFO_EXTENSION);
            $fn = (string)pathinfo($ret, PATHINFO_FILENAME);
            $ret = sprintf('%s-%d.%s', trim($fn, '.'), $suffix, $ext);
        }
        return $ret;
    }


    function getRelativePath($entity, $field) {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }
        $entity = strtolower(preg_replace('/(.*\\\\|^)([^\\\\]+)$/', '$2', $entity));;

        return $entity . '/' . $field;
    }


    function getDir($entity, $field) {
        return $this->root . '/' . $this->getRelativePath($entity, $field);
    }


    function getFileUrl($entity, $name, $value = null) {
        if (func_num_args() < 3) {
            if ($entity && ($fileName = PropertyHelper::getValue($entity, $name))) {
                if ($fileName instanceof File) {
                    $fileName = $fileName->getBasename();
                }
                $value = $fileName;
            }
        } else {
            $value = basename($value);
        }

        if ($value) {
            return ltrim($this->httpRoot . '/' . $this->getRelativePath($entity, $name) . '/' . $value, '/');
        }
        return null;
    }

    /**
     * @param $entity       Entity that holds the file property
     * @param $property     Name of file property
     * @param $name         Name of file on filesystem
     * @return null|string  Path to the file on filesystem
     * @deprecated use getFileUrl() in stead
     */
    function getFileUrlByFilename($entity, $property, $name){
        trigger_error("Use getFileUrl() instead", E_USER_DEPRECATED);
        return $this->getFileUrl($entity, $property, $name);
    }

    /**
     * Returns the file path for the given entity / property combination
     *
     * @param $entity
     * @param $name
     * @param null $fileName
     * @return null|string
     */
    function getFilePath($entity, $name, $fileName = null) {
        if (func_num_args() < 3) {
            if ($entity && ($fileName = PropertyHelper::getValue($entity, $name))) {
                if ($fileName instanceof File) {
                    $fileName = $fileName->getBasename();
                }
            }
        }

        if ($fileName) {
            return $this->getDir($entity, $name) . '/' . $fileName;
        }
        return null;
    }
}