<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Form\Transformer;

use \Symfony\Component\Form\DataTransformerInterface;

/**
 * File transformer for FileType fields
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


    public function reverseTransform($value)
    {
        return $value;
    }


    public function transform($value)
    {
        try {
            return new \Symfony\Component\HttpFoundation\File\File(
                call_user_func($this->callback, $value)
            );
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e){
            return null;
        }
    }
}