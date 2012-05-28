<?php
/**
 * Hawkeye_PluginAbstract
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 * @filesource
 *
 */

/**
 * Hawkeye_PluginAbstract
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 *
 */
abstract class Hawkeye_PluginAbstract
{

    /**
     * Hawkeye
     *
     * @var HawkeyeDB
     */
    protected $_hawkeye;

    /**
     * Construct
     *
     * @param HawkeyeDB $hawkeye
     */
    public function __construct(HawkeyeDB $hawkeye)
    {
        $this->_hawkeye = $hawkeye;
    } // END function __construct
    /**
     * Init
     *
     * Method called immediatly after the plugin is added to the Hawkeye stack
     *
     * @param array $options Options
     */
    public function init ($options = array())
    {
    }

    /**
     * Snapshot
     *
     * Perform a "Before" snapshot function. Typically a query to the database
     * that fetches a piece of information to be stored and compared later.
     *
     * @param PDO   $pdo     A PDO instance to perform queries with
     * @param array $options Array of options
     *
     * @return mixed $snapshot The return type is up to you so long as you can
     *                          compare similar data structures later in diff()
     */
    abstract public function snapshot($pdo, $options = array());

    /**
     * Diff
     *
     * Compare two snapshots from @link self::snapshot() into a report
     *
     * @param $snapshotA The "Before" snapshot
     * @param $snapshotB The "After" snapshot
     *
     * @return array $diff An array composed of the results of the diff
     *                      This data should be as 'accessible' as possible
     *                      - meaning it should be easily interpreted by
     *                      several consumers to do things like echo simple
     *                      diffs or compile report summaries.
     *
     *                      Example:
     *                      <code>
     *                      array(
     *                          'TableRowChanges' => array(
     *                              'users' => 156,
     *                              'logs' => 2,
     *                              'comments' => -3,
     *                          )
     *                      )
     *                      </code>
     *
     *                      Simple, organized.
     *
     */
    abstract public function diff($snapshotA, $snapshotB);

    /**
     * Diff To String
     *
     * Take a diff report from @link self::diff() and return a report
     * string.
     *
     * @param array $diff
     *
     * @return string
     */
    abstract public function diffToString($diff);

    /**
     * Get Name
     *
     * Get the name of the plugin
     */
    public function getName()
    {
        $className = get_called_class();

        return preg_replace('/[^\w]/', '', $className);
    } // END function getName

    /**
     * Get Hawkeye
     *
     * Get instance of HawkeyeDB
     *
     * @return HawkeyeDB
     */
    protected function _getHawkeye ( )
    {
        return $this->_hawkeye;
    } // END function _getHawkeye

    /**
     * Log
     *
     * Log runtime information about the plugin
     *
     * @param string $message
     * @param int    $level
     *
     * @see HawkeyeDB for level information (Error, Notice, Debug, etc)
     *
     * @return HawkeyeDB
     */
    protected function _log($message, $level = null)
    {
        $this->_hawkeye->log($this, $message, $level);

        return $this;
    } // END function _log

} // END class Hawkeye_PluginAbstract