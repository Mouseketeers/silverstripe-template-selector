<?php

namespace Mouseketeers\TemplateSelector;

use SilverStripe\ORM\DataExtension;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\View\SSViewer;
use SilverStripe\Core\ClassInfo;


class TemplateSelector extends DataExtension {
	private static $db = array(
		'Template' => 'Varchar'
	);
	public function updateSettingsFields(FieldList $fields) {
		$themeList = self::getThemeFileList();
		if($themeList && count($themeList) > 0) {
			$freindlyList = self::getFreindlyTemplateNamesList($themeList);
			$fields->addFieldToTab(
				'Root.Settings',
				DropdownField::create(
					$name = 'Template',
					$title = 'Layout',
					$source = array_combine($themeList, $freindlyList)
				)->setEmptyString('Default'),
				'ParentType'
			);
		}
	}
	public function getThemeFileList() {
		// $themeFileDir = null;

		$fileNames = [];
		$projectFileDir = Director::baseFolder() . '/App/templates/App/Web/PageTypes//Layout/';
		// $themeDir = THEMES_DIR;
		// if($baseDir && $themeDir) {

		// 	$themeFileDir = $baseDir.'/'.$themeDir.'/templates/Layout/';
		// 	$projectFileDir = $baseDir . '/App/templates/App/Web/PageTypes//Layout/';
		// } else {
		// 	return null;
		// }
		// $themeFileNameList = glob($themeFileDir.ClassInfo::shortName($this->owner->ClassName).'[a-zA-Z][a-zA-Z]*.ss');
		
		// $projectFileNameList = glob($projectFileDir.ClassInfo::shortName($this->owner->ClassName).'[a-zA-Z][a-zA-Z]*.ss');

		// $fileNames = array();
		// $fileNameList = array_merge($themeFileNameList, $projectFileNameList);

		$templateFileList = glob($projectFileDir.ClassInfo::shortName($this->owner->ClassName).'[a-zA-Z][a-zA-Z]*.ss');
		foreach($templateFileList as $fileName) {
			$fileNames[] = preg_replace('/\\.[^.\\s]{2,3}$/', '',basename($fileName));
		}
		if(count($fileNames) > 0){
			return $fileNames;
		}
	}
	public function getFreindlyTemplateNamesList($themeFileList = null) {
		$freindlyThemeFileList = array();
		foreach($themeFileList as $item) {
			$freindlyThemeFileList[] = ucwords(
				trim(
					strtolower(
						preg_replace('/-?([A-Z])/', ' $1', 
							str_replace($this->owner->ClassName, '', $item)
						)
					)
				)
			);
		}
		return $freindlyThemeFileList;
	}
}