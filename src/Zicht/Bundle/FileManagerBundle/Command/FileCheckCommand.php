<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;
 
class FileCheckCommand extends ContainerAwareCommand
{
    function configure()
    {
        $this
            ->setName('zicht:filemanager:check')
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity to check.')
            ->addOption('purge', '', InputOption::VALUE_NONE, 'Purge the values that do not exist')
            ->addOption('inverse', '', InputOption::VALUE_NONE, 'Inverse the check: check files against database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var $repos \Doctrine\ORM\EntityRepository */
        $repos = $doctrine->getRepository($input->getArgument('entity'));

        $metadataFactory = $this->getContainer()->get('zicht_filemanager.metadata_factory');
        $fileManager  = $this->getContainer()->get('zicht_filemanager.filemanager');
        $className = $repos->getClassName();
        $classMetaData = $metadataFactory->getMetadataForClass($className);

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

                foreach (new \DirectoryIterator($fileManager->getDir($className, $property)) as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }
                    $basename = $file->getBasename();
                    if (!in_array($basename, $fileNames)) {
                        $output->writeln("File <comment>{$basename}</comment> is not used");
                        if ($input->getOption('purge')) {
                            unlink($file->getPathname());
                            $output->writeln("<info>{$basename}</info> deleted");
                        }
                    }
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
                            $entity->{$property . '_delete'} = true;
                            $doctrine->getManager()->persist($entity);
                            $doctrine->getManager()->flush();
                        }
                    }
                }
            }
        }
    }
}