<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\FileManager;

use Symfony\Component\HttpFoundation\File\File;

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
    }


    /**
     * Save an uploaded file and return the filename.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param $entity
     * @param $name
     * @param bool $noclobber
     * @return mixed|string
     */

    function save(File $file, $entity, $name, $noclobber = true) {
        $dir = $this->getDir($entity, $name);
        $i = 0;
        do {
            $f = $this->proposeFilename($file, $i ++);
        } while($noclobber && is_file($dir . '/' . $f));
        $file->move($dir, $f);
        return $f;
    }


    function remove($entity, $name) {
        $path = $this->getFilePath($entity, $name);
        if (is_file($path)) {
            return unlink($path);
        }
        return false;
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


    function getFileUrl($entity, $name) {
        if ($entity && ($fileName = $entity->{'get' . ucfirst($name)}())) {
            return ltrim($this->httpRoot . '/' . $this->getRelativePath($entity, $name) . '/' . $fileName, '/');
        }
        return null;
    }

    /**
     * @param $entity       Entity that holds the file property
     * @param $property     Name of file property
     * @param $name         Name of file on filesystem
     * @return null|string  Path to the file on filesystem
     */
    function getFileUrlByFilename($entity, $property, $name){
        if ($entity) {
            return ltrim($this->httpRoot . '/' . $this->getRelativePath($entity, $property) . '/' . $name, '/');
        }
        return null;
    }

    function getFilePath($entity, $name) {
        if ($entity && ($fileName = $entity->{'get' . ucfirst($name)}())) {
            return $this->getDir($entity, $name) . '/' . $fileName;
        }
        return null;
    }
}