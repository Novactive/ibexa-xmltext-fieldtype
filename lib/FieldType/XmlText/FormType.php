<?php

namespace eZ\Publish\Core\FieldType\XmlText;

use Ibexa\Contracts\Core\Repository\FieldTypeService;
use EzSystems\EzPlatformContentForms\FieldType\DataTransformer\FieldValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class FormType extends AbstractType
{
    /**
     * @var \Ibexa\Contracts\Core\Repository\FieldTypeService
     */
    private $fieldTypeService;

    public function __construct(FieldTypeService $fieldTypeService)
    {
        $this->fieldTypeService = $fieldTypeService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('xml', TextareaType::class, ['attr' => ['rows' => 10]])
            ->addModelTransformer(
                new FieldValueTransformer(
                    $this->fieldTypeService->getFieldType('ezxmltext')
                )
            );
    }

    public function getBlockPrefix()
    {
        return 'ezplatform_fieldtype_ezxmltext';
    }
}
