<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/27/2017
 * Time: 11:45
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

class OriginalWord implements InputFilterAwareInterface
{
    private $inputFilter;

    public $word_id;
    public $text_id;
    public $line_nr;
    public $word_nr;
    public $word;
    public $transl_word_id;
    public $word_note;

    public function exchangeArray($data)
    {
        $this->word_id        = isset($data['word_id']) ? $data['word_id'] : null;
        $this->text_id        = isset($data['text_id']) ? $data['text_id'] : null;
        $this->line_nr        = isset($data['line_nr']) ? $data['line_nr'] : null;
        $this->word_nr        = isset($data['word_nr']) ? $data['word_nr'] : null;
        $this->word           = $data['word'];
        $this->transl_word_id = isset($data['transl_word_id']) ? $data['transl_word_id'] : null;
        $this->word_note = isset($data['word_note']) ? $data['word_note'] : null;
    }

    public function getArrayCopy()
    {
        return [
            'word_id'       => $this->word_id,
            'text_id'       => $this->text_id,
            'line_nr'       => $this->line_nr,
            'word_nr'       => $this->word_nr,
            'word'          => $this->word,
            'tranl_word_id' => $this->transl_word_id,
            'word_note'     => $this->word_note
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