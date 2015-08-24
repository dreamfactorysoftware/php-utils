<?php namespace DreamFactory\Library\Utility\Interfaces;

/**
 * Something that can locate resources
 */
interface ResourceLocatorLike
{
    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * Locates a resource
     *
     * @param mixed  $type    The type of resource to locate
     * @param string $id      The file to locate
     * @param array  $options Any options needed by the locator
     *
     * @return mixed|bool The resource or FALSE on failure
     */
    public function locate($type, $id, $options = []);
}
