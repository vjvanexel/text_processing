<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/27/2017
 * Time: 04:59
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
use Text\Processing\Model\OriginalWord;
use Text\Processing\Model\OriginalLine;

class Text implements InputFilterAwareInterface
{
    private $inputFilter;

    public $text_db_id;
    public $text_id;
    public $word_start;
    public $word_last;
    public $line_start;
    public $line_last;
    public $lines;
    public $content;
    public $type;
    public $translation;

    /**
     * Populate Text object with data. 
     * 
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->text_db_id   = isset($data['text_db_id']) ? $data['text_db_id'] : null;
        $this->text_id      = !empty($data['text_id'])   ? $data['text_id'] : null;
        $this->word_start   = isset($data['word_start']) ? $data['word_start'] : null;
        $this->line_start   = isset($data['line_start']) ? $data['line_start'] : null;
        $this->line_last    = isset($data['line_last'])  ? $data['line_last'] : null;
        $this->word_last    = isset($data['word_last'])  ? $data['word_last'] : null;
        $this->translation  = isset($data['translation']) ? $data['translation'] : null;

        // Set content based on data being loaded into this Text object  
        if (!empty($data['content']) && empty($this->lines)) {
            $this->processTextLines($data);
        }
        // Set content based on stored lines
        if (empty($this->content) && !empty($this->lines)) {
            $this->content = $this->prepareStringText();
        }
        // Set content if it and lines are still empty 
        if (empty($this->content) && !empty($data['lines'])) {
            $this->lines = $data['lines'];
            $this->content = $this->prepareStringText();
        }
    }

    /**
     * Export object data
     * 
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'text_db_id'  => $this->text_db_id,
            'text_id'     => $this->text_id,
            'lines'       => !empty($this->lines) ? $this->lines : null,
            'line_start'  => $this->line_start,
            'word_start'  => $this->word_start,
            'line_last'   => $this->line_last,
            'word_last'   => $this->word_last,
            'content'     => isset($this->content) ? $this->content : null,
            'translation' => isset($this->translation) ? $this->translation : null,
        ];
    }

    /**
     * Set text type to either original or translation. 
     * 
     * @param $type
     */
    public function setType($type)
    {
        switch ($type) {
            case 'word':
                $this->type = $type;
                break;
            case 'transl-word':
                $this->type = $type;
                break;
        }
    }

    /**
     * Transform the form data from the textareas into Word, Line and Text objects.
     *
     * @param $data
     * @return bool
     */
    public function processTextLines($data)
    {
        $linesCollection = [];
        if (!isset($data['content'])) {
            return false;
        }

        // Split textarea content into different lines
        $linesArray = preg_split('/\r\n|[\r\n]/', $data['content']);
        $lineNr = $data['line_start'];
        for ($i = 0; $i < count($linesArray); $i++) {
            $orig_line = new OriginalLine();
            $lineData = [
                'line_nr' => $lineNr,
                'words' => []
            ];
            // Split line into separate words.
            $wordsArray = explode(' ', $linesArray[$i]);
            if (count($wordsArray) > 0) {
                /* Because it is possible that the start of the original text is not on the first word (or the
                 * first line), on the first iteration word nr is based on the value set in the form.
                 */
                if ($i == 0) {
                    $wordNr = $data['word_start'];
                }
                // Load word data into Word objects and collect Words for Line.
                foreach ($wordsArray as $word) {
                    if (!empty($word)) {
                        $orig_word = new OriginalWord();
                        $orig_word->exchangeArray([
                            'word_id' => null,
                            'text_id' => $data['text_id'],
                            'word_nr' => $wordNr,
                            'line_nr' => $lineNr,
                            'word' => $word
                        ]);
                        $lineData['words'][$wordNr] = $orig_word;
                        $wordNr++;
                    }
                }
                // Load line data and word objects into Line object.
                $orig_line->exchangeArray($lineData);
                $linesCollection[$lineNr] = $orig_line;
                $lineNr++;
            }
        }
        // Load data and line objects into this Text object.
        $this->word_last = $wordNr-1;
        $this->line_last = $lineNr-1;
        $this->lines = $linesCollection;
    }

