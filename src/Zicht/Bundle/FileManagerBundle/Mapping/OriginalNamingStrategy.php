<?php
/**
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Mapping;

/**
 * Class OriginalNamingStrategy
 *
 * @package Zicht\Bundle\FileManagerBundle\Mapping
 */
class OriginalNamingStrategy implements NamingStrategyInterface
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
     * @param string $fileName
     * @param string|int $suffix
     * @return string
     */
    public function normalize($fileName, $suffix = 0)
    {
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
