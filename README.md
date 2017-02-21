# text_processing
ZF Module to store original and translated texts and to create textual reference collections

The goal of this module is 
* 1	to store original texts and their translations (in *translation sections*), 
* 2	to create the basis for a dictionary, prosopography, atlas (etc.) by collecting additional information based on the text (i.e. *references*). 

##Introduction: texts and objects##
Text objects store original texts and translations. Original texts are separated into word objects, which are combined in line objects which are then assembled into text object. At the moment the greatest level of precision in documenting an original text is, therefore, at the word level. (A possible future update may increase this precision to the sign/letter level.) 

There are two connections between original text and translations. On the word level and on the passage level. The text passage level is the most essential and is stored as “translation sections”. A translation section maps a translation to the corresponding first and last words of the original text.

At the moment such a translation section should contain the whole text. The natural next step will be to create texts that consist of a series of translation sections. The next update to this module will add this feature. 

##Additional Information: *Text References*  
When we operate on the word level, a link can be added between words in the original text and its translation. Once such cross reference links exist, both the original and the translation will be highlighted when your mouse hovers over one or the other. You can add these links when viewing the texts (not in the edit view). 
In addition, you can add information regarding a word by specifying a word type and selecting an associated option. For example, a word can be identified as a personal name and then linked to a specific individual. Thus you would create a prosopography for your text collection. Another example is creating the basis for a historical geography by identifying words as a geographical name and linking them to a specific place. 

##Getting started##
* Download module and integrate with your ZF website. 
* Create Database tables (see the *text_tables.sql* file) and establish the connection. 
* For each type of textual reference you want to be able to add to your texts: 
  * add an item to the *word_types* table and 
  * create the associated table that contains the options for that word_type. The associated table should (at a minimum) contain the fields: 
    * *ref_id* and 
    * *option_name*.
* Add a new text and translation (on the *domain/text/edit* page) 
* Add textual references and direct (word-to-word) translations (on the *domain/text/show* page)

