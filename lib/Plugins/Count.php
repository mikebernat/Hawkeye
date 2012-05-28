<?php
/**
 * Hawkeye_Plugin_Count
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 * @filesource
 *
 */

/**
 * Hawkeye_Plugin_Count
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 *
 */
class Hawkeye_Plugin_Count
    extends Hawkeye_PluginAbstract
{

    const QUERY_COUNT = 'SELECT COUNT(1) total FROM %s';

    /**
     * Snapshot
     *
     * Takes snapshot of count values for tables defined
     * in $options['tables']
     *
     * @param PDO   $pdo
     * @param array $options
     *
     * (non-PHPdoc)
     * @see Hawkeye_PluginAbstract::snapshot()
     */
    public function snapshot($pdo, $options = array())
    {
        $snapshot = array();
        foreach ($options['tables'] as $table) {
            $snapshot[$table] = $this->_count($table, $pdo);
        }

        return $snapshot;
    } // END function snapshot

    /**
     * Diff
     *
     * Return difference between count values in snapshots
     *
     * @param array $snapshotA
     * @param array $snapshotB
     *
     * (non-PHPdoc)
     * @see Hawkeye_PluginAbstract::diff()
     */
    public function diff($snapshotA, $snapshotB)
    {
        $snapshotA = $snapshotA['Count'];
        $snapshotB = $snapshotB['Count'];

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

        $output[] = 'Row Count changes detected in the following tables';
        foreach ($diff['Count'] as $table => $log) {
            if (!$log['diff']) {
                continue;
            }

            // We only need $sign to show for positive ints
            // Negative and 'equal' signs are already taken care of
            $sign = ($log['afterValue'] > $log['beforeValue']) ? '+' : '';
            $countDiff = $log['afterValue'] - $log['beforeValue'];

            $output[] = sprintf('%s %s%s', $table, $sign, $countDiff);
        }

        if (count($output) == 1) {
            $output = array('No Row Count changes detected');
        }

        return implode(PHP_EOL, $output);
    } // END function diffToString

    /**
     * Count
     *
     * Get count value for table
     *
     * @param string $tableName
     * @param PDO    $pdo
     *
     * @return string $count
     */
    protected function _count($tableName, $pdo)
    {
        $count = $pdo->query(sprintf(self::QUERY_COUNT, $tableName))
            ->fetch(PDO::FETCH_COLUMN);

        return $count;
    } // END function _count

} // END class Hawkeye_Plugin_Count
