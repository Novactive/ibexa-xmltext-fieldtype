<?php

/**
 * This file is part of the eZ Platform XmlText Field Type package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace eZ\Publish\Core\FieldType\XmlText;

use DOMDocument;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use eZ\Publish\Core\FieldType\XmlText\Input\EzXml;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use RuntimeException;

/**
 * XmlText field type.
 */
class Type extends FieldType
{
    /**
     * Default preset of tags available in online editor.
     */
    const TAG_PRESET_DEFAULT = 0;

    /**
     * Preset of tags for online editor intended for simple formatting options.
     */
    const TAG_PRESET_SIMPLE_FORMATTING = 1;

    /**
     * @var \eZ\Publish\Core\FieldType\XmlText\InternalLinkValidator|null
     */
    protected $internalLinkValidator;

    /**
     * List of settings available for this FieldType.
     *
     * The key is the setting name, and the value is the default value for this setting
     *
     * @var array
     */
    protected $settingsSchema = [
        'numRows' => [
            'type' => 'int',
            'default' => 10,
        ],
        'tagPreset' => [
            'type' => 'choice',
            'default' => self::TAG_PRESET_DEFAULT,
        ],
    ];

    /**
     * Type constructor.
     *
     * @param \eZ\Publish\Core\FieldType\XmlText\InternalLinkValidator|null $internalLinkValidator
     */
    public function __construct(InternalLinkValidator $internalLinkValidator = null)
    {
        $this->internalLinkValidator = $internalLinkValidator;
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier()
    {
        return 'ezxmltext';
    }

    /**
     * Returns the name of the given field value.
     *
     * It will be used to generate content name and url alias if current field is designated
     * to be used in the content name/urlAlias pattern.
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        $result = null;
        if ($section = $value->xml->documentElement->firstChild) {
            $textDom = $section->firstChild;

            if ($textDom && $textDom->hasChildNodes()) {
                $result = $textDom->firstChild->textContent;
            } elseif ($textDom) {
                $result = $textDom->textContent;
            }
        }

        if ($result === null) {
            $result = $value->xml->documentElement->textContent;
        }

        return trim(preg_replace(['/\n/', '/\s\s+/'], ' ', $result));
    }

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \eZ\Publish\Core\FieldType\XmlText\Value
     */
    public function getEmptyValue()
    {
        return new Value();
    }

    /**
     * Returns if the given $value is considered empty by the field type.
     *
     * @param \eZ\Publish\Core\FieldType\XmlText\Value $value
     *
     * @return bool
     */
    public function isEmptyValue(SPIValue $value)
    {
        if ($value->xml === null) {
            return true;
        }

        return !$value->xml->documentElement->hasChildNodes();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param \eZ\Publish\Core\FieldType\XmlText\Value|\eZ\Publish\Core\FieldType\XmlText\Input|string $inputValue
     *
     * @return \eZ\Publish\Core\FieldType\XmlText\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (\is_string($inputValue)) {
            if (empty($inputValue)) {
                $inputValue = Value::EMPTY_VALUE;
            }
            $inputValue = new EzXml($inputValue);
        }

        if ($inputValue instanceof Input) {
            $inputValue = new Value($inputValue->getInternalRepresentation());
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \eZ\Publish\Core\FieldType\XmlText\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!$value->xml instanceof DOMDocument) {
            throw new InvalidArgumentType('$value->xml', 'DOMDocument', $value);
        }
    }

    /**
     * Returns sortKey information.
     *
     * @see \Ibexa\Core\FieldType
     *
     * @param \eZ\Publish\Core\FieldType\XmlText\Value $value
     *
     * @return array|bool
     */
    protected function getSortInfo(BaseValue $value)
    {
        return false;
    }

    /**
     * Converts an $hash to the Value defined by the field type.
     * $hash accepts the following keys:
     *  - xml (XML string which complies internal format).
     *
     * @param mixed $hash
     *
     * @return \eZ\Publish\Core\FieldType\XmlText\Value $value
     */
    public function fromHash($hash)
    {
        if (!isset($hash['xml'])) {
            throw new RuntimeException("'xml' index is missing in hash.");
        }

        return new Value($hash['xml']);
    }

    /**
     * Converts a $Value to a hash.
     *
     * @param \eZ\Publish\Core\FieldType\XmlText\Value $value
     *
     * @return mixed
     */
    public function toHash(SPIValue $value)
    {
        return ['xml' => (string)$value];
    }

    /**
     * Creates a new Value object from persistence data.
     * $fieldValue->data is supposed to be a string.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $fieldValue
     *
     * @return \eZ\Publish\Core\FieldType\XmlText\Value
     */
    public function fromPersistenceValue(FieldValue $fieldValue)
    {
        return new Value($fieldValue->data);
    }

    /**
     * @param \eZ\Publish\Core\FieldType\XmlText\Value $value
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\FieldValue
     */
    public function toPersistenceValue(SPIValue $value)
    {
        return new FieldValue(
            [
                'data' => $value->xml->saveXML(),
                'externalData' => null,
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    /**
     * Returns whether the field type is searchable.
     *
     * @return bool
     */
    public function isSearchable()
    {
        return true;
    }

    /**
     * Validates a field.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDefinition The field definition of the field
     * @param \eZ\Publish\Core\FieldType\XmlText\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $value)
    {
        $validationErrors = [];

        if ($this->internalLinkValidator !== null) {
            $errors = $this->internalLinkValidator->validate($value->xml);
            foreach ($errors as $error) {
                $validationErrors[] = new ValidationError($error);
            }
        }

        return $validationErrors;
    }

    /**
     * Validates the fieldSettings of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     *
     * @param mixed $fieldSettings
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validateFieldSettings($fieldSettings)
    {
        $validationErrors = [];

        foreach ($fieldSettings as $name => $value) {
            if (isset($this->settingsSchema[$name])) {
                switch ($name) {
                    case 'numRows':
                        if (!\is_int($value)) {
                            $validationErrors[] = new ValidationError(
                                "Setting '%setting%' value must be of integer type",
                                null,
                                [
                                    'setting' => $name,
                                ],
                                "[$name]"
                            );
                        }
                        break;
                    case 'tagPreset':
                        $definedTagPresets = [
                            self::TAG_PRESET_DEFAULT,
                            self::TAG_PRESET_SIMPLE_FORMATTING,
                        ];
                        if (!empty($value) && !\in_array($value, $definedTagPresets, true)) {
                            $validationErrors[] = new ValidationError(
                                "Setting '%setting%' is of unknown tag preset",
                                null,
                                [
                                    'setting' => $name,
                                ],
                                "[$name]"
                            );
                        }
                        break;
                }
            } else {
                $validationErrors[] = new ValidationError(
                    "Setting '%setting%' is unknown",
                    null,
                    [
                        'setting' => $name,
                    ],
                    "[$name]"
                );
            }
        }

        return $validationErrors;
    }

    /**
     * Returns relation data extracted from value.
     *
     * Not intended for \Ibexa\Contracts\Core\Repository\Values\Content\Relation::COMMON type relations,
     * there is a service API for handling those.
     *
     * @param \eZ\Publish\Core\FieldType\XmlText\Value $fieldValue
     *
     * @return array Hash with relation type as key and array of destination content ids as value.
     *
     * Example:
     * <code>
     *  array(
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::LINK => array(
     *          "contentIds" => array( 12, 13, 14 ),
     *          "locationIds" => array( 24 )
     *      ),
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::EMBED => array(
     *          "contentIds" => array( 12 ),
     *          "locationIds" => array( 24, 45 )
     *      ),
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::ATTRIBUTE => array( 12 )
     *  )
     * </code>
     */
    public function getRelations(SPIValue $value)
    {
        $relations = [];

        /** @var \eZ\Publish\Core\FieldType\XmlText\Value $value */
        if ($value->xml instanceof DOMDocument) {
            $relations = [
                Relation::LINK => $this->getRelatedObjectIds($value, Relation::LINK),
                Relation::EMBED => $this->getRelatedObjectIds($value, Relation::EMBED),
            ];
        }

        return $relations;
    }

    protected function getRelatedObjectIds(Value $fieldValue, $relationType)
    {
        if ($relationType === Relation::EMBED) {
            $tagName = 'embed';
        } else {
            $tagName = 'link';
        }

        $locationIds = [];
        $contentIds = [];
        $linkTags = $fieldValue->xml->getElementsByTagName($tagName);
        if ($linkTags->length > 0) {
            /** @var $link \DOMElement */
            foreach ($linkTags as $link) {
                $contentId = $link->getAttribute('object_id');
                if (!empty($contentId)) {
                    $contentIds[] = $contentId;
                    continue;
                }
                $locationId = $link->getAttribute('node_id');
                if (!empty($locationId)) {
                    $locationIds[] = $locationId;
                }
            }
        }

        return [
            'locationIds' => array_unique($locationIds),
            'contentIds' => array_unique($contentIds),
        ];
    }
}
