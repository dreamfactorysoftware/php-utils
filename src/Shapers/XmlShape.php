<?php namespace DreamFactory\Library\Utility\Shapers;

use DreamFactory\Library\Utility\Interfaces\ShapesData;

class XmlShape implements ShapesData
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type \SimpleXMLElement The produced XML
     */
    protected $document;
    /**
     * @type string The encoding to use
     */
    protected $encoding = 'UTF-8';
    /**
     * @type bool Pretty-print output
     */
    protected $pretty = true;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * XmlShape constructor
     *
     * @param string      $version
     * @param string      $encoding
     * @param string|null $root Optional root node
     * @param bool        $pretty
     */
    public function __construct($version = '1.0', $encoding = 'UTF-8', $root = null, $pretty = true)
    {
        $_xmlString = '<?xml version="' .
            $version .
            '" encoding="' .
            $encoding .
            '"?>' .
            ($root ? '<' . $root . '></' . $root . '>' : '<root></root>');

        $this->document = new \SimpleXMLElement($_xmlString);
        $this->pretty = $pretty;
    }

    /**
     * Create an XML shape from an array
     *
     * @param string $root  The name of the root node
     * @param array  $array The data to reshape
     * @param array  $options
     *
     * @return static
     */
    public static function make($root, array $array = [], $options = [])
    {
        return new static(array_get($options, 'version', '1.0'),
            array_get($options, 'encoding', 'UTF-8'),
            array_get($options, 'root'),
            array_get($options, 'pretty', true));
    }

    /**
     * Transforms an array of data into a new shape
     *
     * @param array $source  The source data
     * @param array $options Any options to pass through to the shaping mechanism
     *
     * @return mixed
     */
    public static function transform(array $source, $options = [])
    {
        $_xml = static::make($_root = array_get($options, 'root', 'root'), $source, $options);
        $_xml->appendArray($source, $_xml->document, array_get($options, 'item-type', 'item'));

        if (array_get($options, 'ugly', false)) {
            return $_xml->document->asXml();
        }

        //  Pretty print
        $_dom = \dom_import_simplexml($_xml->document)->ownerDocument;
        $_dom->formatOutput = true;

        return $_dom->saveXML();
    }

    /**
     * @param array                                 $array
     * @param \SimpleXMLElement[]|\SimpleXMLElement $node
     * @param string                                $type
     */
    protected function appendArray(array $array, &$node, $type = 'item')
    {
        foreach ($array as $_key => $_value) {
            $_key = is_numeric($_key) ? $type . '_' . $_key : $_key;

            if (is_array($_value)) {
                $_subnode = $node->addChild($_key);
                $this->appendArray($_value, $_subnode);
            } else {
                $node->addChild($_key, $_value);
            }
        }
    }
}
