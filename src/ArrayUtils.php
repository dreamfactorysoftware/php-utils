<?php
/**
 * This file is part of the DreamFactory Rave(tm)
 *
 * DreamFactory Rave(tm) <http://github.com/dreamfactorysoftware/rave>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
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

namespace DreamFactory\Library\Utility;

class ArrayUtils
{
    /**
     * A recursive array_change_key_case lowercase function.
     *
     * @param array $input
     *
     * @return array
     */
    public static function array_key_lower( $input )
    {
        if ( !is_array( $input ) )
        {
            trigger_error( "Invalid input array '{$input}'", E_USER_NOTICE );
            exit;
        }
        $input = array_change_key_case( $input, CASE_LOWER );
        foreach ( $input as $key => $array )
        {
            if ( is_array( $array ) )
            {
                $input[$key] = static::array_key_lower( $array );
            }
        }

        return $input;
    }

    /**
     * @param $array
     *
     * @return bool
     */
    public static function isArrayNumeric( $array )
    {
        if ( is_array( $array ) )
        {
            for ( $k = 0, reset( $array ); $k === key( $array ); next( $array ) )
            {
                ++$k;
            }

            return is_null( key( $array ) );
        }

        return false;
    }

    /**
     * @param      $array
     * @param bool $strict
     *
     * @return bool
     */
    public static function isArrayAssociative( $array, $strict = true )
    {
        if ( is_array( $array ) )
        {
            if ( !empty( $array ) )
            {
                if ( $strict )
                {
                    return ( count( array_filter( array_keys( $array ), 'is_string' ) ) == count( $array ) );
                }
                else
                {
                    return ( 0 !== count( array_filter( array_keys( $array ), 'is_string' ) ) );
                }
            }
        }

        return false;
    }

    /**
     * @param        $list
     * @param        $find
     * @param string $delim
     * @param bool   $strict
     *
     * @return bool
     */
    public static function isInList( $list, $find, $delim = ',', $strict = false )
    {
        return ( false !== array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict ) );
    }

    /**
     * @param        $list
     * @param        $find
     * @param string $delim
     * @param bool   $strict
     *
     * @return mixed
     */
    public static function findInList( $list, $find, $delim = ',', $strict = false )
    {
        return array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict );
    }

    /**
     * @param        $list
     * @param        $find
     * @param string $delim
     * @param bool   $strict
     *
     * @return string
     */
    public static function addOnceToList( $list, $find, $delim = ',', $strict = false )
    {
        if ( empty( $list ) )
        {
            $list = $find;

            return $list;
        }
        $pos = array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict );
        if ( false !== $pos )
        {
            return $list;
        }
        $fieldarr = array_map( 'trim', explode( $delim, $list ) );
        $fieldarr[] = $find;

        return implode( $delim, array_values( $fieldarr ) );
    }

    /**
     * @param        $list
     * @param        $find
     * @param string $delim
     * @param bool   $strict
     *
     * @return string
     */
    public static function removeOneFromList( $list, $find, $delim = ',', $strict = false )
    {
        $pos = array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict );
        if ( false === $pos )
        {
            return $list;
        }
        $fieldarr = array_map( 'trim', explode( $delim, $list ) );
        unset( $fieldarr[$pos] );

        return implode( $delim, array_values( $fieldarr ) );
    }

    /**+
     * Provides a diff of two arrays, recursively.
     * Any keys or values that do not match are returned in an array.
     * Empty results indicate no change obviously.
     *
     * @param array $array1
     * @param array $array2
     * @param bool  $check_both_directions
     *
     * @return array
     */
    public static function array_diff_recursive( array $array1, $array2, $check_both_directions = false )
    {
        $_return = array();

        if ( $array1 !== $array2 )
        {
            foreach ( $array1 as $_key => $_value )
            {
                //	Is the key is there...
                if ( !array_key_exists( $_key, $array2 ) )
                {
                    $_return[$_key] = $_value;
                    continue;
                }

                //	Not an array?
                if ( !is_array( $_value ) )
                {
                    if ( $_value !== $array2[$_key] )
                    {
                        $_return[$_key] = $_value;
                        continue;
                    }
                }

                //	If we've got two arrays, diff 'em
                if ( is_array( $array2[$_key] ) )
                {
                    $_diff = static::array_diff_recursive( $_value, $array2[$_key] );

                    if ( !empty( $_diff ) )
                    {
                        $_return[$_key] = $_diff;
                    }

                    continue;
                }

                $_return[$_key] = $_value;
            }

            if ( $check_both_directions )
            {
                foreach ( $array2 as $_key => $_value )
                {
                    //	Is the key is there...
                    if ( !array_key_exists( $_key, $array1 ) )
                    {
                        $_return[$_key] = $_value;
                        continue;
                    }

                    //	Not an array?
                    if ( !is_array( $_value ) )
                    {
                        if ( $_value !== $array1[$_key] )
                        {
                            $_return[$_key] = $_value;
                            continue;
                        }
                    }

                    //	If we've got two arrays, diff 'em
                    if ( is_array( $array1[$_key] ) )
                    {
                        $_diff = static::array_diff_recursive( $_value, $array1[$_key] );

                        if ( !empty( $_diff ) )
                        {
                            $_return[$_key] = $_diff;
                        }

                        continue;
                    }

                    $_return[$_key] = $_value;
                }
            }
        }

        return $_return;
    }

    /**
     * A case-insensitive "in_array" for all intents and purposes. Works with objects too!
     *
     * @param string       $needle
     * @param array|object $haystack
     * @param bool         $strict
     *
     * @return bool Returns true if found, false otherwise. Just like in_array
     */
    public static function contains( $needle, $haystack, $strict = false )
    {
        foreach ( $haystack as $_index => $_value )
        {
            if ( is_string( $_value ) )
            {
                if ( 0 === strcasecmp( $needle, $_value ) )
                {
                    return true;
                }
            }
            else if ( in_array( $needle, $_value, $strict ) )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Searches a multi-dimension array by key and value and returns
     * the array that holds the key => value pair or optionally returns
     * the value of a supplied key from the resultant array.
     *
     * @param array  $array
     * @param string $key
     * @param string $value
     * @param string $returnKey
     *
     * @return null
     */
    public static function findByKeyValue( $array, $key, $value, $returnKey = null )
    {
        foreach ( $array as $item )
        {
            if ( $item[$key] === $value )
            {
                if ( $returnKey )
                {
                    return $item[$returnKey];
                }
                else
                {
                    return $item;
                }
            }
        }

        return null;
    }

}