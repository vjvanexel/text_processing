<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/27/2017
 * Time: 13:10
 */
namespace Text\Processing\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Hydrator\ClassMethods as ClassMethodsHydrator;
use Text\Processing\Model\OriginalLine;
use Text\Processing\Form\OriginalWordsFieldset;

class OriginalLineFieldset extends Fieldset implements \Zend\InputFilter\InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('OriginalLine');

        $this->setObject(new OriginalLine()); // changed from Biblio()
        //$this->setHydrator(new ClassMethodsHydrator(false));
        
        /*$this->add([
            'name' => 'line_start',
            'type' => 'text',
            'options' => [
                'label' => 'Starting line nr.: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3',
                ]
            ],
        ]);
        $this->add([
            'name' => 'word_start',
            'type' => 'text',
            'options' => [
                'label' => 'Starting word nr.: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3',
                ]
            ],
        ]);*/

        $this->add(['name' => 'line_nr',
            'type' => 'text',
            'attributes' => [
                'id' => 'line_nr',
                'class' => 'col-sm-1 col-md-1 col-lg-1',
            ],
            'options' => [
                'label' => 'line nr.: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3'
                ]
            ],
        ]);
        $this->add([
            'name' => 'words',//'route_point_ids',
            'type' => Element\Collection::class,
            'options' => [
                //'label' => 'words',
                'count' => 1,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => [
                    'type' =>OriginalWordsFieldset::class,
                ],
            ],
        ]);
        $this->add([
            'name' => 'add_word',
            'type' => Element\Button::class,
            'options' => [
                'label' => 'Add another word2: '
            ],
            'attributes' => [
                'onclick' => "return add_form_element('form > div > fieldset > fieldset > fieldset')"
            ]
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