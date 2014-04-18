<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Form\Transformer;

use \Symfony\Component\Form\DataTransformerInterface;
use \Symfony\Component\HttpFoundation\File\File;
use \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Zicht\Bundle\FileManagerBundle\Form\FileType;

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

    /**
     * Transforms File -> string
     */

    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        if (is_array($value) && array_key_exists(FileType::UPLOAD_FIELDNAME, $value)) {
            return $value[FileType::UPLOAD_FIELDNAME];
        }

        return null;
    }


    /**
     * @{inheritDoc}
     */

    /**
     * Transforms string -> File
     */

    public function transform($value)
    {
        if(is_array($value)) {
            $value = $value[FileType::UPLOAD_FIELDNAME];
        }

        try {
            return array(
                FileType::UPLOAD_FIELDNAME => new File(call_user_func($this->callback, $value))
//                FileType::HASH_FIELDNAME => '',
//                FileType::FILENAME_FIELDNAME => ''
            );

        } catch (FileNotFoundException $e) {
            return null;
        }
    }
}