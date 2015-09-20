<?php namespace DreamFactory\Library\Utility\Shapers;

use DreamFactory\Library\Utility\Interfaces\ShapesData;

class MediaWikiTableShape implements ShapesData
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /** @inheritdoc */
    public static function transform(array $source, $options = [])
    {
        if (empty($source)) {
            return null;
        }

        $_class = trim('wikitable ' . str_replace('wikitable', null, trim(array_get($options, 'class'))));

        //  Build our header
        $_data[] = '{| class="' . $_class . '"';

        foreach (array_keys(current($source)) as $_header) {
            $_data[] = '! ' . $_header;
        }

        //  Now the rows...
        foreach ($source as $_row) {
            !is_array($_row) && $_row = (array)$_row;

            $_data[] = '|-';
            foreach ($_row as $_cell) {
                $_data[] = trim('| ' . $_cell);
            }
        }

        //  And the end-cap
        $_data[] = '|}';

        return implode(PHP_EOL, $_data);
    }
}
