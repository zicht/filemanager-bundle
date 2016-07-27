<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Twig;

use Twig_SimpleFunction;
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;

/**
 * The twig extension providing the 'file_url' twig function.
 */
class FileManagerExtension extends \Twig_Extension
{
    protected $fm;

    /**
     * Constructor.
     *
     * @param \Zicht\Bundle\FileManagerBundle\FileManager\FileManager $fm
     */
    public function __construct(FileManager $fm)
    {
        $this->fm = $fm;
    }


    /**
     * @{inheritDoc}
     */
    public function getFunctions()
    {
        return array(
            'file_url' => new Twig_SimpleFunction(
                'file_url',
                [$this, 'getFileUrl']
            )
        );
    }


    /**
     * Returns the file url for the specified entity/field combination. If the value is also provided, it is
     * used in stead of the entity's value for the passed field.
     *
     * @param mixed $entity
     * @param string $field
     * @param mixed $value
     * @return string|null
     */
    public function getFileUrl($entity, $field, $value = null)
    {
        return call_user_func_array(array($this->fm, 'getFileUrl'), func_get_args());
    }


    /**
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'zicht_filemanager';
    }
}
