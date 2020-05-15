<?php
/**
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Mapping;

/**
 * Interface NamingStrategyInterface
 *
 * @package Zicht\Bundle\FileManagerBundle\Mapping
 */
interface NamingStrategyInterface
{
    /**
     * Preserves case when set.
     */
    const PRESERVE_CASE = true;

    /**
     * Lower's case when set
     */
    const LOWER_CASE = false;

    /**
     * NamingStrategy constructor.
     *
     * @param bool $casePreservation
     */
    public function __construct($casePreservation = self::LOWER_CASE);

    /**
     * Create a name according to the strategies wanted method
     *
     * @param string $fileName
     * @param string|int $suffix
     * @return string
     */
    public function normalize($fileName, $suffix = 0);
}
