<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
  */

namespace Zicht\Bundle\FileManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use Zicht\Bundle\FileManagerBundle\Helper\PurgatoryHelper;

//class MyDataTransformer implements DataTransformerInterface
//{
//    public function transform($value)
//    {
//        if (null === $value)
//            return;
//
//        if (is_string($value)) {
////        if ($value instanceof File)
//            return array(
//                FileType::UPLOAD_FIELDNAME => $value . '-transformed',
////                FileType::HASH_FIELDNAME => '',
////                FileType::FILENAME_FIELDNAME => ''
//            );
//        }
//
//        return null;
//    }
//
//    public function reverseTransform($value)
//    {
//        if (null === $value) {
//            return null;
//        }
//
//        if (is_array($value) && array_key_exists(FileType::UPLOAD_FIELDNAME, $value)) {
//            return $value[FileType::UPLOAD_FIELDNAME] . 'reverse-transformed';
//        }
//
//        return null;
//    }
//}

class FileType extends AbstractType
{
    const UPLOAD_FIELDNAME   = 'upload_file';
    const HASH_FIELDNAME     = 'hash';
    const FILENAME_FIELDNAME = 'filename';

    /**
     * Constructor.
     *
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @{inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
//                'data_class' => null,
                'entity' => null,
                'property' => null,
                'show_current_file' => true
            )
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $fm = $this->fileManager;

        $builder->add(self::UPLOAD_FIELDNAME, 'file');
        $builder->add(self::HASH_FIELDNAME, 'text', array('mapped' => false)); //, array('read_only' => true));*
        $builder->add(self::FILENAME_FIELDNAME, 'text', array('mapped' => false)); //, array('read_only' => true));

        $builder->addViewTransformer(
            new Transformer\FileTransformer(
                function($value) use($fm, $builder) {
                    return $fm->getFilePath(
                        $builder->getAttribute('entity'),
                        $builder->getAttribute('property'),
                        $value
                    );
                }
            )
        );

//      TODO: show yes/no when option is set / or not
//      $builder->add('remove', 'checkbox', array('label' => 'You wanna remove?'));

        $builder->setAttribute('entity', $builder->getParent()->getDataClass());
        $builder->setAttribute('property', $builder->getName());

        $fileTypeSubscriber = new FileTypeSubscriber(
            $fm,
            $builder->getAttribute('entity'),
            $builder->getAttribute('property')
        );
        $builder->addEventSubscriber($fileTypeSubscriber);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entity'] = $form->getConfig()->getAttribute('entity');
        $view->vars['property'] = $form->getConfig()->getAttribute('property');
        $view->vars['show_current_file']= $form->getConfig()->getOption('show_current_file');
        $view->vars['multipart'] = true;

        $entity = $view->vars['entity'];
        $field  = $view->vars['property'];

        echo "view->vars['value']:" . PHP_EOL;
        var_dump($view->vars['value']);
        echo "----";

        if($view->vars['value'] && is_array($view->vars['value'])  && array_key_exists(FileType::UPLOAD_FIELDNAME, $view->vars['value'])) {

            $view->vars['file_url'] = $this->fileManager->getFileUrl($entity, $field, $view->vars['value'][FileType::UPLOAD_FIELDNAME]);

        } else {
//            $data = $form->getData();
//
//            if (null !== $data && is_array($data) && isset($data[FileType::UPLOAD_FIELDNAME]) && $data[FileType::UPLOAD_FIELDNAME] instanceof File) {
//                $purgatoryFileManager = clone $this->fileManager;
//                $purgatoryFileManager->setHttpRoot($purgatoryFileManager->getHttpRoot() . '/purgatory');
//
//                $view->vars['file_url'] = $purgatoryFileManager->getFileUrl($entity, $field, $data[FileType::UPLOAD_FIELDNAME]);
//            }
////
//            $view->vars['purgatory_field_postfix'] = PurgatoryHelper::makePostFix($entity, $field);
//            $view->vars['purgatory_file_filename'] = $form->getData()->getBaseName();
//            $view->vars['purgatory_file_hash'] = PurgatoryHelper::makeHash($entity, $field, $view->vars['purgatory_file_filename']);
////
////                /** @var FormFactoryInterface $factory */
//            $factory = $form->getConfig()->getAttribute('factory');
//
//            $hashForm = $factory->create();
//            $hashForm->add('hash', 'text', array('read_only' => true, 'data' => 'hash-1-2-3', 'mapped' => false));
//            $hashForm->add('filename', 'text', array('read_only' => true, 'data' => 'filename-123    ', 'mapped' => false));
//
//            $view->children['hashForm'] = $hashForm->createView($view);
        }
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'zicht_file';
    }

    public function getParent()
    {
        //default is 'form' - but overwritten to express that this is done on purpose
        return parent::getParent();
    }
}