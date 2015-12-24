<?php namespace DreamFactory\Library\Utility;

use DreamFactory\Enterprise\Common\Exceptions\NotImplementedException;
use DreamFactory\Library\Utility\Enums\DataShapes;
use DreamFactory\Library\Utility\Shapers\JsonShape;
use DreamFactory\Library\Utility\Shapers\MediaWikiTableShape;
use DreamFactory\Library\Utility\Shapers\XmlShape;

/**
 * DataShaper
 * Transforms data into another shape
 */
class DataShaper
{
    //*************************************************************************
    //* Members
    //*************************************************************************

    /**
     * @var int The shape of data required. Defaults to JSON
     */
    protected $shape = DataShapes::JSON;
    /**
     * @type array The data to shape
     */
    protected $data;

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * Convenience method
     *
     * @param array|mixed $data    The data to shape
     * @param int|string  $shape   The new shape desired
     * @param array       $options Any options to pass to the shaper
     *
     * @return mixed
     */
    public function reshape($data = [], $shape = null, $options = [])
    {
        return $this->with($data)->shape($shape, $options);
    }

    /**
     * Returns the file extension, without a dot, for the shape
     *
     * @param int $shape The shape
     *
     * @return null|string
     */
    public function getShapeExtension($shape)
    {
        $_shape = DataShapes::resolve($shape ?: $this->shape);

        switch ($_shape) {
            case DataShapes::MEDIAWIKI_TABLE:
                return 'md';

            case DataShapes::JSON:
                return 'json';

            case DataShapes::XML:
                return 'xml';
        }

        return null;
    }

    /**
     * Specifies the data to be shaped
     *
     * @param array|mixed $data
     *
     * @return $this
     */
    public function with($data = [])
    {
        //  Convert to an array and store
        if (!is_array($data)) {
            if (is_scalar($data)) {
                throw new \InvalidArgumentException('The $data provided is a scalar value.');
            }

            if (false === ($_json = Json::encode($data)) || JSON_ERROR_NONE != json_last_error()) {
                throw new \InvalidArgumentException('The $data provided cannot be converted to an array.');
            }

            $data = Json::decode($_json, true);
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Reshapes the data given from $this->with()
     *
     * @param int|string $shape   The desired shape
     * @param array      $options Any options to pass to the shaper
     *
     * @return mixed
     * @throws \DreamFactory\Enterprise\Common\Exceptions\NotImplementedException
     */
    public function shape($shape = null, $options = [])
    {
        $_shape = DataShapes::resolve($shape ?: $this->shape);

        switch ($_shape) {
            case DataShapes::RAW:
                return $this->data;

            case DataShapes::MEDIAWIKI_TABLE:
                return MediaWikiTableShape::transform($this->data, $options);

            case DataShapes::JSON:
                return JsonShape::transform($this->data, $options);

            case DataShapes::XML:
                return XmlShape::transform($this->data, $options);
        }

        throw new NotImplementedException('The requested shape "' . $this->shape . '" is not valid.');
    }
}
