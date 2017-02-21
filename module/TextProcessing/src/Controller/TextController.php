<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/26/2017
 * Time: 15:04
 */
namespace Text\Processing\Controller;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Stdlib\ArrayUtils;
use Zend\Form\Element;
use Text\Processing\Model\Text;
use Text\Processing\Form\TextForm;

class TextController extends AbstractActionController
{
    private $textsTable;
    private $placesTableGateway;
    private $referencesTableGateway;

    private $wordTypeTableMap;
    private $wordTypeTableMapByAbbr;


    public function __construct($textsTable, $placesTableGateway, $referencesTableGateway)
    {
        $this->textsTable = $textsTable;
        $this->placesTableGateway = $placesTableGateway;
        $this->referencesTableGateway = $referencesTableGateway;

        if (empty($this->wordTypeTableMap)) {
            $this->setWordTypeMaps();
        }
    }

    private function setWordTypeMaps()
    {
        $refTableSelect = new Select('word_types');
        $refTablesResult = $this->referencesTableGateway->select($refTableSelect);
        $refTablesColl = ArrayUtils::iteratorToArray($refTablesResult);
        foreach ($refTablesColl as $refTable) {
            $this->wordTypeTableMap[$refTable['word_type_id']] = [
                'table' => $refTable['type_option_table'],
                'select_abbr' => $refTable['word_type'],
                'select_full' => $refTable['select_name']
            ];
            $this->wordTypeTableMapByAbbr[$refTable['word_type']] = [
                'id' => $refTable['word_type_id'],
                'table' => $refTable['type_option_table'],
                'select_full' => $refTable['select_name']
            ];
        }
    }

    /**
     * Show overview of all texts
     *
     * @return array
     */
    public function indexAction()
    {
        $textsCollection = $this->textsTable->getText();
        $textsCollection = ArrayUtils::iteratorToArray($textsCollection);

        return [
            'texts' => $textsCollection
        ];

    }

    /**
     * Add a new original text with its translation or edit an existing original-translation text set.
     *
     * TODO: Create sets of original and translation form elements to build complicated texts consisting
     *      of multiple original-translation sections.
     *
     * @return array
     */
    public function editAction()
    {
        $routeId = $this->params()->fromRoute('id', 0);
        $request = $this->getRequest();

        // Prepare empty forms for original and translation text content.
        $origTextForm = new TextForm();
        $origTextForm->setAttribute('id', 'orig_text');
        $translTextForm = new TextForm();
        $translTextForm->setAttribute('id', 'transl_text');

        // Check if a new text is being created or (in the else clause: ) an existing one is being edited.
        if ($routeId === 0) {
            if (!$request->isPost()) {
                return [
                    'origTextForm' => $origTextForm,
                    'translTextForm' => $translTextForm,
                ];
            }

            // Prepare the data submitted through the form to load into Text objects.
            $textData = $request->getPost();
            $data = ArrayUtils::iteratorToArray($textData);
            // check whether part of this text is already in db
            $existingTextId = $this->textsTable->textExists($data['text_id']);
            if ($existingTextId) {
                $data['text_db_id'] = $existingTextId;
            }
            // Copy and alter data to create the translation data
            $translData = $data;
            $translData['content'] = $data['transl-content'];
            unset($translData['transl-content']);

            // Load the data into Text objects.
            $translText = new Text();
            $translText->exchangeArray($translData);
            $translText->setType('transl-word');
            $translData = $translText->getArrayCopy();

            $origText = new Text();
            $origText->exchangeArray($data);
            $origText->setType('word');

        } else {
            // Editing an existing text.
            $textInfo = $this->textsTable->getOriginalText($routeId);
            $origText = $textInfo['text'];
            $textData = $origText->getArrayCopy();

            // Retrieve the translation sections and load these into a form.
            // TODO: Divide original text into subsections and match them up with the translation sections.
            $translSections = $this->textsTable->getTranslationSections($origText);
            foreach ($translSections as $translSection) {
                $translData = $translSection->getArrayCopy();
                $translTextForm->bind($translSection);
                $translTextForm->setData($translData);
                $translTextForm->setInputFilter($translSection->getInputFilter());
            }
        }

        // Bind text objects to forms and check for validity.
        $origTextForm->bind($origText);
        $origTextForm->setData($textData);
        $origTextForm->setInputFilter($origText->getInputFilter());
        if (! $request->isPost()) {
            return [
                'origTextForm' => $origTextForm,
                'translTextForm' => $translTextForm
            ];
        }
        if (! $origTextForm->isValid()) {
            return [
                'origTextForm' => $origTextForm,
                'translTextForm' => $translTextForm
            ];
        }
        $translTextForm->bind($translText);
        $translTextForm->setData($translData);
        $translTextForm->setInputFilter($translText->getInputFilter());
        if (! $translTextForm->isValid()) {
            return [
                'origTextForm' => $origTextForm,
                'translTextForm' => $translTextForm
            ];
        }

        // Save the new or edited original and translation texts.
        $this->textsTable->saveText($origText);
        $this->textsTable->saveText($translText, 'transl-word');

        // Continue to page displaying the new/edited text.
        $this->redirect()->toRoute('text', ['action' => 'show', 'id' => $origText->text_id]);

    }

