<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Form;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\CallbackTransformer;
use \Symfony\Component\Form\DataTransformerInterface;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Form\FormError;
use \Symfony\Component\Form\FormEvent;
use \Symfony\Component\Form\FormEvents;
use \Symfony\Component\Form\FormFactoryInterface;
use \Symfony\Component\Form\FormInterface;
use \Symfony\Component\Form\FormView;
use \Symfony\Component\HttpFoundation\File\File;
use \Symfony\Component\OptionsResolver\OptionsResolverInterface;
use \Symfony\Component\Yaml\Yaml;
use \Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use \Zicht\Bundle\FileManagerBundle\Helper\PurgatoryHelper;
use \Symfony\Component\HttpKernel\Kernel;
use \Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * Class FileType
 *
 * @package Zicht\Bundle\FileManagerBundle\Form
 */
class FileType extends AbstractType
{
    /** @const the HTML-fieldname for the upload field */
    const UPLOAD_FIELDNAME   = 'upload_file';

    /** @const the HTML-fieldname for the (hidden) hash field */
    const HASH_FIELDNAME     = 'hash';

    /** @const the HTML-fieldname for the (hidden) filename field */
    const FILENAME_FIELDNAME = 'filename';

    /** @const the HTML-fieldname for the remove checkbox field */
    const REMOVE_FIELDNAME   = 'remove';

    protected $mimeTypes;
    /** @var array|string[]  */
    protected $entities;

    /**
     * Constructor.
     *
     * @param FileManager $fileManager
     * @param \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator
     */
    public function __construct(FileManager $fileManager, Translator $translator)
    {
        $this->fileManager = $fileManager;
        $this->translator  = $translator;
    }

