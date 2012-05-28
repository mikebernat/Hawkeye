<?php
/**
 * Hawkeye_HawkeyeDB_Test
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 * @filesource
 *
 */

/**
 * Hawkeye_HawkeyeDB_Test
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 *
 */
class Hawkeye_HawkeyeDB_Test
    extends PHPUnit_Framework_TestCase
{

    /**
     * Hawkeye
     *
     * @var Mock_HawkeyeDB
     */
    public $hawkeye;

    public function setUp()
    {
        parent::setUp();

        $this->hawkeye = $this->getMockBuilder('Mock_HawkeyeDB')
            ->setMethods(null)
            ->disableOriginalConstructor();
    }

    /**
     * test_getPluginDir
     */
    public function test_getPluginDir()
    {
        $hawkeye = $this->hawkeye->getMock();

        $result = $hawkeye->getPluginDir();

        $expected = array('Plugins/');

        $this->assertEquals($expected, $result);
    } // END function test_getPluginDir

    /**
     * test_setPluginDir
     */
    public function test_setPluginDir()
    {
        $hawkeye = $this->hawkeye->getMock();

        $expected = array('test/');

        $hawkeye->setPluginDir($expected);

        $result = $hawkeye->getPluginDir();


        $this->assertEquals($expected, $result);
    } // END function test_setPluginDir

    /**
     * test_addPluginDir
     */
    public function test_addPluginDir()
    {
        $hawkeye = $this->hawkeye->getMock();

        $hawkeye->setPluginDir(array('test/'));

        $hawkeye->addPluginDir('foobar/');

        $result = $hawkeye->getPluginDir();

        $expected = array('test/', 'foobar/');

        $this->assertEquals($expected, $result);
    } // END function test_setPluginDir

    /**
     * test_setOptions
     */
    public function test_setOptions()
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('_setTables'))
            ->getMock();

        $hawkeye->expects($this->once())
            ->method('_setTables')
            ->will($this->returnValue(true));

        $expected = array('options');

        $hawkeye->setOptions($expected);

        $result = $hawkeye->getOptions();

        $this->assertEquals($expected, $result);
    } // END function test_setOptions

    /**
     * test__setTables
     *
     * @param unknown_type $tables
     * @param unknown_type $alreadySet
     * @param unknown_type $expected
     *
     * @dataProvider provide__setTables
     */
    public function test__setTables($tables, $alreadySet, $expected)
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('_getAllTables'))
            ->getMock();

        if (!empty($alreadySet)) {
            $hawkeye->_options = array('tables' => $alreadySet);
        } else {
            $hawkeye->expects($this->once())
                ->method('_getAllTables')
                ->will($this->returnValue($tables));
        }

        $hawkeye->_setTables();

        $result = $hawkeye->getOptions();

        $this->assertEquals($expected, $result['tables']);
    } // END function test__setTables

    /**
     * provide__setTables
     */
    public function provide__setTables()
    {
        return array(
            'simple' => array(
                'tables'     => array('foo', 'bar'),
                'alreadySet' => false,
                'expected'   => array('foo', 'bar'),
            ),
            'existingTables' => array(
                'tables'     => array('foo', 'bar'),
                'alreadySet' => array('buzz'),
                'expected'   => array('buzz'),
            ),
        );
    } // END function provide__setTables


    /**
     * test_setPdo
     */
    public function test_setPdo()
    {
        $hawkeye = $this->hawkeye->getMock();

        $expected = new stdClass;

        $hawkeye->setPdo($expected);

        $result = $hawkeye->getPdo();

        $this->assertEquals($expected, $result);
    } // END function test_setPdo

    /**
     * test_construct
     */
    public function test_construct()
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('setPdo', 'setOptions', '_loadPlugins'))
            ->getMock();

        $pdo = new stdClass;
        $options = array('options');

        $hawkeye->expects($this->once())
            ->method('setPdo')
            ->with($pdo)
            ->will($this->returnValue(true));

        $hawkeye->expects($this->once())
            ->method('setOptions')
            ->with($options)
            ->will($this->returnValue(true));

        $hawkeye->expects($this->once())
            ->method('_loadPlugins')
            ->will($this->returnValue(true));

        $hawkeye->__construct($pdo, $options);
    } // END function test_construct

    /**
     * test__discoverPluginPaths
     *
     * @param unknown_type $paths
     * @param unknown_type $dir
     * @param unknown_type $expected
     *
     * @dataProvider provide__discoverPluginPaths
     */
    public function test__discoverPluginPaths($paths, $dir, $expected)
    {
        $this->_requireVFS();

        $vfsPaths = array();
        foreach ($dir as $path => $files) {
            vfsStream::setup($path, null, $files);
            $vfsPaths[] = array($path . '/', new DirectoryIterator(vfsStream::url($path)));
        }

        $hawkeye = $this->hawkeye
            ->setMethods(array('getPluginDir', '_getDirectoryIterator'))
            ->getMock();

        $hawkeye->expects($this->once())
            ->method('getPluginDir')
            ->will($this->returnValue($paths));

        $hawkeye->expects($this->any())
            ->method('_getDirectoryIterator')
            ->will($this->returnValueMap($vfsPaths));

        $result = $hawkeye->_discoverPluginPaths();

        $this->assertEquals($expected, $result);
    } // END function test__discoverPluginPaths

    /**
     * provide__discoverPluginPaths
     */
    public function provide__discoverPluginPaths()
    {
        return array(
            array(
                'paths' => array(
                    'plugins',
                ),
                'dir' => array(
                    'plugins' => array(
                        'Checksum.php' => '',
                        'Count.php' => '',
                        'Structure.php' => '',
                    ),
                ),
                'expected' => array(
                    'Checksum' => 'vfs://plugins/Checksum.php',
                    'Count' => 'vfs://plugins/Count.php',
                    'Structure' => 'vfs://plugins/Structure.php',
                ),
            ),
        );
    } // END function provide__discoverPluginPaths

    /**
     * test__instantiatePlugins
     *
     * @param unknown_type $paths
     * @param unknown_type $expected
     *
     * @dataProvider provide__instantiatePlugins
     */
    public function test__instantiatePlugins ($paths, $expected)
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('_includePlugin'))
            ->getMock();

        foreach ($paths as $class => $path) {
            $hawkeye->expects($this->once())
                ->method('_includePlugin')
                ->with($path)
                ->will($this->returnValue($class));
        }

        $results = $hawkeye->_instantiatePlugins($paths);

        $this->assertEquals($expected, $results);
    } // END function test__instantiatePlugins

    /**
     * provide__instantiatePlugins
     */
    public function provide__instantiatePlugins()
    {
        return array(
            array(
                'paths' => array(
                    'stdClass' => 'path/to/class.php',
                ),
                'expected' => array(
                    'stdClass' => new stdClass,
                ),
            ),
        );
    } // END function provide__instantiatePlugins

    /**
     * test__includePlugin
     */
    public function test__includePlugin()
    {
        $this->_requireVFS();

        $pluginMock = $this->_getPluginMock();
        $mockName = get_class($pluginMock);

        $testPlugin = <<<PHP
<?php
class Hawkeye_Db_Test_Test extends $mockName {}
PHP;

        $fileSystem = array(
            'testPlugin.php' => $testPlugin,
            'emptyPlugin.php' => '',
        );

        vfsStream::setup('root', null, $fileSystem);

        $hawkeye = $this->hawkeye->getMock();

        $result = $hawkeye->_includePlugin(vfsStream::url('testPlugin.php'));
        $this->assertEquals('Hawkeye_Db_Test_Test', $result);

        $result = $hawkeye->_includePlugin(vfsStream::url('emptyPlugin.php'));
        $this->assertEquals(false, $result);
    } // END function test__includePlugin

    /**
     * test__loadPlugins
     */
    public function test__loadPlugins()
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('_discoverPluginPaths', '_instantiatePlugins', 'addPlugin'))
            ->getMock();

        $plugin = $this->_getPluginMock();

        $plugins = array('name' => $plugin);

        $hawkeye->expects($this->once())
            ->method('_discoverPluginPaths')
            ->will($this->returnValue(true));

        $hawkeye->expects($this->once())
            ->method('_instantiatePlugins')
            ->will($this->returnValue($plugins));

        $hawkeye->expects($this->once())
            ->method('addPlugin')
            ->will($this->returnValue(true));

        $hawkeye->_loadPlugins();
    } // END function

    /**
     * test_addPlugin
     */
    public function test_addPlugin()
    {
        $plugin = $this->_getPluginMock();

        $plugin->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('testName'));

        $plugin->expects($this->once())
            ->method('init')
            ->will($this->returnValue(true));

        $hawkeye = $this->hawkeye
            ->setMethods(array('getOptions'))
            ->getMock();

        $hawkeye->addPlugin($plugin);
    } // END function test_addPlugin

    /**
     * test_removePlugin
     */
    public function test_removePlugin()
    {
        $plugin = $this->_getPluginMock();

        $plugin->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('testName'));

        $plugin->expects($this->once())
            ->method('init')
            ->will($this->returnValue(true));

        $hawkeye = $this->hawkeye
            ->setMethods(array('getOptions'))
            ->getMock();

        $hawkeye->addPlugin($plugin);
        $hawkeye->removePlugin('testName');
        $result = $hawkeye->_plugins;

        $this->assertEquals(array(), $result);
    } // END function test_removePlugin

    /**
     * test__getDefaultName
     */
    public function test__getDefaultName()
    {
        $hawkeye = $this->hawkeye->getMock();

        $result1 = $hawkeye->_getDefaultName();
        $this->assertTrue(is_string($result1));

        $result2 = $hawkeye->_getDefaultName();
        $this->assertNotEquals($result1, $result2);
    } // END function test__getDefaultName

    /**
     * test_snapshot
     */
    public function test_snapshot()
    {
        $hawkeye = $this->hawkeye
            ->setMethods(
                array(
                    '_getDefaultName', '_notify', 'getPdo', 'getOptions', '_saveSnapshot'
                )
            )
            ->getMock();

        $hawkeye->expects($this->once())
            ->method('_getDefaultName')
            ->will($this->returnValue('testName'));

        $hawkeye->expects($this->once())
            ->method('_notify')
            ->will($this->returnValue(true));

        $hawkeye->expects($this->once())
            ->method('getPdo')
            ->will($this->returnValue(true));

        $hawkeye->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue(array()));

        $hawkeye->expects($this->once())
            ->method('_saveSnapshot')
            ->will($this->returnValue(true));

        $hawkeye->snapshot();
    } // END function test_snapshot

    /**
     * test_getSnapshot
     */
    public function test_getSnapshot()
    {
        $hawkeye = $this->hawkeye->getMock();

        $expected =array('test' => 'foobar');

        $hawkeye->_snapshots = $expected;

        $result = $hawkeye->getSnapshot('test');

        $this->assertEquals($expected['test'], $result);

        $result = $hawkeye->getSnapshot('does-not-exist');

        $this->assertFalse($result);
    } // END function test_getSnapshot

    /**
     * test__saveSnapshot
     */
    public function test__saveSnapshot()
    {
        $hawkeye = $this->hawkeye->getMock();

        $expected = array('test' => 'foobar');

        $hawkeye->_snapshots = $expected;

        $hawkeye->_saveSnapshot('Snapshot', 'second');

        $expected['second'] = 'Snapshot';

        $result = $hawkeye->_snapshots;

        $this->assertEquals($expected, $result);

    } // END function test__saveSnapshot

    /**
     * test__notify
     */
    public function test__notify()
    {
        $hawkeye = $this->hawkeye->getMock();

        $args = array('option' => 'value');

        $plugin = $this->_getPluginMock(true, array('testNotifyMethod'));

        $plugin->expects($this->once())
            ->method('testNotifyMethod')
            ->with($args)
            ->will($this->returnValue('testValue'));

        $hawkeye->_plugins['testPlugin'] = $plugin;

        $result = $hawkeye->_notify('testNotifyMethod', $args);

        $expected = array('testPlugin' => 'testValue');

        $this->assertEquals($expected, $result);
    } // END function test__notify

    /**
     * test_diff
     */
    public function test_diff()
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('_getLastSnapshots', '_notify'))
            ->getMock();

        $hawkeye->expects($this->once())
            ->method('_getLastSnapshots')
            ->will($this->returnValue(array(true, true)));

        $hawkeye->expects($this->once())
            ->method('_notify')
            ->with('diff', array(true, true))
            ->will($this->returnValue('diffValue'));

        $result = $hawkeye->diff();

        $this->assertEquals('diffValue', $result);
    } // END function test_diff

    /**
     * test__getLastSnapshots
     *
     * @param unknown_type $n
     * @param unknown_type $snapshots
     * @param unknown_type $exception
     * @param unknown_type $expected
     *
     * @dataProvider provide__getLastSnapshots
     */
    public function test__getLastSnapshots($n, $snapshots, $exception, $expected)
    {
        if (!empty($exception)) {
            $this->setExpectedException($exception);
        }

        $hawkeye = $this->hawkeye->getMock();

        $hawkeye->_snapshots = $snapshots;

        $result = $hawkeye->_getLastSnapshots($n);

        $this->assertEquals($expected, $result);
    } // END function test__getLastSnapshots

    /**
     * provide__getLastSnapshots
     *
     * @return array
     */
    public function provide__getLastSnapshots()
    {
        return array(
           array(
               'n' => 2,
               'snapshots' => array('snapshot', 'snapshot'),
               'exception' => false,
               'expected' => array('snapshot', 'snapshot'),
           ),
           array(
               'n' => 2,
               'snapshots' => array('snapshot'),
               'exception' => 'Hawkeye_Exception',
               'expected' => false,
           ),
        );
    } // END function provide__getLastSnapshots

    /**
     * test_diffToString
     */
    public function test_diffToString()
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('_notify', 'diff'))
            ->getMock();

        $diff = array('option' => 'value');

        $hawkeye->expects($this->once())
            ->method('diff')
            ->will($this->returnValue($diff));

        $hawkeye->expects($this->once())
            ->method('_notify')
            ->with('diffToString', $diff)
            ->will($this->returnValue(array('testOutput')));

        $result = $hawkeye->diffToString();

        $this->assertTrue(is_string($result));
    } // END function test_diffToString

    /**
     * test___toString
     */
    public function test___toString()
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('diffToString'))
            ->getMock();

        $hawkeye->expects($this->once())
            ->method('diffToString')
            ->will($this->returnValue(true));

        $hawkeye->__toString();
    } // END function test___toString

    /**
     * test_log
     *
     * @param unknown_type $message
     * @param unknown_type $level
     * @param unknown_type $pluginName
     *
     * @dataProvider provide_log
     */
    public function test_log($message, $level, $pluginName, $logLevel)
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('_printLog'))
            ->getMock();

        if (!empty($logLevel)) {
            $hawkeye->logLevel = $logLevel;
        }

        if ($level >= $hawkeye->logLevel) {
            $hawkeye->expects($this->once())
                ->method('_printLog')
                ->will($this->returnValue(true));
        }

        $plugin = $this->_getPluginMock(true, array('getName'));

        $plugin->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($pluginName));

        $hawkeye->log($message, $level, $plugin);

        $this->assertTrue(count($hawkeye->_logs) == 1);
    } // END function test_log

    /**
     * test__printLog
     */
    public function test__printLog()
    {
        $hawkeye = $this->hawkeye->getMock();

        $log = array(
            'datetime' => 'testDate',
            'level' => 'testLevel',
            'plugin' => 'testPlugin',
            'message' => 'test message',
        );

        $expected = '[testDate][testLevel] testPlugin test message' . PHP_EOL;

        $this->expectOutputString($expected);
        $hawkeye->_printLog($log);
    } // END function test__printLog

    /**
     * provide_log
     *
     * @return array
     */
    public function provide_log()
    {
        return array(
            array(
                'messages' => 'test',
                'level' => null,
                'pluginName' => 'testPlugin',
                'logLevel' => null,
            ),
            array(
                'messages' => 'test',
                'level' => 2,
                'pluginName' => 'testPlugin',
                'logLevel' => 1,
            ),
        );
    } // END function provide_log

    public function test_clear()
    {
        $hawkeye = $this->hawkeye->getMock();

        $hawkeye->_snapshots = array('not-clear');

        $hawkeye->clear();

        $this->assertEmpty($hawkeye->_snapshots);
    } // END function test_clear

    /**
     * _requireVFS
     *
     * Require vfsStream
     */
    protected function _requireVFS()
    {
        @include_once 'vfsStream.php';

        if (!@class_exists('vfsStream', true)) {
            $this->markTestSkipped('vfsStream not installed');
        }
    } // END function _requireVFS

    /**
     * test__getAllTables
     */
    public function test__getAllTables()
    {
        $hawkeye = $this->hawkeye
            ->setMethods(array('getPdo'))
            ->getMock();

        $pdo = $this->getMockBuilder('stdClass')
            ->setMethods(array('query'))
            ->getMock();

        $statement = $this->getMockBuilder('stdClass')
            ->setMethods(array('fetchAll'))
            ->getMock();

        $pdo->expects($this->once())
            ->method('query')
            ->will($this->returnValue($statement));

        $statement->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue(true));

        $hawkeye->expects($this->once())
            ->method('getPdo')
            ->will($this->returnValue($pdo));

        $hawkeye->_getAllTables();

    } // END function test__getAllTables


    /**
     * test__getDirectorityIterator
     */
    public function test__getDirectorityIterator()
    {
        $this->_requireVFS();

        $structure = array(
                'file.php' => '',
        );

        vfsStream::setup('root', null, $structure);

        $hawkeye = $this->hawkeye->getMock();

        $result = $hawkeye->_getDirectoryIterator(vfsStream::url('root'));

        $this->assertEquals('file.php', $result->getFilename());
    } // END function test__getDirectorityIterator

    /**
     * _getPluginMock
     *
     * Test helper to quickly get a mock plugin
     *
     * @param bool $getMock
     *
     * @return Hawkeye_PluginAbstract|PHPUnit_Framework_MockObject_MockBuilder
     */
    protected function _getPluginMock($getMock = true, $methods = array())
    {
        $defaultMethods = array('getName', 'init', 'snapshot', 'diff', 'diffToString');
        $methods = array_unique(array_merge($defaultMethods, $methods));

        $mock = $this->getMockBuilder('Hawkeye_PluginAbstract')
            ->disableOriginalConstructor()
            ->setMethods($methods);

        if ($getMock) {
            return $mock->getMock();
        }

        return $mock;
    } // END function _getPluginMock

} // END class Hawkeye_Exception_Test

/**
 * Mock_HawkeyeDB
 *
 * Utility class
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 *
 */
class Mock_HawkeyeDB
    extends HawkeyeDB
{

    /**
     * Options
     *
     * @var array
     */
    public $_options;

    /**
     * Plugins
     *
     * @var array
     */
    public $_plugins;

    /**
     * Snapshots
     *
     * @var array
     */
    public $_snapshots;

    /**
     * Logs
     *
     * @var array
     */
    public $_logs;

    /**
     * Call
     *
     * Utility class to call protected methods publically
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    public function __call($method, $args = array())
    {
        return call_user_func_array(array($this, $method), $args);
    } // END function __call

} // END class Mock_HawkeyeDB