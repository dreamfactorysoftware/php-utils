<?php namespace DreamFactory\Library\Utility\Shapers;

use DreamFactory\Library\Utility\Interfaces\ShapesData;

class XmlShape implements ShapesData
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type \DOMDocument The produced XML
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
        $_xml =
            new static(array_get($options, 'version', '1.0'),
                array_get($options, 'encoding', 'UTF-8'),
                array_get($options, 'root'),
                array_get($options, 'pretty', true));

        return $_xml->addChildArray($root, $array);
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
        return static::make(null, $source, $options)->document->saveXML();
    }

    /**
     * @param array         $array
     * @param \DOMNode|null $node
     */
    protected function appendArray(array $array, &$node = null)
    {
        $node = $node ?: $this->document;

        foreach ($array as $_key => $_value) {
            $_key = is_numeric($_key) ? 'item' . $_key : $_key;

            if (is_array($_value)) {
                $_node = $node->addChild($_key);
                $this->appendArray($_value, $_node);
            } else {
                $node->addChild($_key, $_value);
            }
        }
    }

    /**
     *  Converts and appends a node to the document
     *
     * @param string $root
     * @param array  $array
     *
     * @return $this
     */
    public function addChildArray($root, $array = [])
    {
        $_node = $this->document->addChild($root);
        $this->appendArray($array, $_node);

        return $this;
    }

    /**
     * @param \SimpleXMLElement $node
     * @param array             $array
     */
    public static function fromArray(&$node, array $array)
    {
        $node = $node ?: new \DOMNode();

        foreach ($array as $_key => $_value) {
            is_numeric($_key) && $_key = 'item' . $_key;

            if (is_array($_value)) {
                $_subnode = $node->addChild($_key);
                static::fromArray($_subnode, $_value);
            } else {
                $node->addChild($_key, $_value);
            }
        }
    }
}
