<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Zicht\Bundle\FileManagerBundle\Form\FileType;

/**
 * File transformer for FileType fields, converts a local value into a File instance. The callback passed
 * at construction time determines the local file path of the passed value.
 */
class FileTransformer implements DataTransformerInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @var string
     */
    private $property;

    /**
     * @param callback $callback
     * @param string $property
     */
    public function __construct($callback, $property)
    {
        $this->callback = $callback;
        $this->property = $property;
    }

    /**
     * Transforms File -> string (to database)
     *
     * @param mixed $value
     * @return mixed|null
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
     * Transforms string (from database) -> File
     *
     * @param mixed $value
     * @return File[]|null
     */
    public function transform($value)
    {
        if (is_array($value)) {
            $value = $value[FileType::UPLOAD_FIELDNAME];
        }

        $path = call_user_func($this->callback, $value, $this->property);
        if (!is_string($path)) {
            return null;
        }

        try {
            return [FileType::UPLOAD_FIELDNAME => new File($path)];
        } catch (FileNotFoundException $e) {
            return null;
        }
    }
}
