<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Integrity;

use Zicht\Bundle\FileManagerBundle\Integrity\DatabaseChecker;

class DatabaseCheckerTest extends AbstractCheckerTestCase
{
    function testSetLoggerCallbackWillFailIfNotCallable()
    {
        $this->expectException('\InvalidArgumentException');
        $checker = new DatabaseChecker($this->fm, $this->mf, $this->doctrine);
        $checker->setLoggingCallback('not callable');
    }


    function testCheckerWhenDirDoesNotExist()
    {
        $this->tearDown();
        $checker = new DatabaseChecker($this->fm, $this->mf, $this->doctrine);
        $logResult = '';
        $checker->setLoggingCallback(function ($d) use (&$logResult) {
            $logResult .= $d . "\n";
        });
        $checker->check('Foo');
        $this->assertMatchesRegularExpression('!Dir does not exist:[^\n]+/tmp/checker-test!', $logResult);
    }

    function testChecker()
    {
        $checker = new DatabaseChecker($this->fm, $this->mf, $this->doctrine);
        $logResult = '';
        $checker->setLoggingCallback(function ($d) use (&$logResult) {
            $logResult .= $d . "\n";
        });

        touch('/tmp/checker-test/foo');
        touch('/tmp/checker-test/bar');
        $checker->check('Foo');

        $this->assertMatchesRegularExpression('/Exists:[^\n]+foo/', $logResult);
        $this->assertMatchesRegularExpression('/Exists:[^\n]+bar/', $logResult);
    }

    function testChecker2()
    {
        $checker = new DatabaseChecker($this->fm, $this->mf, $this->doctrine);
        $logResult = '';
        $checker->setLoggingCallback(function ($d) use (&$logResult) {
            $logResult .= $d . "\n";
        });

        touch('/tmp/checker-test/foo');
        $checker->check('Foo');

        $this->assertMatchesRegularExpression('/Exists:[^\n]+foo/', $logResult);
        $this->assertDoesNotMatchRegularExpression('/Exists:[^\n]+bar/', $logResult);
    }

    function testNotUsed()
    {
        $checker = new DatabaseChecker($this->fm, $this->mf, $this->doctrine);
        $logResult = '';
        $checker->setPurge(false);
        $checker->setLoggingCallback(function ($d) use (&$logResult) {
            $logResult .= $d . "\n";
        });

        touch('/tmp/checker-test/baz');
        $checker->check('Foo');
        $this->assertMatchesRegularExpression('/Not used:[^\n]+baz/', $logResult);
    }
    function testCheckerPurge()
    {
        $checker = new DatabaseChecker($this->fm, $this->mf, $this->doctrine);
        $logResult = '';
        $checker->setPurge(true);
        $checker->setLoggingCallback(function ($d) use (&$logResult) {
            $logResult .= $d . "\n";
        });

        touch('/tmp/checker-test/foo');
        touch('/tmp/checker-test/baz');
        $checker->check('Foo');
        $this->assertFalse(file_exists('/tmp/chekcer-test/baz'));
        $this->assertMatchesRegularExpression('/Deleted:[^\n]+baz/', $logResult);
    }
}
