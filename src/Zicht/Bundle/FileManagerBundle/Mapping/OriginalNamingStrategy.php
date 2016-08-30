<?php
/**
 * @author Muhammed Akbulut <muhammed@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Mapping;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class OriginalNamingStrategy
 *
 * @package Zicht\Bundle\FileManagerBundle\Mapping
 */
class OriginalNamingStrategy implements NamingStrategy
{
    /**
     * @var bool
     */
    private $casePreservation;

    /**
     * NamingStrategy constructor.
     *
     * @param bool $casePreservation
     */
    public function __construct($casePreservation = self::PRESERVE_CASE)
    {
        $this->casePreservation = $casePreservation;
    }

    /**
     * Propose a file name based on the uploaded file name.
     *
     * @param File $file
     * @param string|int $suffix
     * @return string
     */
    public function normalize(File $file, $suffix = 0)
    {
        if ($file instanceof UploadedFile) {
            $fileName = $file->getClientOriginalName();
        } else {
            $fileName = $file->getBasename();
        }

        if ($this->casePreservation === self::LOWER_CASE) {
            $fileName = strtolower($fileName);
        }

        if ($suffix !== 0) {
            $ext = (string)pathinfo($fileName, PATHINFO_EXTENSION);
            $fn = (string)pathinfo($fileName, PATHINFO_FILENAME);
            $fileName = sprintf('%s-%d.%s', trim($fn, '.'), $suffix, $ext);
        }

        return $fileName;
    }
}
