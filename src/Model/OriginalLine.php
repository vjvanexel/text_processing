<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/27/2017
 * Time: 13:41
 */
namespace Text\Processing\Model;

use DomainException;
use Zend\Filter\StringTrim;
use Zend\Filter\StripTags;
use Zend\I18n\Filter\NumberFormat;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\Validator\StringLength;

class OriginalLine implements InputFilterAwareInterface
{
    private $inputFilter;

    public $line_nr;
    public $words;

    public function exchangeArray($data)
    {
        $this->line_nr = $data['line_nr'];
        $this->words = $data['words'];

    }

    public function getArrayCopy()
    {
        return [
            'line_nr' => $this->line_nr,
            'words'   => $this->words
        ];
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new DomainException(sprintf(
            '%s does not allow injection of an alternate input filter',
            __CLASS__
        ));
    }

    public function getInputFilter()
    {
        // TODO: Implement getInputFilter() method.
        if ($this->inputFilter) {
            return $this->inputFilter;
        }

        $inputFilter = new InputFilter();

        $this->inputFilter = $inputFilter;
        return $this->inputFilter;

    }
}