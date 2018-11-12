<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Yaml\Yaml;
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * Class FileType
 *
 * @package Zicht\Bundle\FileManagerBundle\Form
 */
class FileType extends AbstractType
{
    /**
     * The HTML-fieldname for the upload field
     */
    const UPLOAD_FIELDNAME   = 'upload_file';

    /**
     * The HTML-fieldname for the (hidden) hash field
     */
    const HASH_FIELDNAME     = 'hash';

    /**
     * The HTML-fieldname for the (hidden) filename field
     */
    const FILENAME_FIELDNAME = 'filename';

    /**
     * The HTML-fieldname for the remove checkbox field
     */
    const REMOVE_FIELDNAME   = 'remove';

    /**
     * Name of the 'select' part of the field
     */
    const RADIO_FIELDNAME    = 'select';

    /**
     * Name of the 'url' part of the field
     */
    const URL_FIELDNAME      = 'url';

    /**
     * Name of the 'url' value part of the field
     */
    const FILE_URL           = 'url';

    /**
     * Name of the 'upload' value part of the field
     */
    const FILE_UPLOAD        = 'upload';

    /**
     * Will optionally save the new file using the name of the previous file
     */
    const KEEP_PREVIOUS_FILENAME = 'keep_previous_filename';

    protected $mimeTypes;

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
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entity'             => null,
                'property'           => null,
                'show_current_file'  => true,
                'show_remove'        => true,
                'show_keep_previous_filename' => true,
                'translation_domain' => 'admin',
                'file_types'         => [],
                'allow_url'          => false,
            ]
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

        $builder
            ->add(
                self::UPLOAD_FIELDNAME,
                'Symfony\Component\Form\Extension\Core\Type\FileType',
                array(
                    'translation_domain' => $options['translation_domain'],
                    'label'              => 'zicht_filemanager.upload_file',
                    'attr'               => array(
                        'accept' => implode(', ', $allowedTypes)
                    ),
                    'required' => false
                )
            )
            ->add(
                self::HASH_FIELDNAME,
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' =>['read_only' => true],
                    'translation_domain' => $options['translation_domain']
                )
            )
            ->add(
                self::FILENAME_FIELDNAME,
                HiddenType::class,
                array(
                    'mapped' => false,
                    'attr' => ['read_only' => true],
                    'translation_domain' => $options['translation_domain'])
            )
            ->add(
                self::RADIO_FIELDNAME,
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        self::FILE_UPLOAD => self::FILE_UPLOAD,
                        self::FILE_URL => self::FILE_URL
                    ),
                    'data' => 'upload',
                )
            )
            ->add(
                self::URL_FIELDNAME,
                TextType::class,
                array(
                    'mapped' => false,
                    'label' => 'zicht_filemanager.url_label',
                    'translation_domain' => $options['translation_domain'],
                    'required' => false,
                )
            );

        if ($options['show_remove']) {
            $builder->add(
                self::REMOVE_FIELDNAME,
                CheckboxType::class,
                array(
                    'mapped' => false,
                    'label' => 'zicht_filemanager.remove_file',
                    'translation_domain' => $options['translation_domain'],
                    'required' => false
                )
            );
        }

        if ($options['show_keep_previous_filename']) {
            $builder->add(
                self::KEEP_PREVIOUS_FILENAME,
                CheckboxType::class,
                [
                    'mapped' => false,
                    'label' => 'zicht_filemanager.keep_previous_filename',
                    'translation_domain' => $options['translation_domain'],
                    'required' => false,
                ]
            );
        }

        $builder->setAttribute('property', $builder->getName());

        /**
         * In Symfony >= 2.3 there is no FormBuilder::getParent() anymore. And because in the buildForm Symfony just builds a
         * generic form (in a vacuum), not knowing the context, there is no method to know what the parent form is - as far as
         * Symfony and/or buildForm() know, there is no parent. So to determine what entity we are creating this file for,
         * we need to listen to the first event fired, the PRE_SET_DATA (the event just before the initial data is set in the form).
         * In this event we have the form-instance, so we can access the parent and extract the dataClass.
         */
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $id = $this->getId($event->getForm()->getConfig());
                $this->entities[$id] = $event->getForm()->getParent()->getConfig()->getDataClass();
            }
        );

        $builder->addViewTransformer(
            new Transformer\FileTransformer(
                array($this, 'transformCallback'),
                array($builder->getAttribute('property'), $this->getId($builder->getFormConfig()))
            )
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

    /**
     * @param FormConfigInterface $formConfig
     * @return string
     */
    protected function getId(FormConfigInterface $formConfig)
    {
        /** @var \Sonata\DoctrineORMAdminBundle\Admin\FieldDescription $fieldDescription */
        if ($fieldDescription = $formConfig->getAttribute('sonata_admin')['field_description']) {
            return sha1(
                $fieldDescription->getType().
                $fieldDescription->getName().
                $fieldDescription->getAdmin()->getUniqid()
            );
        } else {
            return $formConfig->getName();
        }
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
        $view->vars['show_remove']= $form->getConfig()->getOption('show_remove');
        $view->vars['allow_url']= $form->getConfig()->getOption('allow_url');
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
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'zicht_file';
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

            array_walk(
                $types,
                function (&$val) use ($self) {
                    if (false == preg_match('#^([^/]+)/([\w|\.|\-]+)#', $val)) {
                        $val = $self->getMimeType($val);
                    }
                }
            );
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
    public function getMimeType($extension)
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
