<?php namespace DreamFactory\Library\Utility\Shapers;

use DreamFactory\Library\Utility\Interfaces\ShapesData;
use DreamFactory\Library\Utility\Scalar;
use Illuminate\Support\Arr;

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
     * Transforms an array of data into a new shape
     *
     * @param array $source  The source data
     * @param array $options Any options to pass through to the shaping mechanism
     *
     * @return mixed
     */
    public static function transform(array $source, $options = [])
    {
        return static::make(array_get($options, 'root', 'root'), $source, $options)->saveXml();
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
        $_xml = new static(array_get($options, 'version', '1.0'), array_get($options, 'encoding', 'UTF-8'), array_get($options, 'pretty', true));
        $_xml->appendNode($root, $array);

        return $_xml;
    }

    /**
     * XmlShape constructor
     *
     * @param string $version
     * @param string $encoding
     * @param bool   $pretty
     */
    public function __construct($version = '1.0', $encoding = 'UTF-8', $pretty = true)
    {
        $this->pretty = $pretty;
        $this->document = new \DOMDocument($version, $this->encoding = $encoding);
        $this->document->formatOutput = $pretty;
    }

    /**
     * Convert an Array to XML
     *
     * @param string           $root  The name of the root node
     * @param array|string|int $array The data to reshape
     *
     * @return \DOMElement
     */
    protected function convert($root, $array = null)
    {
        $_node = $this->document->createElement($root);

        //  Get attributes
        if (is_array($array)) {
            if (array_key_exists('@attributes', $array)) {
                foreach ($array['@attributes'] as $_key => $_value) {
                    if (!$this->isValidTagName($_key)) {
                        throw new \RuntimeException('Illegal character in attribute name. attribute: ' . $_key . ' in node: ' . $root);
                    }

                    $_node->setAttribute($_key, Scalar::boolval($_value));
                }
            }

            //  Check for text/data
            if (array_key_exists('@value', $array)) {
                $_node->appendChild($this->document->createTextNode(Scalar::boolval($array['@value'])));

                return $_node;
            }

            //  Check for CDATA
            if (array_key_exists('@data', $array)) {
                $_node->appendChild($this->document->createCDATASection(Scalar::boolval($array['@cdata'])));

                return $_node;
            }

            //  Build out the xml tree
            foreach ($array as $_key => $_value) {
                if (!$this->isValidTagName($_key)) {
                    throw new \RuntimeException('Illegal character in tag name. tag: ' . $_key . ' in node: ' . $root);
                }

                //  Non-scalar values require more attention
                if (!is_scalar($_value)) {
                    if (is_array($_value)) {
                        if (!Arr::isAssoc($_value)) {
                            foreach ($_value as $_subnode) {
                                $_node->appendChild($this->convert($_key, $_subnode));
                            }
                        } else {
                            $_node->appendChild($this->convert($_key, $_value));
                        }

                        return $_node;
                    }

                    if (is_object($_value)) {
                        try {
                            if (method_exists($_value, 'toArray')) {
                                $_value = $_value->toArray();
                            } else {
                                $_value = (array)$_value;
                            }
                        } catch (\Exception $_ex) {
                            throw new \RuntimeException('It is not possible to convert the output  to XML.');
                        }
                    }
                }

                $_node->appendChild($this->convert($_key, $_value));
            }
        } else {
            $_node->appendChild($this->document->createTextNode(Scalar::boolval($array)));
        }

        return $_node;
    }

    /*
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     */
    protected function isValidTagName($tag)
    {
        static $_pattern = '/^[a-z_]+[a-z0-9:\-\._]*[^:]*$/i';

        return preg_match($_pattern, $tag, $matches) && $tag == $matches[0];
    }

    /**
     * @param \DomNode $node
     *
     * @return \DOMNode
     */
    public function appendChild($node)
    {
        return $this->document->appendChild($node);
    }

    /**
     *  Converts and appends a node to the document
     *
     * @param string $root
     * @param array  $array
     */
    public function appendNode($root, $array = [])
    {
        $this->appendChild($this->convert($root, $array));
    }

    /**
     * @param \DomNode|null $node
     * @param int|null      $options
     *
     * @return string
     */
    public function saveXml($node = null, $options = null)
    {
        return $this->document->saveXML($node, $options);
    }
}
