<?php
/**
 * Hawkeye_Exception
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 * @filesource
 *
 */

/**
 * Hawkeye_Exception
 *
 * @package  Hawkeye
 * @author   Mike Bernat <mike@mikebernat.com>
 * @license  MIT http://www.opensource.org/licenses/MIT
 * @link     https://github.com/mikebernat/Hawkeye
 *
 */
class Hawkeye_Exception
    extends Exception
{

    /**
     * Assert
     *
     * Make an assertion and throw exception is false
     *
     * @param bool   $condition
     * @param string $message
     * @param string $exceptionClass
     *
     * @throws Hawkeye_Exception
     *
     * @return bool
     */
    public static function assert($condition, $message = 'Assertion Failed', $exceptionClass = null)
    {
        if (true == $condition) {
            return true;
        }

        if (!$exceptionClass) {
            $exceptionClass = get_called_class();
        }

        throw new $exceptionClass($message);
    } // END function assert

} // END class Hawkeye_Exception