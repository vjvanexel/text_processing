<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/27/2017
 * Time: 10:41
 */
namespace Text\Processing\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Hydrator\ClassMethods as ClassMethodsHydrator;
use Text\Processing\Model\Text;

class OriginalTextFieldset extends Fieldset implements \Zend\InputFilter\InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('OriginalText');

        $this->setObject(new Text()); // changed from Biblio()
        $this->setHydrator(new ClassMethodsHydrator(false));

        $this->add([
            'name' => 'lines',
            'type' => Element\Collection::class,
            'attributes' => [
                'id' => '',
                'class' => 'col-sm-9 col-md-9 col-lg-9'
            ],
            'options' => [
                /*'label' => 'Line of text (original): ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3'
                ],*/
                //'use_as_base_fieldset' => true,
                'count' => 1,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => [
                    'type' =>OriginalLineFieldset::class,
                ],
            ],
        ]);

    }

    public function getInputFilterSpecification()
    {
        return [
            'name' => [
                'required' => true,
            ]
        ];
    }
}
