<?php
/**
 * @author Muhammed Akbulut <muhammed@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Mapping;

/**
 * Class DefaultNamingStrategy
 *
 * @package Zicht\Bundle\FileManagerBundle\Mapping
 */
class DefaultNamingStrategy implements NamingStrategyInterface
{
    /**
     * @var bool
     */
    private $casePreservation;

    /**
     * DefaultNamingStrategy constructor.
     *
     * @param bool $casePreservation
     */
    public function __construct($casePreservation = self::LOWER_CASE)
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

        $ret = preg_replace('/[^\w.]+/', '-', $fileName);
        $ret = preg_replace('/-+/', '-', $ret);

        if ($suffix !== 0) {
            $ext = (string)pathinfo($ret, PATHINFO_EXTENSION);
            $fn = (string)pathinfo($ret, PATHINFO_FILENAME);
            $ret = sprintf('%s-%d.%s', trim($fn, '.'), $suffix, $ext);
        }

        return $ret;
    }
}
