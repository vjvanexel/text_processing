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
use Text\Processing\Model\OriginalWord;

class OriginalWordsFieldset extends Fieldset implements \Zend\InputFilter\InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('word');

        $this->setObject(new OriginalWord()); // changed from Biblio()
        $this->setHydrator(new ClassMethodsHydrator(false));

        $this->add(['name' => 'word_nr',
            'type' => 'text',
            'attributes' => [
                'id' => 'line_nr',
                'class' => 'col-sm-1 col-md-1 col-lg-1',
            ],
            'options' => [
                'label' => 'word nr.: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3'
                ]
            ],
        ]);
        $this->add(['name' => 'word_id',
            'type' => 'text',
            'attributes' => [
                'id' => 'line_nr',
                'class' => 'col-sm-1 col-md-1 col-lg-1',
            ],
            'options' => [
                'label' => 'word id.: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3'
                ]
            ],
        ]);
        $this->add(['name' => 'word',
            'type' => 'text',
            'attributes' => [
                'id' => 'line_nr',
                'class' => 'col-sm-1 col-md-1 col-lg-1',
            ],
            'options' => [
                'label' => 'word: ',
                'label_attributes' => [
                    'class' => 'col-sm-3 col-md-3 col-lg-3'
                ]
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