<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Integrity;

use Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;

class FilesystemChecker extends AbstractChecker
{
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
                    $this->log(sprintf('File <info>%s</info> does not exist', $filePath));

                    if ($this->isPurge()) {
                        PropertyHelper::setValue($record, $property, '');
                        $this->doctrine->getManager()->persist($record);
                        $this->doctrine->getManager()->flush();
                    }
                } else {
                    $basename = basename($filePath);
                    $this->log("File exists: <info>{$basename}</info>", 1);
                }
            }
        }
    }
}