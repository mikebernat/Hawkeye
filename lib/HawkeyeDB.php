<?php
/**
 * HawkeyeDb
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 * @filesource
 *
 */

/**
 * HawkeyeDb
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 *
 */
class HawkeyeDB
{

    const ERROR = 5;

    const WARNING = 4;

    const NOTICE = 3;

    const INFO = 2;

    const DEBUG = 1;

    const ERROR_PLUGIN_LOAD_FAIL = 'Could not load plugin %s';

    const ERROR_NO_VALID_SNAPSHOTS = 'Could not find snapshots to diff';

    /**
     * Log Level
     *
     * Level (and higher) to print logs
     *
     * @var int
     */
    public $logLevel = 3;

    /**
     * Plugin Directories
     *
     * @var array
     */
    protected $_pluginDir = array('Plugins/');

    /**
     * Plugins
     *
     * @var array
     */
    protected $_plugins = array();

    /**
     * Snapshots
     *
     * @var array
     */
    protected $_snapshots = array();

    /**
     * Options
     *
     * @var array
     */
    protected $_options = array();

    /**
     * PDO
     *
     * Instance of PDO
     *
     * @var PDO
     */
    protected $_pdo;

    /**
     * Get Plugin Dir
     *
     * Get the plugin dir
     *
     * @return array
     */
    public function getPluginDir()
    {
        return $this->_pluginDir;
    } // END function getPluginDir

    /**
     * Add Plugin Dir
     *
     * Add path to the plugin dir stack
     *
     * @param string $path
     *
     * @return Hawkeye
     */
    public function addPluginDir($path)
    {
        $this->_pluginDir[] = $path;

        return $this;
    } // END function addPluginDir

    /**
     * Set Plugin Directory
     *
     * @param array $dir
     *
     * @return Hawkeye
     */
    public function setPluginDir($dir)
    {
        $this->_pluginDir = $dir;

        return $this;
    } // END function setPluginDir


    /**
     * Set (Replace) Options
     *
     * Set options array
     *
     * @param array $options
     *
     * @return Hawkeye
     */
    public function setOptions($options)
    {
        $this->_options = $options;

        $this->_setTables();

        return $this;
    } // END function setOptions

    /**
     * Set Tables
     *
     * Checks the options array for a whitelist of tables.
     * If none is found one is generated to include all tables
     */
    protected function _setTables()
    {
        if (!empty($this->_options['tables'])) {
            return;
        }

        $this->_options['tables'] = $this->_getAllTables();
    } // END function _setTables

    /**
     * Get All Tables
     *
     * Get All Tables
     *
     * @return array
     */
    protected function _getAllTables()
    {
        return $this->getPdo()
            ->query('SHOW TABLES')
            ->fetchAll(PDO::FETCH_COLUMN);
    } // END function _getAllTables

    /**
     * Get Options
     *
     * Return the options array
     *
     * @return array $options
     */
    public function getOptions()
    {
        return $this->_options;
    } // END function getOptions

    /**
     * Set Pdo
     *
     * Set the PDO instance
     *
     * @param PDO $pdo
     *
     * @return Hawkeye
     */
    public function setPdo($pdo)
    {
        $this->_pdo = $pdo;

        return $this;
    } // END function setPdo

    /**
     * Get Pdo
     *
     * Return instance of PDO
     *
     * @return PDO
     */
    public function getPdo()
    {
        return $this->_pdo;
    } // END function getPdo

    /**
     * Construct
     *
     * @param PDO $pdo
     * @param array $options
     */
    public function __construct($pdo, $options = array())
    {
        $this->setPdo($pdo);
        $this->setOptions($options);

        $this->_loadPlugins();
    }

    /**
     * Get Directory Iterator
     *
     * @param string $path
     */
    protected function _getDirectoryIterator($path)
    {
        return new DirectoryIterator($path);
    } // END function _getDirectoryIterator

