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

    /**
     * @covers FactoryEnum::toConstant()
     * @covers FactoryEnum::toValue()
     * @covers FactoryEnum::resolve()
     */
    public function testResolvers()
    {
        $this->assertEquals('BOOLEAN_TRUE', TestEnum::toConstant('true'));
        $this->assertEquals('INTEGER', TestEnum::toConstant(12345));
        $this->assertEquals('STRING', TestEnum::toConstant('i am a string'));

        $this->assertEquals('BOOLEAN_TRUE', TestEnum::resolve('true'));
        $this->assertEquals('INTEGER', TestEnum::resolve(12345));
        $this->assertEquals('STRING', TestEnum::resolve('i am a string'));
    }
}