    /**
     * @{inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $resolver->setDefaults(
            array(
                'entity'             => null,
                'property'           => null,
                'show_current_file'  => true,
                'show_remove'        => true,
                'translation_domain' => 'admin',
                'file_types'         => array(),
            )
        );
    }

    /**
     * @{inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $allowedTypes = $this->getAllowedTypes($options);

        $fm    = $this->fileManager;
        $label = isset($options['label']) ? $options['label'] : 'zicht_filemanager.upload_file';

        $builder
            ->add(
                self::UPLOAD_FIELDNAME,
                'file',
                array(
                    'translation_domain' => $options['translation_domain'],
                    'label'              => $label,
                    'attr'               => array(
                        'accept' => implode(', ', $allowedTypes)
                    )
                )
            )
            ->add(
                self::HASH_FIELDNAME,
                'hidden',
                array(
                    'mapped' => false,
                    'read_only' => true,
                    'translation_domain' => $options['translation_domain']
                )
            )
            ->add(
                self::FILENAME_FIELDNAME,
                'hidden',
                array(
                    'mapped' => false,
                    'read_only' => true,
                    'translation_domain' => $options['translation_domain'])
            );

        if ($options['show_remove']) {
            $builder->add(
                self::REMOVE_FIELDNAME,
                'checkbox',
                array(
                    'mapped' => false,
                    'label' => 'zicht_filemanager.remove_file',
                    'translation_domain' => $options['translation_domain']
                )
            );
        }

        $builder->setAttribute('property', $builder->getName());
        $builder->addViewTransformer(new Transformer\FileTransformer(array($this, 'transformCallback'), array($builder->getAttribute('property'), $this->getId($builder->getFormConfig()))));

        /**
         * In Symfony >= 2.3 there is no FormBuilder::getParent() anymore. And because in the buildForm Symfony just builds a
         * generic form (in a vacuum), not knowing the context, there is no method to know what the parent form is - as far as
         * Symfony and/or buildForm() know, there is no parent. So to determine what entity we are creating this file for,
         * we need to listen to the first event fired, the PRE_SET_DATA (the event just before the initial data is set in the form).
         * In this event we have the form-instance, so we can access the parent and extract the dataClass.
         */
        $builder->addEventListener(FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $this->entities[$this->getId($event->getForm()->getConfig())] = $event->getForm()->getParent()->getConfig()->getDataClass();
            }
        );

        /**
         * This FileTypeSubscriber is needed to preserve the old values, if there is no new file uploaded. Otherwise a blank string would be stored.
         */
        $fileTypeSubscriber = new FileTypeSubscriber(
            $fm,
            $builder->getAttribute('property'),
            $this->translator,
            $allowedTypes
        );
        $builder->addEventSubscriber($fileTypeSubscriber);
    }

    protected function getId(FormConfigInterface $formConfig)
    {
        /** @var \Sonata\DoctrineORMAdminBundle\Admin\FieldDescription $fieldDescription */
        $fieldDescription = $formConfig->getAttribute('sonata_admin')['field_description'];
        return sha1(
            $fieldDescription->getType().
            $fieldDescription->getName().
            $fieldDescription->getAdmin()->getUniqid()
        );
    }

    /**
     * Callback for the FileTransformer - since we can't pass the entity in buildForm, we needed a seperate handler, so the entity is defined :(
     *
     * @param string $value
     * @param string $property
     * @return null|string
     */
    public function transformCallback($value, $property)
    {
        list($property, $id) = $property;
        return $this->fileManager->getFilePath($this->entities[$id], $property, $value);
    }

    /**
     * @{inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {

        $entity = $this->entities[$this->getId($form->getConfig())];
        //First we set some vars, like the entity, the property and if the current file should be shown
        $view->vars['entity'] = $entity;
        $view->vars['property'] = $form->getConfig()->getAttribute('property');
        $view->vars['show_current_file']= $form->getConfig()->getOption('show_current_file');
        $view->vars['multipart'] = true;

        //We check if there is a value. If there is a file uploaded, the $view->vars['value'] = null, so this is only valid when the value comes from the database.
        if ($view->vars['value'] && is_array($view->vars['value'])  && array_key_exists(FileType::UPLOAD_FIELDNAME, $view->vars['value'])) {


            foreach ($view->vars['value'] as $name => $value) {
                $view->children[$name]->vars['value'] = $view->vars['value'][$name];
            }

            $view->vars['file_url'] = $this->fileManager->getFileUrl($entity, $view->vars['property'], $view->vars['value'][FileType::UPLOAD_FIELDNAME]);
        } else {
            //We don't have previously stored data, we can check if we have a file uploaded. If so, we can show that.
            $formData = $form->getData();

            if (null !== $formData && $formData instanceof File) {
                $purgatoryFileManager = clone $this->fileManager;
                $purgatoryFileManager->setHttpRoot($purgatoryFileManager->getHttpRoot() . '/purgatory');

                $view->vars['file_url'] = $purgatoryFileManager->getFileUrl($entity, $view->vars['property'], $formData);
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

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return 'form';
    }

    /**
     * Update options field file_types
     * so if only extension is given it
     * will try to determine mime type
     *
     * @param array $options
     * @return null | mixed;
     */
    protected function getAllowedTypes(array $options)
    {
        $types = null;

        if (isset($options['file_types'])) {

            $types = $options['file_types'];
            $self  = $this;

            if (!is_array($types)) {
                $types = explode(',', $types);
                $types = array_map('trim', $types);
            }

            array_walk($types, function(&$val) use ($self) {
                if (false == preg_match('#^([^/]+)/([\w|\.|\-]+)#', $val)) {
                    $val = $self->getMimeType($val);
                }
            });
        }

        return $types;
    }

    /**
     * mime type converter for lazy loading :)
     *
     * @param string $extension
     * @return string
     * @throws \InvalidArgumentException
     */
    function getMimeType($extension)
    {
        /**
         * check and remove (*.)ext so we only got the extension
         * when wrong define for example *.jpg becomes jpg
         */
        if (false != preg_match("#^.*\.(?P<EXTENSION>[a-z0-9]{2,4})$#i", $extension, $match)) {
            $extension = $match['EXTENSION'];
        }

        $extension  = strtolower($extension);

        if (is_null($this->mimeTypes)) {
            $file = __DIR__.'/../Resources/config/mime.yml';
            if (is_file($file)) {
                $this->mimeTypes = Yaml::parse(file_get_contents($file));
            } else {
                throw new \InvalidArgumentException('Mime file not found, perhaps you need to create it first? (zicht:filemanager:create:mime)');
            }
        }

        if (array_key_exists($extension, $this->mimeTypes)) {
            return $this->mimeTypes[$extension];
        } else {
            throw new \InvalidArgumentException(sprintf('Could not determine mime type on: %s', $extension));
        }
    }
}
