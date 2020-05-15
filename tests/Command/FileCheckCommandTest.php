<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Annotation;


use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\CommandTester;

class FileCheckCommandTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->cmd = new \Zicht\Bundle\FileManagerBundle\Command\FileCheckCommand();

        $container = new \Symfony\Component\DependencyInjection\Container(null);
        $this->cmd->setContainer($container);
        $container->set(
            'zicht_filemanager.integrity_checker.filesystem',
            $this->fschecker = $this->getMock('Zicht\Bundle\FileManagerBundle\Integrity\CheckerInterface')
        );
        $container->set(
            'zicht_filemanager.integrity_checker.database',
            $this->dbchecker = $this->getMock('Zicht\Bundle\FileManagerBundle\Integrity\CheckerInterface')
        );
        $container->set(
            'zicht_filemanager.entity_helper',
            $helper = $this->getMock('EntityHelper', array('getManagedEntities'))
        );
        $helper->expects($this->any())->method('getManagedEntities')->will($this->returnValue(array('foo')));
    }

    function trueAndFalse()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * @dataProvider trueAndFalse
     */
    function testInverseOption($provided)
    {
        if ($provided) {
            $this->dbchecker->expects($this->once())->method('setLoggingCallback');
            $this->fschecker->expects($this->never())->method('setLoggingCallback');
        } else {
            $this->dbchecker->expects($this->never())->method('setLoggingCallback');
            $this->fschecker->expects($this->once())->method('setLoggingCallback');
        }

        $input = new ArrayInput(
            array(
                '--inverse' => $provided,
            ),
            $this->cmd->getDefinition()
        );
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $this->cmd->run($input, $output);
    }

    /**
     * @dataProvider trueAndFalse
     */
    function testPurgeOption($provided)
    {
        if ($provided) {
            $this->fschecker->expects($this->once())->method('setPurge')->with(true);
        } else {
            $this->fschecker->expects($this->never())->method('setPurge');
        }

        $input = new ArrayInput(
            array(
                '--purge' => $provided,
            ),
            $this->cmd->getDefinition()
        );
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $this->cmd->run($input, $output);
    }


    function testLoggerCallback()
    {
        $callback = function() {};
        $result = '';

        $this->fschecker->expects($this->once())->method('setLoggingCallback')->will($this->returnCallback(function($fn) use(&$callback) {
            $callback = $fn;
            $fn('[test]', 0);
        }));

        $input = new ArrayInput(
            array(
            ),
            $this->cmd->getDefinition()
        );
        $output = $this->getMock('Symfony\Component\Console\Output\ConsoleOutput', array('writeln'));
        $output->expects($this->any())->method('writeln')->will($this->returnCallback(function($data) use(&$result) {
            $result .= $data;
        }));
        $this->cmd->run($input, $output);
        $this->assertRegExp('/\[test\]/', $result);
    }


    function testWithoutEntityArguments()
    {
        $input = new ArrayInput(
            array(
            ),
            $this->cmd->getDefinition()
        );
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $this->fschecker->expects($this->once())->method('check')->with('foo');
        $this->cmd->run($input, $output);
    }

    function testEntityArgument()
    {
        $input = new ArrayInput(
            array(
                'entity' => 'bar'
            ),
            $this->cmd->getDefinition()
        );
        $output = $this->getMock('Symfony\Component\Console\Output\ConsoleOutput', array('writeln'));
        $this->fschecker->expects($this->once())->method('check')->with('bar');
        $this->cmd->run($input, $output);
    }
}