    /**
     * Display original texts along with their translation and additional
     * information as text references as well as word-word translations. 
     *
     * @return array
     */
    public function showAction()
    {
        $routeId = $this->params()->fromRoute('id', 0);

        // Get the original text, direct word translation ($crossRefs) and textual references ($wordRefsColl) 
        $original = $this->textsTable->getOriginalText($routeId);
        $originalText = $original['text'];
        $crossRefs = $original['cross'];
        $wordIds = $original['words'];
        if (count($wordIds) > 0) {
            $wordRefsColl = $this->getTextReferences($wordIds);
        } else {
            $wordRefsColl = [];
        }

        // Get the translation sections and divide the original text in matching sections. 
        $translSections = $this->textsTable->getTranslationSections($originalText);
        $textSections = [];
        foreach ($translSections as $translSection){
            $translArray = $translSection->prepareHtmlText('transl-word');
            $origTextTranslSection = $originalText->getTranslSectOrig($translSection);
            $origTextArray = $origTextTranslSection->prepareHtmlText();
            // Assemble matched sections of original and translated text. 
            $textSections[] = [
                'orig' => $origTextArray,
                'trans' => $translArray
            ];
        }

        return [
            'crossrefs' => $crossRefs,
            'refInfos' => $wordRefsColl,
            'textSections' => $textSections,
        ];
    }


    public function getTextReferences($wordIdCollection, $wordType=null)
    {
        // Make sure that the tables containing the options for the word types are mapped.
        if (empty($this->wordTypeTableMap)) {
            $this->setWordTypeMaps();
        }

        // Select the word type references
        $refSelect = new Select('text_refs');
        $refSelect->where->in('orig_word_id', $wordIdCollection);
        if (!empty($wordType)) {
            $refSelect->where(['word_type' => $wordType]);
        }
        $referencesResult = $this->referencesTableGateway->select($refSelect);
        $refCollection = ArrayUtils::iteratorToArray($referencesResult);
        $refRequestColl = [];
        $wordRefsColl = [];
        foreach ($refCollection as $refItem) {
            $refRequestColl[$refItem['word_type']][$refItem['orig_word_id']] = $refItem['word_type_ref'];
            $wordRefsColl[(string)$refItem['word_type']][(string)$refItem['word_type_ref']]['word_ids'][] = $refItem['orig_word_id'];
        }
        foreach ($refRequestColl as $refRequestId => $refRequestArray) {
            $wordIds = array_keys($refRequestArray);
            $table = $this->wordTypeTableMap[$refRequestId]['table'];
            $refSelect = new Select($table);
            $refSelect->where->in('ref_id', $refRequestArray);
            $rawRefOptions = $this->placesTableGateway->select($refSelect);
            $refOptions = ArrayUtils::iteratorToArray($rawRefOptions);
            foreach ($refOptions as $refOption) {
                $wordRefsColl[(string)$refRequestId][$refOption['ref_id']]['ref_info'] = $refOption['option_name'];
            }
        }

        return $wordRefsColl;
    }

