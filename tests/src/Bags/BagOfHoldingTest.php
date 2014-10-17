<?php
/**
 * This file is part of the DreamFactory Console Tools Library
 *
 * Copyright 2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace DreamFactory\Tools\Fabric\Tests\Bags;

use DreamFactory\Library\Console\Bags\BagOfHolding;

class BagOfHoldingTest extends \PHPUnit_Framework_TestCase
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @var array
     */
    private $_data;

    /**
     * @var BagOfHolding
     */
    private $_bag;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    protected function setUp()
    {
        $this->_data = array(
            'hello'      => 'world',
            'always'     => 'be happy',
            'user.login' => 'scooby',
            'csrf.token' => array(
                'a' => '1234',
                'b' => '4321',
            ),
            'category'   => array(
                'fishing' => array(
                    'first'  => 'cod',
                    'second' => 'sole'
                )
            ),
        );

        $this->_bag = new BagOfHolding( 'bag-test', $this->_data );
    }

    protected function tearDown()
    {
        $this->_bag = null;
        $this->_data = array();
    }

    public function testInitialize()
    {
        $_bag = new BagOfHolding( 'bag-test' );
        $_bag->initialize( $this->_data );
        $this->assertEquals( $this->_data, $_bag->all() );

        $_data = array('should' => 'change');
        $_bag->initialize( $_data );
        $this->assertEquals( $_data, $_bag->all() );
    }

    public function testGetId()
    {
        $this->assertEquals( 'bag-test', $this->_bag->getId() );

        $_bag = new BagOfHolding( 'test' );
        $this->assertEquals( 'test', $_bag->getId() );
    }

    /**
     * @dataProvider bagDataProvider
     *
     * @param $key
     * @param $value
     * @param $exists
     */
    public function testHas( $key, $value, $exists )
    {
        $this->assertEquals( $exists, $this->_bag->has( $key, false ) );
    }

    public function testKeys()
    {
        $this->assertEquals( array_keys( $this->_data ), $this->_bag->keys() );
    }

    /**
     * @dataProvider bagDataProvider
     *
     * @param $_key
     * @param $value
     * @param $expected
     */
    public function testGet( $_key, $value, $expected )
    {
        $this->assertEquals( $value, $this->_bag->get( $_key ) );
    }

    public function testGetDefaults()
    {
        $this->assertNull( $this->_bag->get( 'user2.login' ) );
        $this->assertEquals( 'default', $this->_bag->get( 'user2.login', 'default' ) );
    }

    /**
     * @dataProvider bagDataProvider
     *
     * @param $_key
     * @param $value
     * @param $expected
     */
    public function testSet( $_key, $value, $expected )
    {
        $this->_bag->set( $_key, $value );
        $this->assertEquals( $value, $this->_bag->get( $_key ) );
    }

    public function testAll()
    {
        $this->assertEquals( $this->_data, $this->_bag->all() );
        $this->_bag->set( 'knock-knock', 'who-is-there' );

        $_data = $this->_data;
        $_data['knock-knock'] = 'who-is-there';

        $this->assertEquals( $_data, $this->_bag->all() );
    }

    public function testAllJson()
    {
        $_json = <<<JSON
{
    "hello": "world",
    "always": "be happy",
    "user.login": "scooby",
    "csrf.token": {
        "a": "1234",
        "b": "4321"
    },
    "category": {
        "fishing": {
            "first": "cod",
            "second": "sole"
        }
    }
}
JSON;

        $this->assertEquals( $_json, $this->_bag->all( 'json' ) );

    }

    public function testReplace()
    {
        $_data = array();

        $_data['name'] = 'scooby';
        $_data['jeep'] = 'cherokee';

        $this->_bag->replace( $_data );

        $this->assertEquals( $_data, $this->_bag->all() );
        $this->assertNull( $this->_bag->get( 'hello' ) );
        $this->assertNull( $this->_bag->get( 'always' ) );
        $this->assertNull( $this->_bag->get( 'user.login' ) );
    }

    public function testRemove()
    {
        $this->assertEquals( 'world', $this->_bag->get( 'hello' ) );
        $this->_bag->remove( 'hello' );
        $this->assertNull( $this->_bag->get( 'hello' ) );

        $this->assertEquals( 'be happy', $this->_bag->get( 'always' ) );
        $this->_bag->remove( 'always' );
        $this->assertNull( $this->_bag->get( 'always' ) );

        $this->assertEquals( 'scooby', $this->_bag->get( 'user.login' ) );
        $this->_bag->remove( 'user.login' );
        $this->assertNull( $this->_bag->get( 'user.login' ) );
    }

    public function testClear()
    {
        $this->_bag->clear();
        $this->assertEquals( array(), $this->_bag->all() );
    }

    public function bagDataProvider()
    {
        return array(
            array('hello', 'world', true),
            array('always', 'be happy', true),
            array('user.login', 'scooby', true),
            array('csrf.token', array('a' => '1234', 'b' => '4321'), true),
            array('category', array('fishing' => array('first' => 'cod', 'second' => 'sole')), true),
            array('user2.login', null, false),
            array('never', null, false),
            array('bye', null, false),
            array('bye/for/now', null, false),
        );
    }

    /**
     * @covers DreamFactory\Library\Console\Bags\BagOfHolding::getIterator
     */
    public function testGetIterator()
    {
        $_i = 0;

        foreach ( $this->_bag as $_key => $_value )
        {
            $this->assertEquals( $this->_data[$_key], $_value );
            $_i++;
        }

        $this->assertEquals( count( $this->_data ), $_i );
    }

    /**
     * @covers DreamFactory\Library\Console\Bags\BagOfHolding::count
     */
    public function testCount()
    {
        $this->assertEquals( count( $this->_data ), count( $this->_bag ) );
    }
}
