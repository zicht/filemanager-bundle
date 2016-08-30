<?php
/**
 * @author Muhammed Akbulut <muhammed@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Mapping;

use Symfony\Component\HttpFoundation\File\File;

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
     * @param File $file
     * @param string|int $suffix
     * @return string
     */
    public function normalize(File $file, $suffix = 0);
}