    /**
     * Discover Plugin Paths
     *
     * Traverse the plugin directory and return an array of plugins
     * with absolute paths
     *
     * @return array
     */
    protected function _discoverPluginPaths()
    {
        $pluginPaths = array();

        foreach ($this->getPluginDir() as $path) {
            $path = rtrim(
                $path,
                DIRECTORY_SEPARATOR
            ) .
            DIRECTORY_SEPARATOR;

            $dir = $this->_getDirectoryIterator($path);

            foreach ($dir as $fileInfo) {
                if ('php' === $fileInfo->getExtension()) {
                    $fileName = str_replace(
                        '.' . $fileInfo->getExtension(),
                        '',
                        $fileInfo->getFilename()
                    );

                    $pluginPaths[$fileName] = $fileInfo->getPathname();
                }
            }
        }

        return $pluginPaths;
    } // END function _discoverPluginPaths

    /**
     * Instantiate Plugins
     *
     * Instantiate plugins given their absolute paths
     *
     * @param array $pluginPaths
     *
     * @return array Plugins
     */
    protected function _instantiatePlugins($pluginPaths)
    {
        $plugins = array();

        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PluginAbstract.php';

        foreach ($pluginPaths as $pluginName => $pluginPath) {
            $pluginClassName = $this->_includePlugin($pluginPath);

            $plugin = new $pluginClassName($this);

            $plugins[$pluginName] = $plugin;
        }

        return $plugins;
    } // END function _instantiatePlugins

    /**
     * Include Plugin
     *
     * @link include a plugin and return its class name
     *
     * @param string $path
     *
     * @return string $className
     */
    protected function _includePlugin($path)
    {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Exception.php';

        Hawkeye_Exception::assert(
            is_readable($path),
            sprintf(
                self::ERROR_PLUGIN_LOAD_FAIL,
                $path
            )
        );

        $oldClasses = get_declared_classes();

        include $path;

        $newClasses = array_diff(get_declared_classes(), $oldClasses);

        foreach ($newClasses as $class) {
            if (in_array('Hawkeye_PluginAbstract', class_parents($class)) !== false) {
                return $class;
            }
        }

        return false;
    } // END function _includePlugin

    /**
     * Load Plugins
     *
     * Load the plugins and add them to the stack
     */
    protected function _loadPlugins()
    {
        $pluginPaths = $this->_discoverPluginPaths();
        $plugins = $this->_instantiatePlugins($pluginPaths);

        foreach ($plugins as $name => $plugin) {
            $this->addPlugin($plugin, $name);
        }
    } // END function _loadPlugins

    /**
     * Add Plugin
     *
     * Add a plugin to the stack
     *
     * @param Hawkeye_PluginAbstract $plugin
     * @param string $name
     *
     * @return Hawkeye
     */
    public function addPlugin(Hawkeye_PluginAbstract $plugin, $name = '')
    {
        if (empty($name)) {
            $name = $plugin->getName();
        }

        $this->_plugins[$name] = $plugin;

        $plugin->init($this->getOptions());

        return $this;
    } // END function addPlugin

    /**
     * Remove Plugin
     *
     * Remove plugin from the stack by name
     *
     * @param string $name
     *
     * @return Hawkeye
     */
    public function removePlugin($name)
    {
        unset($this->_plugins[$name]);

        return $this;
    } // END function removePlugin

    /**
     * Get Default Name
     *
     * Provide default name for snapshots
     *
     * @return string
     */
    public function _getDefaultName()
    {
        return date('ymd_') . microtime(true);
    } // END function _getDefaultName

    /**
     * Snapshot
     *
     * Save snapshot of db
     *
     * @param string $name
     *
     * @return array snapshot
     */
    public function snapshot($name = null)
    {
        if (!$name) {
            $name = $this->_getDefaultName();
        }

        $snapshot = $this->_notify(
            'snapshot',
            array(
                $this->getPdo(),
                $this->getOptions()
            )
        );

        $this->_saveSnapshot($snapshot, $name);

        return $snapshot;
    } // END function snapshot

    /**
     * Get Snapshot
     *
     * Get snapshot data
     *
     * @param string $name
     *
     * @return array $snapshot Where the key is the plugin name and the values
     *                          have the snapshot data
     */
    public function getSnapshot($name)
    {
        if (array_key_exists($name, $this->_snapshots)) {
            return $this->_snapshots[$name];
        }

        return false;
    } // END function getSnapshot

