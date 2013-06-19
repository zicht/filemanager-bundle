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
        /** @var $checker \Zicht\Bundle\FileManagerBundle\Integrity\CheckerInterface */
        $container = $this->getContainer();

        if ($input->getOption('inverse')) {
            $checker = $container->get('zicht_filemanager.integrity_checker.database');
        } else {
            $checker = $container->get('zicht_filemanager.integrity_checker.filesystem');
        }

        if ($entityClass = $input->getArgument('entity')) {
            $entityClasses = array($entityClass);
        } else {
            $entityClasses = $container->get('zicht_filemanager.entity_helper')->getManagedEntities();
        }

        if ($input->getOption('purge')) {
            $checker->setPurge(true);
        }

        $checker->setLoggingCallback(function($str, $verbosity) use($output) {
            if ($output->getVerbosity() > $verbosity) {
                $output->writeln($str);
            }
        });
        foreach ($entityClasses as $entityClass) {
            $output->writeln("Checking entity {$entityClass}");
            $checker->check($entityClass);
        }
    }
}