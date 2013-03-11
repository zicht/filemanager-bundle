<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Form\Transformer;

use \Symfony\Component\Form\DataTransformerInterface;
use \Symfony\Component\HttpFoundation\File\File;
use \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * File transformer for FileType fields, converts a local value into a File instance. The callback passed
 * at construction time determines the local file path of the passed value.
 */
class FileTransformer implements DataTransformerInterface
{
    /**
     * Constructor.
     *
     * @param callback $callback
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }


    /**
     * @{inheritDoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }


    /**
     * @{inheritDoc}
     */
    public function transform($value)
    {
        try {
            return new File(call_user_func($this->callback, $value));
        } catch (FileNotFoundException $e) {
            return null;
        }
    }
}