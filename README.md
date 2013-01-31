# ZichtFileManagerBundle #

This bundle adds file handling to symfony forms.

# Doctrine #

In your entity, specify the @File annotation (Zicht\Bundle\FileManagerBundle\Doctrine\Annotation\File) for a field:

    // ...
    use Zicht\Bundle\FileManagerBundle\Doctrine\Annotation\File;
    // ...

    /**
     * @ORM\Column(type="string", nullable=true)
     * @File
     */
    protected $photo = null;


This will have the photo property be loaded as a File instance, and persisted as a basename of an uploaded file if an
UploadedFile is bound to the property.

# Sonata #

If you want to use this bundle in sonata, you should provide the form theme as such:

    class SceneryAdmin extends Admin
    {
        // ...
        public function getFormTheme()
        {
            return 'ZichtFileManagerBundle:Sonata:form_theme.html.twig';
        }
        // ...
    }

# Twig #

You can refer files with the file_url() function. This function ignores the base path of the file so you can easily add
an imagine_filter. For regular files, you should an asset() function:

    {{ asset(file_url(entity, field)) }}