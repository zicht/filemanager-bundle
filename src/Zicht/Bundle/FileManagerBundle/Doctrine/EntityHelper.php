<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Doctrine;

use Metadata\MetadataFactory;
use Zicht\Util\Str;

class EntityHelper
{
    public function __construct(MetadataFactory $registry, \Doctrine\Bundle\DoctrineBundle\Registry $doctrine, $kernel)
    {
        $this->metadata = $registry;
        $this->doctrine = $doctrine;
        $this->kernel = $kernel;
    }


    public function getManagedEntities()
    {
        $entities = array();
        foreach ($this->kernel->getBundles() as $bundle) {
            $entityPath = $bundle->getPath() . '/Entity';
            if (is_dir($entityPath)) {
                $iter = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($entityPath)), '/\.php$/');

                foreach ($iter as $file) {
                    $entityName = substr(
                        ltrim(str_replace(realpath($entityPath), '', realpath($file->getPathname())), '/'),
                        0,
                        -4
                    );

                    $alias = Str::classname(get_class($bundle)) . ':' . str_replace('/', '\\', $entityName);
                    try {
                        $repos = $this->doctrine->getRepository($alias);

                        $className     = $repos->getClassName();
                        $classMetaData = $this->metadata->getMetadataForClass($className);
                        foreach ($classMetaData->propertyMetadata as $property => $metadata) {
                            if (isset($metadata->fileManager)) {
                                $entities[] = $alias;
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        return $entities;
    }
}