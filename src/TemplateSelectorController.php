<?php

namespace Mouseketeers\TemplateSelector;

use SilverStripe\Core\Extension;
use SilverStripe\Control\HTTPRequest;


class TemplateSelectorController extends Extension 
{
	public function index(HTTPRequest $request) {
		if($template = $this->owner->Template) {
	        return $this->owner->customise([
	            'Layout' => $this->owner->renderWith(['\App\Web\PageTypes\Layout\\'.$this->owner->Template]),
	        ])->renderWith(['Page']);		
		}
		else {
			return [];
		}		
		// if($template = $this->owner->Template) {
		// 	return $this->owner->renderWith(['PageSubpageGallery', 'Page']);	
		// }
		// else {
		// 	return array();
		// }
	}	
}