<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/26/2017
 * Time: 15:42
 */
namespace Text\Processing\Model;

use Text\Processing\Model\Text;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Update;
use Zend\Db\TableGateway\TableGateway;
use Zend\Stdlib\ArrayUtils;
use Text\Processing\Model\OriginalWord;
use Text\Processing\Model\OriginalLine;

class TextsTable
{
    public $textTableGateway;
    public $origWordsTableGateway;
    public $translSectTableGateway;

    public function __construct(TableGateway $textTableGateway, $originalWordsTableGateway, $translationSectionTG)
    {
        $this->textTableGateway = $textTableGateway;
        $this->origWordsTableGateway = $originalWordsTableGateway;
        $this->translSectTableGateway = $translationSectionTG;
    }

    /**
     * Get Database ID and title of a single text or all texts.
     *
     * @param null $textName
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getText($textName = null)
    {
        if (is_string($textName)){
            $resultSet = $this->textTableGateway->select(['text_id' => $textName]);
        } elseif (is_integer($textName)) {
            $resultSet = $this->textTableGateway->select(['text_db_id' => $textName]);
        } else {
            $resultSet = $this->textTableGateway->select();
        }
        return $resultSet;
    }

    /**
     * Check that text exists and return
     *
     * @param  string $textName     Title name of text
     * @return mixed                FALSE if no text exists, otherwise the database id for the text
     */
    public function textExists($textName)
    {
        $resultSet = $this->textTableGateway->select(['text_id' => $textName]);
        // get text table data as array
        $textData = ArrayUtils::iteratorToArray($resultSet);
        if (empty($textData)) {
            return false;
        } else {
            return $textData[0]['text_db_id'];
        }
    }

    /**
     * Get original text (text) with associated direct word translations (cross) and textual references
     *
     * @param $textName
     *
     * @return array
     */
    public function getOriginalText($textName) {
        $resultSet = $this->textTableGateway->select(['text_id' => $textName]);
        // get text table data as array
        $textData = ArrayUtils::iteratorToArray($resultSet);
        if (empty($textData)) {
            return [
                'text' => null,
                'cross'=> null,
                'words'=> null
            ];
        }

        // Get words of the original text
        $select = new Select($this->textTableGateway->getTable());
        $select->join('orig_words', 'texts.text_db_id = orig_words.text_db_id', Select::SQL_STAR/*, Select::JOIN_RIGHT*/);
        $select->where(['text_id' => $textData[0]['text_id']]);
        $select->join('orig_transl_cross', 'orig_words.word_id = orig_transl_cross.orig_word_id', ['transl_word_id'], Select::JOIN_LEFT);
        $select->order(['orig_words.line_nr ASC', 'orig_words.word_nr ASC']);
        $origResultSet = $this->origWordsTableGateway->select($select);

        $lineCollection = [];
        $crossRefs = [
            'orig'   => [],
            'transl' => [],
        ];
        $wordIdArray = [];
        // Collect each word by line into an array
        foreach ($origResultSet as $wordInfo) {
            $lineCollection[$wordInfo->line_nr][] = $wordInfo;
            $wordIdArray[] = $wordInfo->word_id;
            if ($wordInfo->transl_word_id != null) {
                $crossRefs['orig'][$wordInfo->word_id]          = $wordInfo->transl_word_id;
                $crossRefs['transl'][$wordInfo->transl_word_id] = $wordInfo->word_id;
            }
        }
        // For each line load the words into a Line object
        foreach ($lineCollection as $line_Nr => $lineItem) {
            $line = new OriginalLine();
            $line->exchangeArray([
                'line_nr' => $line_Nr,
                'words' => $lineItem
            ]);
            $lineCollection[$line_Nr] = $line;
        }

        // Prepare data to load into Text object
        $textData[0]['lines'] = $lineCollection;
        $firstWord = current($lineCollection)->words[0];
        if (!empty($firstWord)) {
            $textData[0]['line_start'] = $firstWord->line_nr;
            $textData[0]['word_start'] = $firstWord->word_nr;
        }
        $text = new Text();
        $text->exchangeArray($textData[0]);

        return [
            'text' => $text,
            'cross'=> $crossRefs,
            'words'=> $wordIdArray
        ];
    }

