<?php
/**
 * Hawkeye_Plugin_Checksum
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 * @filesource
 *
 */

/**
 * Hawkeye_Plugin_Checksum
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 *
 */
class Hawkeye_Plugin_Checksum
    extends Hawkeye_PluginAbstract
{

    const ERROR_LOADING_CHECKSUM = 'Could not get Checksum for table [%s]';

    const QUERY_CHECKSUM = 'CHECKSUM TABLE %s EXTENDED';

    const FETCH_COLUMN = 'Checksum';

    /**
     * Snapshot
     *
     * Takes snapshot of checksum value for tables defined
     * in $options['tables']
     *
     * @param pdo   $pdo
     * @param array $options
     *
     * (non-PHPdoc)
     * @see Hawkeye_PluginAbstract::snapshot()
     */
    public function snapshot($pdo, $options = array())
    {
        $snapshot = array();
        foreach ($options['tables'] as $table) {
            $snapshot[$table] = $this->_checksum($table, $pdo);
        }

        return $snapshot;
    } // END function snapshot

    /**
     * Diff
     *
     * Return difference between checksum values in snapshots
     *
     * @param array $snapshotA
     * @param array $snapshotB
     *
     * (non-PHPdoc)
     * @see Hawkeye_PluginAbstract::diff()
     */
    public function diff($snapshotA, $snapshotB)
    {
        $snapshotA = $snapshotA['Checksum'];
        $snapshotB = $snapshotB['Checksum'];

        $tables = array_keys($snapshotB);

        $diff = array();

        foreach ($tables as $table) {
            if ($snapshotB[$table] !== $snapshotA[$table]) {
                $result = true;
            } else {
                $result = false;
            }

            $diff[$table] = array(
                'diff'        => $result,
                'beforeValue' => $snapshotA[$table],
                'afterValue'  => $snapshotB[$table],
            );
        }

        return $diff;
    } // END function diff

    /**
     * Diff To String
     *
     * @param array $diff
     *
     * (non-PHPdoc)
     * @see Hawkeye_PluginAbstract::diffToString()
     */
    public function diffToString($diff)
    {
        $output = array();

        $output[] = 'Data-Changes detected in the following tables';
        foreach ($diff['Checksum'] as $table => $log) {
            if (!$log['diff']) {
                continue;
            }

            $output[] = $table;
        }

        if (count($output) == 1) {
            $output = array('No data changes detected');
        }

        return implode(PHP_EOL, $output);
    } // END function diffToString

    /**
     * Checksum
     *
     * Get checksum value for table
     *
     * @param string $tableName
     * @param PDO    $pdo
     *
     * @return string $checksum
     */
    protected function _checksum($tableName, $pdo)
    {
        $result = $pdo->query(sprintf(self::QUERY_CHECKSUM, $tableName))
                ->fetch(PDO::FETCH_ASSOC);

        if (!isset($result[self::FETCH_COLUMN])) {
            $this->_log(
                sprintf(
                    self::ERROR_LOADING_CHECKSUM,
                    $tableName
                ),
                Hawkeye::NOTICE
            );

            return 'error-fetching-checksum';
        }

        return $result[self::FETCH_COLUMN];
    } // END function _checksum

} // END class Hawkeye_Plugin_Checksum
