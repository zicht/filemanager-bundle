<?php
/**
 * @author    Philip Bergman <philip@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;

class CreateMimeFileCommand extends ContainerAwareCommand
{
    const MIME_FILE = '/etc/mime.types';

    /**
     * @{inheritDoc}
     */
    public function configure()
    {
        $this
            ->setName('zicht:filemanager:create:mime')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to overwrite existing file')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do a dry run')
            ->setDescription("Makes a (yml) config file for available mime types")
            ->setHelp(<<<EOH
    Makes a (yml) config file for available mime types, it reads /etc/mime.types
    and creates from this file a yml file that can be used for the file_types option

    So it knows that jpg is image/jpeg and can check the mime types and limit input fields

    Usage:
     zicht:filemanager:create:mime [-d(ry-run)] [-f(force)]
EOH

            );
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');
        $dumper = new Dumper();
        $file = sprintf('%s/../Resources/config/mime.yml', __DIR__);

        $result = array();

        if (false !== $content = file_get_contents(self::MIME_FILE)) {
            foreach (explode("\n", $content) as $line) {
                if (false == preg_match('/^#/', $line) && strlen(trim($line)) > 0) {
                    $data = preg_split('/\s+/', $line);
                    for ($i = 1; $i <= count($data) - 1; $i++) {
                        $result[$data[$i]] = $data[0];
                    }
                }
            }
        } else {
            throw new \RuntimeException(sprintf('could not open file: %s', self::MIME_FILE));
        }

        $ymlDump = $dumper->dump($result, 2);

        if ((false === file_exists($file) || $force) && !$dryRun) {
            if (false === file_put_contents($file, $ymlDump)) {
                throw new \RuntimeException(sprintf('could not write to file: %s', $file));
            } else {

                $output->writeln(sprintf('Successful writing file: <info>%s</info>', realpath($file)));
            }
        } elseif (file_exists($file) && !$dryRun) {
            $output->writeln('Mime file exists, use force options to overwrite');
        } elseif ($dryRun) {
            echo $ymlDump;
        }
    }
}