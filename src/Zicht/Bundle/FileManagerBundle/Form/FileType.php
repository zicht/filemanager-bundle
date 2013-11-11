<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Form;

use \Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\HttpFoundation\File\File;
use \Symfony\Component\OptionsResolver\OptionsResolverInterface;
use \Symfony\Component\Form\FormEvents;
use \Symfony\Component\Form\FormView;
use \Symfony\Component\Form\FormInterface;

use Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;
use \Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use \Zicht\Bundle\FileManagerBundle\Form\FileTypeSubscriber;
use Zicht\Bundle\FileManagerBundle\Helper\PurgatoryHelper;


/**
 * Form type to use in conjunction with the @File annotated properties.
 *
 * The view contains the entity and property fields which can be used to render an url to file with the file_url()
 * function.
 */
class FileType extends AbstractType
{
    /**
     * Constructor.
     *
     * @param \Zicht\Bundle\FileManagerBundle\FileManager\FileManager $fileManager
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
                'compound'          => false,
                'data_class'        => 'Symfony\Component\HttpFoundation\File\File',
                'empty_data'        => null,
                'entity'            => null,
                'property'          => null,
                'show_current_file' => true
            )
        );
    }


    /**
     * @{inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $fm = $this->fileManager;
        $builder->setAttribute('entity', $builder->getParent()->getDataClass());
        $builder->setAttribute('property', $builder->getName());

        $fileTypeSubscriber = new FileTypeSubscriber(
            $fm,
            $builder->getAttribute('entity'),
            $builder->getAttribute('property')
        );
        $builder->addEventSubscriber($fileTypeSubscriber);

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
    }

    /**
     * @{inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entity'] = $form->getConfig()->getAttribute('entity');
        $view->vars['property'] = $form->getConfig()->getAttribute('property');

        $view->vars['show_current_file']= $form->getConfig()->getOption('show_current_file');

        $view->vars['multipart'] = true;
        $view->vars['type'] = 'file';

        $entity = $view->vars['entity'];
        $field  = $view->vars['property'];

        if($view->vars['value']) {

            $view->vars['file_url'] = $this->fileManager->getFileUrl($entity, $field, $view->vars['value']);

        } elseif (! is_null($form->getData()) && $form->getData() instanceof File) {

            $purgatoryFileManager = clone $this->fileManager;
            $purgatoryFileManager->setHttpRoot($purgatoryFileManager->getHttpRoot() . '/purgatory');

            $view->vars['file_url'] = $purgatoryFileManager->getFileUrl($entity, $field, $form->getData());

            $view->vars['purgatory_field_postfix'] = PurgatoryHelper::makePostFix($entity, $field);
            $view->vars['purgatory_file_filename'] = $form->getData()->getBaseName();
            $view->vars['purgatory_file_hash'] = PurgatoryHelper::makeHash($entity, $field, $view->vars['purgatory_file_filename']);
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


    /**
     * @{inheritDoc}
     */
    public function getParent()
    {
        return 'field';
    }
}