    /**
     * Get all translation words belonging to a text
     *
     * @param  string                      $textName
     * @return \Text\Processing\Model\Text $translatedText
     */
    public function getTranslationText($textName)
    {
        $resultSet = $this->textTableGateway->select(['text_id' => $textName]);
        $textData = ArrayUtils::iteratorToArray($resultSet);
        
        $select = new Select($this->textTableGateway->getTable());
        $select->join('transl_words', 'texts.text_db_id = transl_words.text_db_id', Select::SQL_STAR/*, Select::JOIN_RIGHT*/);
        $select->where(['text_id' => $textData[0]['text_id']]);
        $origResultSet = $this->origWordsTableGateway->select($select);

        $lineCollection = [];
        foreach ($origResultSet as $wordInfo) {
            $lineCollection[$wordInfo->line_nr][] = $wordInfo;
        }
        foreach ($lineCollection as $line_Nr => $lineItem) {
            $line = new OriginalLine();
            $line->exchangeArray([
                'line_nr' => $line_Nr,
                'words' => $lineItem
            ]);
            $lineCollection[$line_Nr] = $line;
        }
        $textData[0]['lines'] = $lineCollection;
        $translatedText = new Text();
        $translatedText->exchangeArray($textData[0]);

        return $translatedText;


    }

    /**
     * Set table for query
     *
     * @param $textClass
     * @return void
     */
    private function setTable($textClass)
    {
        switch ($textClass) {
            case 'word':
                $this->table = 'orig_words';
                break;
            case 'transl-word':
                $this->table = 'transl_words';
                break;
        }
    }

    /**
     * Store the original or translation text in the database
     *
     * @param Text   $text
     * @param string $textClass
     * @return void
     */
    public function saveText($text, $textClass = 'word')
    {
        $wordIdArray = [];
        $this->table = null;
        $this->setTable($textClass);

        if ($this->textExists($text->text_id)) {
            // set database id of text
            $newTextId = isset($text->text_db_id) ? $text->text_db_id : $this->textExists($text->text_id);
            $text->text_db_id = $newTextId;

            // Get existing text to compare with new input
            if ($textClass == 'word') {
                $storedTextArray = $this->getOriginalText($text->text_id);
                $storedText = $storedTextArray['text'];
            }
            if ($textClass == 'transl-word') {
                $storedSections = $this->getTranslationSections($text->text_db_id);
                if (count($storedSections) > 0) {
                    $storedText = current($storedSections);
                }
            }
        } else {
            // Save new text and set the database id in the Text object
            $this->textTableGateway->insert(['text_id' => $text->text_id]);
            $newTextId = $this->textTableGateway->adapter->driver->getLastGeneratedValue();
            $text->text_db_id = $newTextId;
        }

        // Compare existing text with new input to prepare database actions
        if (isset($storedText)) {
            if (!empty($storedText->lines)) {
                // compare existing lines to submitted lines
                $sqlCollections = $this->compareTexts($text, $storedText);
                $wordIds = $this->processWordsDb($sqlCollections, $newTextId, $this->table);
                $wordIdArray = array_merge($wordIdArray, $wordIds);
            } else {
                $wordIds = $this->insertNewWords($text, $newTextId, $this->table);
                $wordIdArray = array_merge($wordIdArray, $wordIds);
            }
            $break = 'halt';
        } else {
            $wordIds = $this->insertNewWords($text, $newTextId, $this->table);
            $wordIdArray = array_merge($wordIdArray, $wordIds);
        }

        // Store translation section data
        if ($textClass == 'transl-word') {
            $transSectData = [
                'text_db_id' => $text->text_db_id,
                'line_start' => $text->line_start,
                'word_start' => $text->word_start,
                'line_last' => $text->line_last,
                'word_last' => $text->word_last,
                'translation' => serialize($wordIdArray)
            ];
            // Insert new or update existing translation section data.
            if (!isset($storedText)) {
                $this->translSectTableGateway->insert($transSectData);
            } else {
                $sectionFindResult = $this->translSectTableGateway->select([
                    'text_db_id' => $text->text_db_id,
                    'line_start' => $text->line_start,
                    'line_last' =>$text->line_last
                ]);
                if (count($sectionFindResult) > 0) {
                    $this->translSectTableGateway->update($transSectData, [
                        'text_db_id' => $text->text_db_id,
                        'line_start' => $text->line_start,
                        'line_last' => $text->line_last
                    ]);
                }
            }
        }
    }

