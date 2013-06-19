<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Integrity;

use \Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use \Metadata\MetadataFactoryInterface;
use \Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * Base class for the integrity checkers
 */
abstract class AbstractChecker implements CheckerInterface
{
    private $logger = null;
    private $purge = false;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repos;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $classMetaData;

    /**
     * Constructor.
     *
     * @param \Zicht\Bundle\FileManagerBundle\FileManager\FileManager $fm
     * @param \Metadata\MetadataFactoryInterface $metadataFactory
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     */
    public function __construct(FileManager $fm, MetadataFactoryInterface $metadataFactory, Registry $doctrine)
    {
        $this->fm = $fm;
        $this->metadataFactory = $metadataFactory;
        $this->doctrine = $doctrine;
    }


    /**
     * Set the Entity being processed.
     *
     * @param string $entityClass
     * @return void
     */
    protected function setEntity($entityClass)
    {
        $this->repos = $this->doctrine->getRepository($entityClass);
        $this->className = $this->repos->getClassName();
        $this->classMetaData = $this->metadataFactory->getMetadataForClass($this->className);
    }

    /**
     * Write a log to the logger
     *
     * @param string $str
     * @param int $logLevel
     * @return void
     */
    protected final function log($str, $logLevel = 0)
    {
        if (isset($this->logger)) {
            call_user_func($this->logger, $str, $logLevel);
        }
    }

    /**
     * Set a logger callback for verbose output
     *
     * @param callable $callable
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function setLoggingCallback($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException("Not a callable");
        }
        $this->logger = $callable;
    }

    /**
     * @{inheritDoc}
     */
    public function setPurge($purge)
    {
        $this->purge = $purge;
    }


    /**
     * Checks if purge option is set
     *
     * @return bool
     */
    protected function isPurge()
    {
        return $this->purge;
    }
}
