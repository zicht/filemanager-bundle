<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Twig;

use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;

class FileManagerExtension extends \Twig_Extension
{
    function __construct(FileManager $fm) {
        $this->fm = $fm;
    }

    function getFunctions() {
        return array(
            'file_url' => new \Twig_Function_Method(
                $this,
                'getFileUrl'
            ),
            'file_url_by_filename' => new \Twig_Function_Method(
                $this,
                'getFileUrlByFilename'
            )
        );
    }

    function getFileUrl($entity, $field) {
        return $this->fm->getFileUrl($entity, $field);
    }

    function getFileUrlByFilename($entity, $field, $value){
        return $this->fm->getFileUrlByFilename($entity, $field, $value);
    }


    function getName() {
        return 'filemanager';
    }
}