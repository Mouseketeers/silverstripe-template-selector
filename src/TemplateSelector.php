<?php

namespace Mouseketeers\TemplateSelector;

use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

class TemplateSelector extends DataExtension
{
    private static $db = [
        'Template' => 'Varchar(255)',
    ];

    /** Optional render identifier => label map, configured on the page class. */
    private static $template_selector_templates = [];

    public function updateSettingsFields(FieldList $fields)
    {
        // LeftAndMain replaces SSViewer's themes with the CMS themes. The editor
        // retains the public theme stack, including themes set at runtime.
        $themes = HTMLEditorConfig::getThemes() ?: SSViewer::get_themes();
        $source = $this->getTemplateOptions($themes);
        $saved = (string) $this->owner->Template;
        if ($saved !== '' && !isset($source[$saved])) {
            $resolved = $this->getSelectedTemplate($themes);
            if ($resolved !== null) {
                // Keep existing basename-only values selected without a data migration.
                $source[$saved] = $source[$resolved];
                unset($source[$resolved]);
            } else {
                $source[$saved] = $saved . ' (unavailable)';
            }
        }
        if ($source) {
            $fields->addFieldToTab(
                'Root.Settings',
                DropdownField::create('Template', 'Layout', $source)->setEmptyString('Default'),
				'ParentTypeParentID'
            );
        }
    }

    /** Return theme-relative identifiers, exactly as passed to renderWith(). */
    public function getThemeFileList($themes = null)
    {
        return array_keys($this->getTemplateOptions($themes));
    }

    public function getTemplateOptions($themes = null)
    {
        $loader = ThemeResourceLoader::inst();
        $themes = $themes === null ? SSViewer::get_themes() : $themes;
        $configured = $this->owner->config()->get('template_selector_templates');
        if ($configured) {
            $options = [];
            foreach ($configured as $identifier => $label) {
                if ($loader->findTemplate($identifier, $themes)) {
                    $options[$identifier] = $label;
                }
            }
            return $options;
        }

        $class = str_replace('\\', '/', ltrim($this->owner->ClassName, '\\'));
        $shortName = ClassInfo::shortName($this->owner->ClassName);
        $namespace = strpos($class, '/') === false ? '' : dirname($class) . '/';
        $directories = array_unique([$namespace . 'Layout/', 'Layout/']);
        $options = [];
        foreach ($directories as $directory) {
            foreach ($loader->getThemePaths($themes) as $themePath) {
                $path = Director::baseFolder() . '/' . $themePath . '/templates/' . $directory;
                foreach (glob($path . $shortName . '*.ss') ?: [] as $file) {
                    $name = basename($file, '.ss');
                    // The unsuffixed class template is the default, not a variant.
                    if ($name === $shortName || !is_file($file)) {
                        continue;
                    }
                    $identifier = $directory . $name;
                    $options[$identifier] = $this->getFreindlyTemplateNamesList([$identifier])[0];
                }
            }
        }
        return $options;
    }

    /** Resolve old basename values only against the available candidates. */
    public function getSelectedTemplate($themes = null)
    {
        $saved = (string) $this->owner->Template;
        if ($saved === '') {
            return null;
        }
        $options = $this->getTemplateOptions($themes);
        if (isset($options[$saved])) {
            return $saved;
        }
        foreach ($options as $identifier => $label) {
            if ($saved === basename(str_replace('\\', '/', $identifier))) {
                return $identifier;
            }
        }
        return null;
    }

    // Retain the original public method name for backwards compatibility.
    public function getFreindlyTemplateNamesList($themeFileList = null)
    {
        $shortName = ClassInfo::shortName($this->owner->ClassName);
        $labels = [];
        foreach ($themeFileList ?: [] as $item) {
            $name = basename(str_replace('\\', '/', $item));
            if (strpos($name, $shortName) === 0) {
                $name = substr($name, strlen($shortName));
            }
            $label = trim(preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', str_replace(['-', '_'], ' ', $name)));
            $labels[] = $label === '' ? $item : ucwords($label);
        }
        return $labels;
    }
}
