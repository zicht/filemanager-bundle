<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Integrity;

use Zicht\Bundle\FileManagerBundle\Integrity\FilesystemChecker;

class FilesystemCheckerTest extends AbstractCheckerTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    function testSetLoggerCallbackWillFailIfNotCallable()
    {
        $checker = new FilesystemChecker($this->fm, $this->mf, $this->doctrine);
        $checker->setLoggingCallback('not callable');
    }


    function testChecker() {
        $checker = new FilesystemChecker($this->fm, $this->mf, $this->doctrine);
        $this->fm->expects($this->at(0))->method('getFilePath')->will($this->returnValue('/tmp/checker-test/foo'));
        $this->fm->expects($this->at(1))->method('getFilePath')->will($this->returnValue('/tmp/checker-test/bar'));
        $logResult = '';
        $checker->setLoggingCallback(function($d) use(&$logResult) {
            $logResult .= $d . "\n";
        });
        $checker->check('Foo');
        $this->assertRegExp('!File does not exist:[^\n]+foo!', $logResult);
        $this->assertRegExp('!File does not exist:[^\n]+bar!', $logResult);
    }

    function testChecker2() {
        $checker = new FilesystemChecker($this->fm, $this->mf, $this->doctrine);
        $this->fm->expects($this->at(0))->method('getFilePath')->will($this->returnValue('/tmp/checker-test/foo'));
        $this->fm->expects($this->at(1))->method('getFilePath')->will($this->returnValue('/tmp/checker-test/bar'));
        $logResult = '';
        $checker->setLoggingCallback(function($d) use(&$logResult) {
            $logResult .= $d . "\n";
        });

        touch('/tmp/checker-test/foo');
        touch('/tmp/checker-test/bar');
        clearstatcache();
        $checker->check('Foo');
        $this->assertRegExp('!File exists:[^\n]+foo!', $logResult);
        $this->assertRegExp('!File exists:[^\n]+bar!', $logResult);
    }


    function testPurge() {
        $checker = new FilesystemChecker($this->fm, $this->mf, $this->doctrine);
        $checker->setPurge(true);

        $manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->setMethods(array('persist', 'flush'))->disableOriginalConstructor()->getMock();
        $this->doctrine->expects($this->any())->method('getManager')->will($this->returnValue($manager));
        $manager->expects($this->atLeastOnce())->method('persist');
        $manager->expects($this->atLeastOnce())->method('flush');

        $this->fm->expects($this->at(0))->method('getFilePath')->will($this->returnValue('/tmp/checker-test/foo'));
        $this->fm->expects($this->at(1))->method('getFilePath')->will($this->returnValue('/tmp/checker-test/bar'));

        foreach ($this->records as $rec) {
            $rec->expects($this->any())->method('setFile')->with(null);
        }

        $checker->check('Foo');

    }
}