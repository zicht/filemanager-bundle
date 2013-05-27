<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Zicht\Bundle\FileManagerBundle\Doctrine\EntityHelper;
use Zicht\Util\Str;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;

/**
 * A command to check if the files in the database are in sync with the files on disk or vice versa.
 */
class FileCheckCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    public function configure()
    {
        $this
            ->setName('zicht:filemanager:check')
            ->addArgument('entity', InputArgument::OPTIONAL, 'The entity to check.', null)
            ->addOption('purge', '', InputOption::VALUE_NONE, 'Purge the values that do not exist')
            ->addOption('inverse', '', InputOption::VALUE_NONE, 'Inverse the check: check files against database')
            ->setDescription("Checks if all managed files in the database exist on disk or vice versa.")
            ->setHelp(
                "Checks all of the values in the database and checks if the file exists on disk.\n\n"
                . "Pass the --purge to reset the values to NULL in the database\n"
                . "Pass --inverse to check if all files on disk are actually present in the database.\n"
                . "Passing both --inverse and --purge will delete all files that are not present in the database"
             )
        ;
    }


    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var $repos \Doctrine\ORM\EntityRepository */
        $metadataFactory = $this->getContainer()->get('zicht_filemanager.metadata_factory');
        $fileManager  = $this->getContainer()->get('zicht_filemanager.filemanager');

        if ($entity = $input->getArgument('entity')) {
            $entities = array($entity);
        } else {
            $kernel = $this->getContainer()->get('kernel');
            $helper = new EntityHelper($metadataFactory, $doctrine);
            $entities = $helper->getManagedEntities($kernel->getBundles());
        }

        foreach ($entities as $entity) {
            $repos = $doctrine->getRepository($entity);

            $className = $repos->getClassName();
            $classMetaData = $metadataFactory->getMetadataForClass($className);

            $output->writeln("Checking entity {$entity}");
            if ($input->getOption('inverse')) {
                $fileNames = array();
                $records = $repos->findAll();
                foreach ($classMetaData->propertyMetadata as $property => $metadata) {
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

                    $fileDir = $fileManager->getDir($className, $property);
                    if (is_dir($fileDir)) {
                        foreach (new \DirectoryIterator($fileDir) as $file) {
                            if (!$file->isFile()) {
                                continue;
                            }
                            $basename = $file->getBasename();
                            if (!in_array($basename, $fileNames)) {
                                if ($input->getOption('purge')) {
                                    unlink($file->getPathname());
                                    $output->writeln("Deleted: <info>{$basename}</info>");
                                } else {
                                    $output->writeln("Not used: <comment>{$basename}</comment>");
                                }
                            }
                        }
                    } else {
                        $output->writeln("<error>Directory does not exist: {$fileDir}</error>");
                    }
                }
            } else {
                foreach ($repos->findAll() as $entity) {
                    /** @var \Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata $metadata */
                    foreach ($classMetaData->propertyMetadata as $property => $metadata) {
                        if (!isset($metadata->fileManager)) {
                            continue;
                        }
                        $filePath = $fileManager->getFilePath($entity, $property);

                        if ($filePath && !is_file($filePath)) {
                            $output->writeln(sprintf('File <info>%s</info> does not exist', $filePath));

                            if ($input->getOption('purge')) {
                                PropertyHelper::setValue($entity, $property, null);
                                $doctrine->getManager()->persist($entity);
                                $doctrine->getManager()->flush();
                            }
                        }
                    }
                }
            }
        }
    }
}