    /**
     * Collect the select options for the modal to add textual references. 
     * 
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function ajaxAction()
    {
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine("Content-type: application/json");

        // get parameters 
        $request = $this->getRequest();
        if ($request->isPost()) {
            $rawParams = $request->getPost();
            $params = ArrayUtils::iteratorToArray($rawParams);

        }
        if (isset($params['option_type'])) {
            $optionType = $params['option_type'];
        }

        $options = [];
        // get the different word_types 
        if ($optionType == 'word_type') {
            foreach ($this->wordTypeTableMap as $wordType) {
                $options[] = [$wordType['select_abbr'], $wordType['select_full']];
            }
        } /**
         * If you would like to assemble a more complicated list of selection options for a specific word-type, then 
         * you can add the code in a elseif{}-section here. E.g.:
         * 
         * elseif ($optionType == 'specific word-type') {
         *      foreach (getSpecificWordTypeOptions('complicating parameters') as $newOption) {
         *          $options[] = [$newOption['abbreviation'], $newOption['full_name']]
         *      }
        }*/ else {
            // get the options for the selected word type 
            $typeOptionSelect = new Select($this->wordTypeTableMapByAbbr[$optionType]['table']);
            $typeOptionResult = $this->referencesTableGateway->select($typeOptionSelect);
            $typeOptionsColl = ArrayUtils::iteratorToArray($typeOptionResult);
            foreach ($typeOptionsColl as $typeOption) {
                $options[] = [$typeOption['ref_id'], $typeOption['option_name']];
            }
        }

        $jsonOptions = json_encode($options);

        $response->setContent($jsonOptions);
        return $response;
    }

    /**
     * Process the selected word type option and save as a textual reference. 
     * 
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function addOptionAjaxAction()
    {
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine("Content-type: application/json");

        // Prepare data to create new reference 
        $request = $this->getRequest();
        if ($request->isPost()) {
            $rawParams = $request->getPost();
            $params = ArrayUtils::iteratorToArray($rawParams);

        }
        $textRefArray = [
            'orig_word_id' => $params['word_id'],
            'word_type' => $this->wordTypeTableMapByAbbr[$params['word_type']]['id'],
            'word_type_ref' => $params['type_option']
        ];
        // check if similar (on orig_word_id and word_type) reference already exists
        $checkResult = $this->referencesTableGateway->select([
            'orig_word_id' => $textRefArray['orig_word_id'],
            'word_type' => $textRefArray['word_type']
        ]);
        if (count($checkResult) < 1) {
            $this->referencesTableGateway->insert($textRefArray);
            $newInsertId = $this->referencesTableGateway->getAdapter()->getDriver()->getConnection()->getLastGeneratedValue();

        }
        $response->setContent(json_encode('successfully added word type info'));
        return $response;
    }

    public function addTranslationAjaxAction()
    {
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine("Content-type: application/json");

        // Prepare data to create new cross reference between original word and translation word. 
        $request = $this->getRequest();
        if ($request->isPost()) {
            $rawParams = $request->getPost();
            $params = ArrayUtils::iteratorToArray($rawParams);
            $textId = $this->textsTable->textExists($params['text_id']);
            $this->textsTable->addWordTranslation($textId, $params['orig_word_id'], $params['transl_word_id']);
        }
        $response->setContent(json_encode('successfully added translation link between two words'));
        return $response;
    }
}