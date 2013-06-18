<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Integrity;

use Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;

class DatabaseChecker extends AbstractChecker
{
    public function check($entityClass)
    {
        $this->setEntity($entityClass);

        $fileNames = array();
        $records = $this->repos->findAll();

        foreach ($this->classMetaData->propertyMetadata as $property => $metadata) {
            if (!isset($metadata->fileManager)) {
                continue;
            }

            // first gather all file property values
            foreach ($records as $entity) {
                $value = PropertyHelper::getValue($entity, $property);
                if ($value) {
                    $fileNames[]= $value;
                }
            }
            $this->log("-> found " . count($fileNames) . " values to check", 1);

            $fileDir = $this->fm->getDir($this->className, $property);

            if (is_dir($fileDir)) {
                foreach (new \DirectoryIterator($fileDir) as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }
                    $basename = $file->getBasename();
                    if (!in_array($basename, $fileNames)) {
                        if ($this->isPurge()) {
                            unlink($file->getPathname());
                            $this->log("Deleted:  <info>{$basename}</info>");
                        } else {
                            $this->log("Not used: <comment>{$basename}</comment>");
                        }
                    } else {
                        $this->log("Exists:   <info>{$basename}</info>", 1);
                    }
                }
            } else {
                $this->log("Dir does not exist: <error>{$fileDir}</error>");
            }
        }
    }
}