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

class FileType extends AbstractType
{
    const UPLOAD_FIELDNAME   = 'upload_file';
    const HASH_FIELDNAME     = 'hash';
    const FILENAME_FIELDNAME = 'filename';
    const REMOVE_FIELDNAME   = 'remove';

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
        $builder->add(self::HASH_FIELDNAME, 'hidden', array('mapped' => false, 'read_only' => true));
        $builder->add(self::FILENAME_FIELDNAME, 'hidden', array('mapped' => false, 'read_only' => true));

        //TODO: show yes/no when option is set / or not
        $builder->add(self::REMOVE_FIELDNAME, 'checkbox', array('mapped' => false, 'label' => 'You wanna remove?'));

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

        if($view->vars['value'] && is_array($view->vars['value'])  && array_key_exists(FileType::UPLOAD_FIELDNAME, $view->vars['value'])) {

            $view->vars['file_url'] = $this->fileManager->getFileUrl($entity, $field, $view->vars['value'][FileType::UPLOAD_FIELDNAME]);

        } else {
            $formData = $form->getData();

            //since the hash and filename aren't mapped, they are not in the form->getData
            //they can be accessed using $form->get('hash')->getData() or $form->get('filename')->getData()

            if (null !== $formData && $formData instanceof File) {
                $purgatoryFileManager = clone $this->fileManager;
                $purgatoryFileManager->setHttpRoot($purgatoryFileManager->getHttpRoot() . '/purgatory');

                $view->vars['file_url'] = $purgatoryFileManager->getFileUrl($entity, $field, $formData);
            }
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