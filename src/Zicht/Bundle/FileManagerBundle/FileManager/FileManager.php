<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\FileManager;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use \Symfony\Component\HttpFoundation\File\File;
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\HttpFoundation\File\UploadedFile;
use \Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;
use \Zicht\Util\Str;

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
     * @param \Symfony\Component\Filesystem\Filesystem $fs
     * @param string $root
     * @param string $httpRoot
     */
    public function __construct(FileSystem $fs, $root, $httpRoot)
    {
        $this->fs = $fs;
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
        } while ($noclobber && $this->fs->exists($pathname));
        $this->fs->mkdir(dirname($pathname), 0777 & ~umask(), true);
        $this->fs->touch($pathname);
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
        @$this->fs->remove($preparedPath);

        try{
            $file->move(dirname($preparedPath), basename($preparedPath));
        } catch(FileException $fileException) {
            throw new FileException($fileException->getMessage() . "\n(hint: check the 'upload_max_filesize' in php.ini)", 0, $fileException);
        }
    }


    /**
     * Removes all prepared paths that weren't used.
     */
    public function __destruct()
    {
        if (!empty($this->preparedPaths)) {
            foreach ($this->preparedPaths as $file) {
                @$this->fs->remove($file);
            }
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
        $relativePath = $this->fs->makePathRelative($filePath, $this->root);
        if (preg_match('!(^|/)\.\.!', $relativePath)) {
            throw new \RuntimeException("{$relativePath} does not seem to be managed by the filemanager");
        }
        if ($this->fs->exists($filePath)) {
            $this->fs->remove($filePath);
            return true;
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
        $entity = strtolower(Str::classname($entity));

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
        } elseif ($fileName instanceof File) {
            $fileName = $fileName->getBasename();
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
            if (is_object($entity)) {
                $fileName = PropertyHelper::getValue($entity, $field);
            }
        } elseif ($fileName instanceof File) {
            $fileName = $fileName->getBasename();
        }

        if ($fileName) {
            return $this->getDir($entity, $field) . '/' . $fileName;
        }
        return null;
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     *
     * Overwrite the defined root
     *
     * @param string $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }

    /**
     * @param string $httpRoot
     */
    public function setHttpRoot($httpRoot)
    {
        $this->httpRoot = $httpRoot;
    }

    /**
     * @return string
     */
    public function getHttpRoot()
    {
        return $this->httpRoot;
    }
}