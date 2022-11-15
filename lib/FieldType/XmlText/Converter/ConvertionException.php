<?php
declare( strict_types=1 );

namespace eZ\Publish\Core\FieldType\XmlText\Converter;

class ConvertionException extends \Exception
{
    public $result;
    public $errors;
    public $inputDocument;
    public $contentFieldId;

    /**
     * @param $result
     * @param $errors
     * @param $inputDocument
     * @param $contentFieldId
     */
    public function __construct( $result, $errors, $inputDocument, $contentFieldId )
    {
        $this->result = $result;
        $this->errors = $errors;
        $this->inputDocument = $inputDocument;
        $this->contentFieldId = $contentFieldId;
    }


}
