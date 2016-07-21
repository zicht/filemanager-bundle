<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Integrity;

use Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;

/**
 * Use the filesystem as source for checking the values (i.e., update the records if values aren't present on disk)
 */
class FilesystemChecker extends AbstractChecker
{
    /**
     * @{inheritDoc}
     */
    public function check($entityClass)
    {
        $this->setEntity($entityClass);

        $records = $this->repos->findAll();
        foreach ($records as $record) {
            /** @var \Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata $metadata */
            foreach ($this->classMetaData->propertyMetadata as $property => $metadata) {
                if (!isset($metadata->fileManager)) {
                    continue;
                }
                if (!PropertyHelper::getValue($record, $property)) {
                    continue;
                }
                $filePath = $this->fm->getFilePath($record, $property);

                if ($filePath && !is_file($filePath)) {
                    $this->log(sprintf('File does not exist: <info>%s</info>', $filePath));

                    if ($this->isPurge()) {
                        PropertyHelper::setValue($record, $property, '');
                        $this->doctrine->getManager()->persist($record);
                        $this->doctrine->getManager()->flush();
                    }
                } elseif ($filePath) {
                    $basename = basename($filePath);
                    $this->log("File exists: <info>{$basename}</info>", 1);
                }
            }
        }
    }
}