    /**
     * Compare submitted text to stored text and return lists to create, update or delete words
     *
     * @param  Text  $formText   Text input from form
     * @param  Text  $storedText Pre-existing text
     * @return array             Array specifying word that would need to be created, updated or deleted.
     */
    public function compareTexts($formText, $storedText)
    {
        $createWords = [];
        $relatedWords = [];
        $updateWords = [];
        $deleteWords = [];

        $storedLines = $storedText->lines;
        $lastLineNr = 1;
        foreach ($formText->lines as $lineNr => $line) {
            if (count($line->words) > 0) {
                $lastLineNr = $line->line_nr;
            }
            // Check whether line is new or already exists
            if (key_exists($lineNr, $storedLines)) {
                $storedWords = $storedLines[$lineNr]->words;
                $storedWordsArray = [];
                $lastWordNr = 1;
                // Convert words of stored line to searchable associative array
                foreach ($storedWords as $storedWord) {
                    $storedWordsArray[$storedWord->word] = [
                        'line_nr' => $lineNr,
                        'word_id' => $storedWord->word_id,
                        'word_nr' => $storedWord->word_nr
                    ];
                    $lastWordNr = $storedWord->word_nr;
                }
                // Check if word is stored to create/update and to get existing word_id
                foreach ($line->words as $wordNr => $createWord) {
                    if (key_exists($createWord->word, $storedWordsArray)) {
                        // Check if word_nr corresponds to stored word_nr
                        if ($createWord->word_nr != $storedWordsArray[$createWord->word]['word_nr']) {
                            $createWord->word_id = $storedWordsArray[$createWord->word]['word_id'];
                            $updateWords[] = $createWord;
                            unset($storedWordsArray[$createWord->word]);
                        } else {
                            $createWord->word_id = $storedWordsArray[$createWord->word]['word_id'];
                            $relatedWords[] = $createWord;
                            unset($storedWordsArray[$createWord->word]);
                        }
                    } else {
                        $createWords[] = $createWord;
                    }
                }
                // remaining stored words are no longer in text and need to be deleted
                $deleteWords = array_merge($deleteWords, $storedWordsArray);
            } else {
                // For new line all words need to be created
                foreach ($line->words as $createWord) {
                    $createWords[] = $createWord;
                }
            }
        }
        $formText->line_last = $lastLineNr;
        return [
            'create' => $createWords,
            'related' => $relatedWords,
            'update' => $updateWords,
            'delete' => $deleteWords
        ];
    }

    /**
     * Add new words to database
     *
     * @param  Text   $text         Original or translation text object
     * @param  int    $newTextId    Database text ID
     * @param  string $table        Table name
     * @return array  $wordIdArray  Collection of word database ID's
     */
    public function insertNewWords($text, $newTextId, $table)
    {
        $wordIdArray = [];
        foreach ($text->lines as $line) {
            foreach ($line->words as $word) {
                $valueRow = [
                    'text_db_id' => $newTextId,
                    'line_nr' => $word->line_nr,
                    'word_nr' => $word->word_nr,
                    'word' => $word->word
                ];
                $this->origWordsTableGateway->insert($valueRow, $table);
                $newWordId = $this->origWordsTableGateway->adapter->driver->getLastGeneratedValue();
                $wordIdArray[] = $newWordId;
            }
        }
        return $wordIdArray;
    }

    /**
     * Add direct word translations to database
     *
     * @param $textDbId
     * @param $origWordId
     * @param $translWordId
     */
    public function addWordTranslation($textDbId, $origWordId, $translWordId)
    {
        $wordTranslation = [
            'text_db_id' => $textDbId,
            'orig_word_id' => $origWordId,
            'transl_word_id' => $translWordId
        ];
        $this->origWordsTableGateway->insert($wordTranslation, 'orig_transl_cross');
    }

