<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Metadata\MetadataFactory;
use Zicht\Util\Str;

/**
 * Helper to determine the relevant class names having File annotations
 */
class EntityHelper
{
    /**
     * Constructor.
     *
     * @param \Metadata\MetadataFactory $registry
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     */
    public function __construct(MetadataFactory $registry, Registry $doctrine, $kernel)
    {
        $this->metadata = $registry;
        $this->doctrine = $doctrine;
        $this->kernel = $kernel;
    }


    /**
     * Returns all entities having file annotations
     *
     * @return array
     */
    public function getManagedEntities()
    {
        $entities = array();
        /** @var \Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle */
        foreach ($this->kernel->getBundles() as $bundle) {
            $entityPath = $bundle->getPath() . '/Entity';
            if (is_dir($entityPath)) {
                $iter = new \RegexIterator(
                    new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($entityPath)
                    ),
                    '/\.php$/'
                );

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
