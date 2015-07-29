<?php
class ContentBlocksModule extends DataExtension {

	private static $create_block_tab = true;
	private static $contentarea_rows = 12;

	private static $db = array();

	private static $has_one = array();
	
	private static $many_many = array(
		'Blocks' => 'Block'
	);
	
	private static $many_many_extraFields = array(
        'Blocks' => array(
			'SortOrder'=>'Int'
		)
    );
	
	public function updateCMSFields(FieldList $fields) {

		// Relation handler for Blocks		
		$SConfig = GridFieldConfig_RelationEditor::create(25);
		$SConfig->addComponent(new GridFieldOrderableRows('SortOrder'));
		$SConfig->addComponent(new GridFieldDeleteAction());
		
		// If the copy button module is installed, add copy as option
		if (class_exists('GridFieldCopyButton')) {
			$SConfig->addComponent(new GridFieldCopyButton(), 'GridFieldDeleteAction');
		}

		$gridField = new GridField("Blocks", "Content blocks", $this->owner->Blocks(), $SConfig);
		
		$classes = array_values(ClassInfo::subclassesFor($gridField->getModelClass()));
		
		if (count($classes) > 1 && class_exists('GridFieldAddNewMultiClass')) {
			$SConfig->removeComponentsByType('GridFieldAddNewButton');
			$SConfig->addComponent(new GridFieldAddNewMultiClass());
		}
		
		if (self::$create_block_tab) {
			$fields->addFieldToTab("Root.Blocks", $gridField);
		} else {
			// Downsize the content field
			$fields->removeByName('Content');
			$fields->addFieldToTab('Root.Main', HTMLEditorField::create('Content')->setRows(self::$contentarea_rows), 'Metadata');
			
			$fields->addFieldToTab("Root.Main", $gridField, 'Metadata');
		}
		
		return $fields;
	}

	public function ActiveBlocks() {
		return $this->owner->Blocks()->filter(array('Active' => '1'))->sort('SortOrder');
	}

	protected function fetchBlockTrans($name, $lang) {
		$block = Block::get()->filter(['Name' => $name])->first();

		if ($block) {
			return BlockTranslation::get()
				->filter([
					'BlockID' => $block->ID,
					'Language' => $lang
				])->first();
		}

		return null;
	}
	
	public function OneBlock($name) {
		$lang = i18n::get_locale();

		$blockTrans = $this->fetchBlockTrans($name, $lang);

		//Fallback to english if there is no translation for language
		if (count($blockTrans) === 0) {
			$blockTrans = $this->fetchBlockTrans($name, 'en_US');
		}

		return $blockTrans;
	}
	
	//TO-DO
	public function contentcontrollerInit($controller) {
		// Requirements::themedCSS('block');
	}

	/**
	* Simple support for Translatable, when a page is translated, copy all content blocks and relate to translated page
	* TODO: This is not working as intended, for some reason an image is added to the duplicated block
	* All blocks are added to translated page - or something else ...
	*/
	public function onTranslatableCreate() {
		
		$translatedPage = $this->owner;
		// Getting the parent translation
		//$originalPage = $translatedPage->getTranslation('en_US');
		$originalPage = $this->owner->getTranslation($this->owner->default_locale());
		foreach($originalPage->Blocks() as $originalBlock) {
			$block = $originalBlock->duplicate(true);
			$translatedPage->Blocks()->add($block);
		}
	}
}