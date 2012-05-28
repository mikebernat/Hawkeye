<?php

class Hawkeye_Exception_Test
    extends PHPUnit_Framework_TestCase
{

    /**
     * Test Assert
     *
     * @covers Hawkeye_Exception::assert
     *
     * @dataProvider provide_assert
     */
    public function test_assert($condition, $message, $class, $exception, $expected)
    {
        if ($exception) {
            $this->setExpectedException($exception);
        }

        if (!empty($class) && @!class_exists($class)) {
            $exceptionClass = $this->getMockBuilder('Exception')
                ->setMockClassName($class)
                ->getMock();
        }

        $result = Hawkeye_Exception::assert($condition, $message, $class);

        $this->assertEquals($result, $expected);
    } // END function test_assert

    /**
     * Provide Assert
     */
    public function provide_assert()
    {
        return array(
            'true condition' => array(
                'condition' => (true === true),
                'message'   => null,
                'class'     => null,
                'exception' => false,
                'expected'  => true,
            ),
            'false condition' => array(
                'condition' => (true === false),
                'message'   => null,
                'class'     => null,
                'exception' => 'Hawkeye_Exception',
                'expected'  => false,
            ),
            'custom class' => array(
                'condition' => false,
                'message'   => null,
                'class'     => 'Test_Exception',
                'exception' => 'Test_Exception',
                'expected'  => false,
            ),
        );
    } // END function provide_assert

} // END class Hawkeye_Exception_Test