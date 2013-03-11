<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Form;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\HttpFoundation\File\File;
use \Symfony\Component\OptionsResolver\OptionsResolverInterface;
use \Symfony\Component\Form\FormView;
use \Symfony\Component\Form\FormInterface;

use \Zicht\Bundle\FileManagerBundle\FileManager\FileManager;


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
                'compound' => false,
                'data_class' => 'Symfony\Component\HttpFoundation\File\File',
                'empty_data' => null,
                'entity' => null,
                'property' => null
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
        $view->vars['multipart'] = true;
        $view->vars['type'] = 'file';
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