    /**
     * Convert line objects into HTML.
     *
     * @param string $class             Identify text as original or translation
     * @return array $htmlLinesArray    Collection of HTML lines
     */
    public function prepareHtmlText($class = 'word')
    {
        $htmlLinesArray = [];
        if (count($this->lines) > 0 ) {
            // Split line into words and wrap them inside <span> elements.
            foreach ($this->lines as $line) {
                $htmlLine = '';
                foreach ($line->words as $word) {
                    $data_word_id = $word->word_id;
                    $data_word_nr = $word->word_nr;
                    $data_word = $word->word;

                    // prepare word for original text
                    if ($class == 'word') {
                        $htmlWordSpan = "<span class=$class data-word-id=$data_word_id data-word-nr=$data_word_nr>";
                        if (!empty($word->word_note)) {
                            if ($word->word_note == 'determin') {
                                $data_word = "<sup>" . $word->word[0] . "</sup>" . substr($word->word, 1);
                            }
                        }
                        $htmlWordSpan .= "$data_word</span> ";
                    }
                    // prepare word for translation text
                    if ($class == 'transl-word') {
                        $htmlWordSpan = "<span class=$class data-transl-word-id=$data_word_id data-word-nr=$data_word_nr>$data_word</span> ";
                    }

                    $htmlLine .= $htmlWordSpan;
                }
                $htmlLinesArray[$line->line_nr] = $htmlLine;
            }
        }
        return $htmlLinesArray;
    }

    /**
     * Convert lines to string text
     *
     * @param string  $class            Identify text as original or translation
     * @return string $linesString
     */
    public function prepareStringText($class = 'word')
    {
        $linesString = '';
        $stringsArray = [];
        /*
         * TODO: add option to insert special line nr (e.g. "Col.1 l.2")
         * Two loops maintained here to be used when this function is added.
         */
        if (count($this->lines) > 0 ) {
            foreach ($this->lines as $line) {
                $stringLine = '';
                foreach ($line->words as $word) {
                    if (is_string($word)) {
                        $stringLine .= $word . ' ';
                    }
                    if (is_object($word)) {
                        $stringLine .= $word->word . ' ';
                    }
                }
                $stringsArray[$line->line_nr] = trim($stringLine);
            }
        }
        foreach ($stringsArray as $lineId => $string) {
            $linesString .= $string . "\n";
        }

        return $linesString;
    }

    /**
     * Extract original text parts corresponding to the limits specified in the Translation Section object.
     *
     * @param   Text  $translSection
     * @return  mixed $translSection
     */
    public function getTranslSectOrig($translSection)
    {
        $lineStart = $translSection->line_start;
        $wordStart = $translSection->word_start;
        $lineLast = $translSection->line_last;
        $wordLast = $translSection->word_last;
        $translSection->lines = [];

        for ($i = $lineStart; $i < ($lineLast+1); $i++) {
            $line = clone $this->lines[$i];
            $origWords = $line->words;
            // Exclude words that are lower than the translation section's first word
            if ($line->line_nr == $lineStart) {
                foreach ($line->words as $word) {
                    $i2 = 0;
                    if ($word->word_nr < $wordStart) {
                        unset($line->words[$i2]);
                    }
                    $i2++;
                }
                $translSection->lines[$line->line_nr] = $line;

            // Add all words from lines between first and last line of the translation section
            } elseif ($line->line_nr != $lineLast) {
                $translSection->lines[$line->line_nr] = $line;

            // Exclude words that are higher than the translation section's last word
            } else {
                foreach ($line->words as $word) {
                    $i2 = 0;
                    if ($word->word_nr > $wordLast) {
                        unset($line->words[$i2]);
                    }
                    $i2++;
                }
                $translSection->lines[$line->line_nr] = $line;
            }
        }
        return $translSection;
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
        if ($this->inputFilter) {
            return $this->inputFilter;
        }

        $inputFilter = new InputFilter();

        $inputFilter->add([
            'name' => 'text_id',
            'required' => true,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
            ],
        ]);
        $inputFilter->add([
            'name' => 'line_start',
            'required' => true,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
            ],
        ]);
        $inputFilter->add([
            'name' => 'word_start',
            'required' => true,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
            ],
        ]);
        $inputFilter->add([
            'name' => 'content',
            'required' => false,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class]
            ]
        ]);
        $this->inputFilter = $inputFilter;
        return $this->inputFilter;

    }
}