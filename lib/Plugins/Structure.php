<?php
/**
 * Hawkeye_Plugin_Structure
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 * @filesource
 *
 */

/**
 * Hawkeye_Plugin_Structure
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 *
 */
class Hawkeye_Plugin_Structure
    extends Hawkeye_PluginAbstract
{

    /**
     * Snapshot
     *
     * Takes structure snapshots for tables defined
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
        foreach($options['tables'] as $table) {
            $snapshot[$table] = $this->_structure($table, $pdo);
        }

        return $snapshot;
    } // END function snapshot

    /**
     * Diff
     *
     * Return structure differences in snapshots
     *
     * @param array $snapshotA
     * @param array $snapshotB
     *
     * (non-PHPdoc)
     * @see Hawkeye_PluginAbstract::diff()
     */
    public function diff($snapshotA, $snapshotB)
    {
        $snapshotA = $snapshotA['Structure'];
        $snapshotB = $snapshotB['Structure'];

        $tables = array_keys($snapshotB);

        $diff = array();

        foreach($tables as $table) {
            if ($snapshotB[$table] !== $snapshotA[$table]) {
                $result = true;
            } else {
                $result = false;
            }

            $diff[$table] = array(
                'diff'        => $result,
                'beforeValue' => var_export($snapshotA[$table], true),
                'afterValue'  => var_export($snapshotB[$table], true),
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

        $output[] = 'Structure changes detected in the following tables';
        foreach ($diff['Structure'] as $table => $log) {
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
            $output = array('No Structure changes detected');
        }

        return implode(PHP_EOL, $output);
    } // END function diffToString

    /**
     * Structure
     *
     * Get structure definition for table
     *
     * @param string $tableName
     * @param PDO $pdo
     *
     * @return array $structure
     */
    protected function _structure($tableName, $pdo)
    {
        $structure = array();
        $structure['describe'] = $pdo->query('DESCRIBE ' . $tableName)->fetchAll(PDO::FETCH_ASSOC);

        $index = $pdo->query('SHOW INDEX FROM ' . $tableName)->fetchAll(PDO::FETCH_ASSOC);

        // Remove fields that could be mistaken as structure change
        $columns_ignore = array('Cardinality');

        foreach ($index as $key => $column)
        {
            foreach ($columns_ignore as $col)
            {
                unset($index[$key][$col]);
            }
        }

        $structure['index'] = $index;

        return $structure;
    } // END function _structure

} // END class Hawkeye_Plugin_Structure
