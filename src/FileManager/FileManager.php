<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\FileManager;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;
use Zicht\Bundle\FileManagerBundle\Event\ResourceEvent;
use Zicht\Bundle\FileManagerBundle\Mapping\NamingStrategyInterface;
use Zicht\Util\Str;

/**
 * Storage layer for files.
 *
 * The save and getFilePath() use a simple mapping based on an entity and field name. The entity name is based on
 * either a string or a class name, where the class name is mapped to the local class name and lowercased.
 */
class FileManager
{
    private $fs;
    private $root;
    private $httpRoot;
    private $preparedPaths;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var [CacheManager, FilterConfiguration]
     */
    private $imagineConfig;

    /**
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @param \Symfony\Component\Filesystem\Filesystem $fs
     * @param string $root
     * @param string $httpRoot
     * @param NamingStrategyInterface $namingStrategy
     */
    public function __construct(Filesystem $fs, $root, $httpRoot, NamingStrategyInterface $namingStrategy)
    {
        $this->fs = $fs;
        $this->root = rtrim($root, '/');
        $this->httpRoot = rtrim($httpRoot, '/');
        $this->preparedPaths = [];
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * Set the imagine config to allow for flushing of specific cached files
     *
     * @param CacheManager $cacheManager
     * @param FilterConfiguration $configuration
     */
    public function setImagineConfig(CacheManager $cacheManager, FilterConfiguration $configuration)
    {
        $this->imagineConfig = [$cacheManager, $configuration];
    }


    /**
     * Prepares a file for upload, which means that a stub file is created at the point where the file
     * otherwise would be uploaded.
     *
     * @param File $file
     * @param mixed $entity
     * @param string $field
     * @param bool $noclobber
     * @param string $forceFilename
     * @return string
     */
    public function prepare(File $file, $entity, $field, $noclobber = true, $forceFilename = '')
    {
        $dir = $this->getDir($entity, $field);
        if ($forceFilename) {
            $pathname = $dir . '/' . $forceFilename;
        } else {
            if ($file instanceof UploadedFile) {
                $fileName = $file->getClientOriginalName();
            } else {
                $fileName = $file->getBasename();
            }

            $i = 0;
            do {
                $f = $this->namingStrategy->normalize($fileName, $i++);
                $pathname = $dir . '/' . $f;
            } while ($noclobber && $this->fs->exists($pathname));
            $this->fs->mkdir(dirname($pathname), 0777 & ~umask(), true);
            $this->fs->touch($pathname);
        }
        $this->preparedPaths[] = $pathname;
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
        $existed = $this->fs->exists($preparedPath);
        @$this->fs->remove($preparedPath);

        try {
            $this->dispatchEvent($existed ? ResourceEvent::REPLACED : ResourceEvent::CREATED, $preparedPath);
            $file->move(dirname($preparedPath), basename($preparedPath));
        } catch (FileException $fileException) {
            throw new FileException(
                $fileException->getMessage() . "\n(hint: check the 'upload_max_filesize' in php.ini)",
                0,
                $fileException
            );
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
            $this->dispatchEvent(ResourceEvent::DELETED, $filePath);
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
        }

        if ($fileName instanceof File) {
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
        }

        if ($fileName instanceof File) {
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

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * Dispatch an event for changed resources
     *
     * @param string $eventType
     * @param string $filePath
     */
    private function dispatchEvent($eventType, $filePath)
    {
        if (null !== $this->eventDispatcher) {
            $relativePath = $this->fs->makePathRelative(dirname($filePath), $this->root) . basename($filePath);
            $this->eventDispatcher->dispatch(
                new ResourceEvent($relativePath, $this->httpRoot, $this->root),
                $eventType
            );

            if (null !== $this->imagineConfig) {
                if (false !== strpos($relativePath, '/../') || 0 === strpos($relativePath, '../')) {
                    // outside web root, stop.
                    return;
                }

                // Create events for the imagine cache as well.

                /** @var CacheManager $cacheManager */
                /** @var FilterConfiguration $filterConfig */
                list ($cacheManager, $filterConfig) = $this->imagineConfig;
                $webPath = $this->httpRoot . '/' . $relativePath;
                $cacheManager->remove($webPath);

                foreach ($filterConfig->all() as $name => $filter) {
                    $url = $cacheManager->resolve($webPath, $name);

                    // this weird construct is here because the imagine cache manager generates absolute urls
                    // even though they're local for some unapparent reason.
                    $relativeUrl = parse_url($url, PHP_URL_PATH);
                    $url = $this->fs->makePathRelative(dirname($relativeUrl), $this->root) . basename($relativeUrl);

                    if (false === strpos($url, '../')) {
                        $this->eventDispatcher->dispatch(
                            new ResourceEvent($url, $this->httpRoot, $this->root),
                            $eventType
                        );
                    }
                }
            }
        }
    }
}
