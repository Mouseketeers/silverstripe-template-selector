<?php

namespace Mouseketeers\TemplateSelector;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;

class TemplateSelectorController extends Extension
{
    public function index(HTTPRequest $request)
    {
        $template = $this->getOwner()->data()->getSelectedTemplate();
        if ($template === null) {
            return [];
        }

        // Let the controller's normal viewer choose the outer page template.
        return ['Layout' => $this->getOwner()->renderWith([$template])];
    }
}