    /**
     * Create, Update and Delete words in database.
     *
     * @param  array  $sqlCollections    Collection of arrays specifying which words need to be created, updated, deleted
     * @param  int    $newTextId         Database ID of text
     * @param  string $table             Table name
     * @return array  $wordIdArray       Collection of database IDs of words
     */
    public function processWordsDb($sqlCollections, $newTextId, $table)
    {
        $wordIdArray = [];
        // Create new words
        foreach ($sqlCollections['create'] as $createWord) {
            $valueRow = [
                'text_db_id' => $newTextId,
                'line_nr' => $createWord->line_nr,
                'word_nr' => $createWord->word_nr,
                'word' => $createWord->word
            ];
            $this->origWordsTableGateway->insert($valueRow, $table);
            $newWordId = $this->origWordsTableGateway->adapter->driver->getLastGeneratedValue();
            $wordIdArray[] = $newWordId;
        }

        // Update existing words
        foreach ($sqlCollections['update'] as $updateWord) {
            $valueRow = [
                'word_nr' => $updateWord->word_nr,
            ];
            $this->origWordsTableGateway->update($valueRow, ['word_id' => $updateWord->word_id], null, $table);
            $wordIdArray[] = $updateWord['word_id'];
        }

        // Delete words
        foreach ($sqlCollections['delete'] as $deleteWord) {
            $delWhere = ['word_id' => $deleteWord['word_id']];
            if ($table == 'orig_words') {
                $delWhere2 = ['orig_word_id' => $deleteWord['word_id']];
            }
            if ($table == 'transl_words') {
                $delWhere2 = ['transl_word_id' => $deleteWord['word_id']];
            }
            $delWhere3 = ['orig_word_id' => $deleteWord['word_id']];
            // remove the word and its cross index to translations and to word types
            $this->origWordsTableGateway->delete($delWhere, $table);
            $this->origWordsTableGateway->delete($delWhere2, 'orig_transl_cross');
            $this->origWordsTableGateway->delete($delWhere3, 'text_refs');
        }
        // Word IDs that stay the same need to be added to collection to be stored in translation section info
        foreach ($sqlCollections['related'] as $relatedWord) {
            $wordIdArray[] = $relatedWord->word_id;
        }
        return $wordIdArray;
    }

    /**
     * Get all the translation sections for an original text
     *
     * @param  string / Text  $originalText  original text object
     * @return array          $sections      array collection translation texts
     */
    public function getTranslationSections($originalText)
    {
        $sections = [];

        // Get database ID of text
        if ($originalText instanceof Text) {
            $textId = $originalText->text_db_id;
        }
        if (is_string($originalText)) {
            $textId = (integer)$originalText;
            $originalText = new Text();
            $originalTextDb = ArrayUtils::iteratorToArray($this->getText($textId));
            $originalTextDb = current($originalTextDb);
            $originalText->exchangeArray($originalTextDb);
        }

        // Get translation sections
        $tSResultRaw = $this->translSectTableGateway->select(['text_db_id' => $textId]);
        $tSData = ArrayUtils::iteratorToArray($tSResultRaw);
        foreach ($tSData as $sectionData) {
            $transSection = new Text();
            $sectionData['text_id'] = $originalText->text_id;
            $transSection->exchangeArray($sectionData);

            // Get word IDs from translation section and then get word data
            $wordIds = unserialize($sectionData['translation']);
            $wordsSelect = new Select('transl_words');
            $wordsSelect->where->in('word_id', $wordIds);
            $wordsSelect->order(['transl_words.line_nr ASC', 'transl_words.word_nr ASC']);
            $translWordsResult = $this->origWordsTableGateway->select($wordsSelect);
            $translWords = ArrayUtils::iteratorToArray($translWordsResult);

            // Prepare line data
            $lineData = [
                'line_nr' => null,
                'words' => [],
            ];
            $translLine = new OriginalLine();
            $linesCollection = [];

            // Load word data into word objects
            foreach ($translWords as $translWord) {
                $translWordObj = new OriginalWord();
                $translWordObj->exchangeArray($translWord);

                // Check whether word belongs to a new line
                $lineNr = $translWord['line_nr'];
                if (!empty($lineData['line_nr']) && $lineNr != $lineData['line_nr']) {
                    // load data into new Line object
                    $translLine->exchangeArray($lineData);
                    $linesCollection[$lineData['line_nr']] = $translLine;
                    $translLine = new OriginalLine();
                    $lineData = [
                        'line_nr' => $lineNr,
                        'words' => [],
                    ];
                }
                $lineData['line_nr'] = $lineNr;
                $lineData['words'][] = $translWordObj;
            }
            // load data into new Line object
            $translLine->exchangeArray($lineData);
            $linesCollection[$lineData['line_nr']] = $translLine;
            $sectionData['lines'] = $linesCollection;

            // Load translation section data into Text object
            $transSection->exchangeArray($sectionData);
            $sections[] = $transSection;
        }
        return $sections;
    }
}