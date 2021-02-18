<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Twig;

use Twig\Extension\AbstractExtension;
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use Twig\TwigFunction;

/**
 * The twig extension providing the 'file_url' twig function.
 */
class FileManagerExtension extends AbstractExtension
{
    protected $fm;

    /**
     * @param \Zicht\Bundle\FileManagerBundle\FileManager\FileManager $fm
     */
    public function __construct(FileManager $fm)
    {
        $this->fm = $fm;
    }


    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            'file_url' => new TwigFunction('file_url', [$this, 'getFileUrl']),
            'file_urls' => new TwigFunction('file_urls', [$this, 'getFileUrls']),
        ];
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
        return call_user_func_array([$this->fm, 'getFileUrl'], func_get_args());
    }

    /**
     * Returns a list of file urls for the specified entity/field combination.
     *
     * @param mixed[] $entities
     * @param string $field
     * @return string|null
     */
    public function getFileUrls($entities, $field)
    {
        $urls = [];
        foreach ($entities as $entity) {
            $urls [] = $this->fm->getFileUrl($entity, $field);
        }
        return $urls;
    }


    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'zicht_filemanager';
    }
}
