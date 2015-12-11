<?php namespace DreamFactory\Library\Utility\Tests\Enums;

class FactoryEnumTest extends \PHPUnit_Framework_TestCase
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @covers FactoryEnum::introspect()
     * @covers FactoryEnum::contains()
     * @covers FactoryEnum::defines()
     */
    public function testIntrospect()
    {
        $_constants = TestEnum::all();
        $this->assertNotEmpty($_constants);

        foreach ($_constants as $_constant => $_value) {
            $this->assertTrue(TestEnum::contains($_value));
            $this->assertTrue(TestEnum::defines($_constant));
        }
    }
}