    /**
     * Save Snapshot
     *
     * Save snapshot
     *
     * @param array  $snapshot
     * @param string $name
     *
     * @return Hawkeye
     */
    protected function _saveSnapshot($snapshot, $name)
    {
        $this->_snapshots[$name] = $snapshot;

        return $this;
    } // END function _saveSnapshot

    /**
     * Notify
     *
     * Notify plugins
     *
     * @param string $method Name invoke in plugins
     * @param mixed  $args   Additional arguments
     *
     * @return array of results
     */
    protected function _notify($method, $args)
    {
        $results = array();

        foreach ($this->_plugins as $name => $plugin) {
            $results[$name] = call_user_func_array(array($plugin, $method), array($args));
        }

        return $results;
    } // END function _notify

    /**
     * Diff
     *
     * Perform diff-analysis on before and after snapshots.
     * If both snapshots are not provided, the last two
     * will be poped off the snapshots stack and used.
     *
     * @param array $snapshotBefore
     * @param array $snapshotAfter
     *
     * @throws Hawkeye_Exception if two snapshots could not be found
     *
     * @return array
     */
    public function diff($snapshotBefore = null, $snapshotAfter = null)
    {
        if (empty($snapshotBefore) || empty($snapshotAfter)) {
            list($snapshotAfter, $snapshotBefore) = $this->_getLastSnapshots(2);
        }

        $diff = $this->_notify(
            'diff',
            array(
                $snapshotBefore,
                $snapshotAfter
            )
        );

        return $diff;
    } // END function diff

    /**
     * Get Last Snapshots
     *
     * Get the last N snapshots
     *
     * @param int $n
     *
     * @throws Hawkeye_Exception
     *
     * @return array $snapshots
     */
    protected function _getLastSnapshots($n = 2)
    {
        $snapshots = array();

        $snapshotStore = $this->_snapshots;

        for ($i = 1; $i <= $n; $i++) {
            if (!$snapshots[] = array_pop($snapshotStore)) {
                require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Exception.php';
                throw new Hawkeye_Exception(self::ERROR_NO_VALID_SNAPSHOTS);
            }
        }

        return $snapshots;
    } // END function _getLastSnapshots

    /**
     * Diff To String
     *
     * Return a string-report of a diff. If no diff is provided,
     * last two snapshots will be used.
     *
     * @param array $diff
     *
     * @return string
     */
    public function diffToString($diff = null)
    {
        if (empty($diff)) {
            $diff = $this->diff();
        }

        $output = $this->_notify('diffToString', $diff);

        return implode(str_repeat(PHP_EOL, 2), $output) . PHP_EOL;
    } // END function diffToString

    /**
     * To String
     *
     * Render diff report of the last 2 snapshots
     *
     * @return string
     */
    public function __toString()
    {
        return $this->diffToString();
    } // END function __toString

    /**
     * Log
     *
     * Log runtime information
     *
     * @param string                 $message
     * @param int                    $level
     * @param Hawkeye_PluginAbstract $plugin
     *
     * @see Hawkeye for level consts (Error, Notice, Debug, etc)
     *
     * return Hawkeye
     */
    public function log($message, $level = null, $plugin = null)
    {
        if (!$level) {
            $level = self::INFO;
        }

        $log = array(
            'message'  => $message,
            'level'    => $level,
            'datetime' => date('ymd.His.u'),
        );

        if ($plugin instanceof Hawkeye_PluginAbstract) {
            $log['plugin'] = $plugin->getName();
        }

        $this->_logs[] = $log;

        if ($level >= $this->logLevel) {
            $this->_printLog($log);
        }

        return $this;
    } // END function log

    /**
     * Print Log
     *
     * Print log to screen
     *
     * @param array $log
     */
    protected function _printLog($log)
    {
        $template = '[%time%][%level%] %plugin% %message%' . PHP_EOL;

        print strtr(
            $template,
            array(
                '%time%'    => $log['datetime'],
                '%level%'   => $log['level'],
                '%plugin%'  => $log['plugin'],
                '%message%' => $log['message'],
            )
        );
    } // END function _printLog

    /**
     * Clear
     *
     * Clear snapshot instances
     *
     * @return Hawkeye
     */
    public function clear()
    {
        $this->_snapshots = array();

        return $this;
    } // END function clear
}