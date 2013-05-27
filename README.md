# Zicht filemanagerbundle #

This bundle provides a quick and easy link between doctrine model properties and files. The bundle provides an
@File annotation, with which the filemanager is notified about the fact that the property contains a file name.
The files are stored in a common root (e.g. web/media) and are partitioned in {entity}/{field}. For example, a User
entity's 'avatar' field would become web/media/user/avatar/thefile.jpg.

## Installing ##

* Include zicht/filemanager-bundle in your composer.json configuration
* Add the bundle to your AppKernel
* Use the annotation in your model and annotate your property with it:

      use \Zicht\Bundle\FileManagerBundle\Annotation\File;

      // ....
      /**
       * @File
       * @ORM\Column(type="string", nullable=true)
       */
      protected $file;

## Doctrine ##

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

# Twig #

You can refer files with the file_url() function. This function ignores the base path of the file so you can easily add
an imagine_filter. For regular files, you should an asset() function:

    {{ asset(file_url(entity, field)) }}


## Using files as fixtures ##

If you're using datafixtures, you can use files as fixtures by wrapping them in the FixtureFile object. This object
doesn't move the original file to the target, but uses a 'copy' in stead. This way, the original fixtures remain in
tact:

    $file = new FixtureFile(__DIR__ . '/../../Resources/fixtures/chuck-norris.jpg');
    $user = new User();
    $user->setAvatar($file);
    // ...
    $manager->persist($user);
    $manager->flush();

## Using forms ##

You should use the 'zicht_file' type to handle uploads in forms:

    $user = new User();
    $myForm = $this->createForm($user)->add('avatar', 'zicht_file');

## The `zicht:filemanager:check` command ##
The check command compares the values in the database to the files present on disk, or vice versa.
To check if there are values in the database that aren't present on disk, you run the zicht:filemanager:check command:

    php app/console zicht:filemanager:check

To clear the values that aren't present on disk, pass the --purge flag:

    php app/console zicht:filemanager:check --purge

To check if there are files on disk that are not present in the database you pass the --inverse flag:

    php app/console zicht:filemanager:check --inverse

To delete all files that are managed by the filemanager but are not present in the database, pass the --purge flag:

    php app/console zicht:filemanager:check --inverse

You can pass an optional entity alias to check only one specific repository. Otherwise, all entities with file
annotations are checked.

    php app/console zicht:filemanager:check AcmeDemoBundle:SomeEntity

## How does it work, exactly? ##

The filemanager registers an event subscriber in Doctrine, which listens to persisting entities that contain @File
annotations. Whenever the entity's managed properties are changed, a stub is created in place where the file should
come. The filemanager handles existing filenames by adding -1, -2, etc to the filename.

As soon as the manager gets flushed, the file is moved from it's original location into the location of the stub.
If the flush isn't called, the stub is removed on destruction of the filemanager, i.e. at the end of the request.

