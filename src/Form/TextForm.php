<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/27/2017
 * Time: 05:56
 */
namespace Text\Processing\Form;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\InputFilter;
use Text\Processing\Form\OriginalLineFieldset;
use Text\Processing\Form\OriginalTextFieldset;

class TextForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct($name = null);

        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setInputFilter(new InputFilter());

        $this->add([
            'name' => 'text_id',
            'type' => 'text',
            'options' => [
                'label' => 'Text name: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3'
                ]
            ],

        ]);
        $this->add([
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
        ]);
        $this->add([
            'name' => 'line_last',
            'type' => 'text',
            'options' => [
                'label' => 'Last line nr.: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3',
                ]
            ],
        ]);
        $this->add([
            'name' => 'word_last',
            'type' => 'text',
            'options' => [
                'label' => 'Last word nr.: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3',
                ]
            ],
        ]);
        $this->add([
            'name' => 'content',
            'type' => 'textarea',
            'attributes' => [
                'id' => 'content',
                //'class' => 'col-sm-9 col-md-9 col-lg-9',
                'cols' => 80,
                'rows' => 5,
            ],
            'options' => [
                'label' => 'Content: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3',
                ],
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Go',
                'id'    => 'submitbutton',
                'class' => 'btn btn-primary'
            ],
        ]);
    }
    

}