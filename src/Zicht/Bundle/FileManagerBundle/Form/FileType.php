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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Yaml\Yaml;
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use Zicht\Bundle\FileManagerBundle\Helper\PurgatoryHelper;
use Symfony\Component\HttpKernel\Kernel;

class FileType extends AbstractType
{
    const UPLOAD_FIELDNAME   = 'upload_file';
    const HASH_FIELDNAME     = 'hash';
    const FILENAME_FIELDNAME = 'filename';
    const REMOVE_FIELDNAME   = 'remove';

    protected $mimeTypes;
    protected $parent;

    /**
     * Constructor.
     *
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
        $this->parent      = (Kernel::MINOR_VERSION <= 2) ? 'field' : 'form';
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $fm    = $this->fileManager;
        $label = isset($options['label']) ? $options['label'] : 'zicht_filemanager.upload_file';
        $this->updateOptions($options);

        $builder
            ->add(
                self::UPLOAD_FIELDNAME,
                'file',
                array(
                    'translation_domain' => $options['translation_domain'],
                    'label'              => $label,
                    'attr'               => array(
                        'accept' => implode(', ', $options['file_types'])
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
            )
        ;

//        if ($options['show_remove'] === true) { - TODO: this needs to be tested and needs some fixes at ZichtFileManagerBundle::form_theme.html.twig at line 25 ^^
            $builder->add(self::REMOVE_FIELDNAME, 'checkbox', array('mapped' => false, 'label' => 'zicht_filemanager.remove_file', 'translation_domain' => $options['translation_domain']));
//        }

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

        $builder
            ->get(self::UPLOAD_FIELDNAME)
            ->addEventListener(FormEvents::POST_BIND, function (FormEvent $event) use ($options) {
                    /** @var \Symfony\Component\HttpFoundation\File\File $data */
                    $data = $event->getData();
                    if (!empty($data) && $data instanceof \Symfony\Component\HttpFoundation\File\File) {
                        if (null !== $mime = $data->getMimeType()) {
                            if (!in_array($mime, $options['file_types'])) {
                                $event->getForm()->addError(
                                    new FormError(
                                        'zicht_filemanager.wrong_type',
                                        array(
                                            $data->getMimeType(),
                                            implode(', ', $options['file_types'])
                                        )
                                    )
                                );
                            }
                        }
                    }
                }
            );


        if (method_exists($builder, 'getParent')) {
            $entity = $builder->getParent()->getDataClass();
        } else {
            $entity = $builder->getDataClass();
        }

        $builder->setAttribute('entity', $entity);
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
        $view->vars['show_remove']= $form->getConfig()->getOption('show_remove');
        $view->vars['multipart'] = true;

        $entity = $view->vars['entity'];
        $field  = $view->vars['property'];

        if ($view->vars['value'] && is_array($view->vars['value'])  && array_key_exists(FileType::UPLOAD_FIELDNAME, $view->vars['value'])) {
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

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Update options field file_types
     * so if only extension is given it
     * will try to determine mime type
     *
     * @param   $options
     * @return  $this;
     */
    protected function updateOptions(&$options)
    {
        if(isset($options['file_types'])) {

            $types = &$options['file_types'];
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

        return $this;
    }

    /**
     * mime type converter for lazy loading :)
     *
     * @param  $extension
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
