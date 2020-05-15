<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\FileManagerBundle\Doctrine\EntityHelper;
use Zicht\Bundle\FileManagerBundle\Integrity\DatabaseChecker;
use Zicht\Bundle\FileManagerBundle\Integrity\FilesystemChecker;

/**
 * A command to check if the files in the database are in sync with the files on disk or vice versa.
 */
class FileCheckCommand extends Command
{
    protected static $defaultName = 'zicht:filemanager:check';
    /**
     * @var DatabaseChecker
     */
    private $databaseChecker;
    /**
     * @var FilesystemChecker
     */
    private $filesystemChecker;
    /**
     * @var EntityHelper
     */
    private $entityHelper;

    public function __construct(DatabaseChecker $databaseChecker, FilesystemChecker $filesystemChecker, EntityHelper $entityHelper, string $name = null)
    {
        parent::__construct($name);
        $this->databaseChecker = $databaseChecker;
        $this->filesystemChecker = $filesystemChecker;
        $this->entityHelper = $entityHelper;
    }

    /**
     * @{inheritDoc}
     */
    public function configure()
    {
        $this
            ->addArgument('entity', InputArgument::OPTIONAL, 'The entity to check.', null)
            ->addOption('purge', '', InputOption::VALUE_NONE, 'Purge the values that do not exist')
            ->addOption('inverse', '', InputOption::VALUE_NONE, 'Inverse the check: check files against database')
            ->setDescription("Checks if all managed files in the database exist on disk or vice versa.")
            ->setHelp(
                "Checks all of the values in the database and checks if the file exists on disk.\n\n"
                . "Pass the --purge to reset the values to NULL in the database\n"
                . "Pass --inverse to check if all files on disk are actually present in the database.\n"
                . "Passing both --inverse and --purge will delete all files that are not present in the database"
            );
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('inverse')) {
            $checker = $this->databaseChecker;
        } else {
            $checker = $this->filesystemChecker;
        }

        if ($entityClass = $input->getArgument('entity')) {
            $entityClasses = array($entityClass);
        } else {
            $entityClasses = $this->entityHelper->getManagedEntities();
        }

        if ($input->getOption('purge')) {
            $checker->setPurge(true);
        }

        $fnLogger = function ($str, $verbosity) use ($output) {
            if ($output->getVerbosity() > $verbosity) {
                $output->writeln($str);
            }
        };
        $checker->setLoggingCallback($fnLogger);
        foreach ($entityClasses as $entityClass) {
            $output->writeln("Checking entity {$entityClass}");
            $checker->check($entityClass);
        }
    }
}
