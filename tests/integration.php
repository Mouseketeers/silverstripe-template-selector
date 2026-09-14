<?php

// Run against an isolated Silverstripe 5 bootstrap (no production database).
// php tests/integration.php /path/to/bootstrap.php /case-sensitive/fixture-directory
namespace Mouseketeers\TemplateSelector\Tests;

use Mouseketeers\TemplateSelector\TemplateSelector;
use Mouseketeers\TemplateSelector\TemplateSelectorController;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeList;
use SilverStripe\View\ThemeResourceLoader;

if (empty($argv[1]) || empty($argv[2])) {
    exit("Usage: php tests/integration.php bootstrap.php case-sensitive-fixture-directory\n");
}
require $argv[1];
require_once dirname(__DIR__) . '/src/TemplateSelector.php';
require_once dirname(__DIR__) . '/src/TemplateSelectorController.php';

class LandingPage extends SiteTree
{
    private static $table_name = 'TemplateSelectorTestPage';
}

function check($condition, $message)
{
    if (!$condition) {
        throw new \RuntimeException($message);
    }
    echo "PASS: $message\n";
}

$base = rtrim($argv[2], '/');
if (!is_dir($base)) {
    mkdir($base, 0777, true);
}
file_put_contents($base . '/case-probe', 'test');
check(!file_exists($base . '/CASE-PROBE'), 'fixtures use a case-sensitive filesystem');
$namespace = 'Mouseketeers/TemplateSelector/Tests/';
$id = $namespace . 'Layout/LandingPageGallery';
$files = [
    'app/templates/' . $id . '.ss' => 'APP GALLERY',
    'themes/site/templates/' . $id . '.ss' => 'THEME GALLERY',
    'themes/site/templates/' . $namespace . 'Layout/LandingPageX.ss' => 'ONE LETTER',
    'themes/site/templates/Layout/LandingPageWide.ss' => 'GLOBAL WIDE',
    'themes/site/templates/' . $namespace . 'Layout/LandingPage.ss' => 'DEFAULT LAYOUT',
    'themes/site/templates/' . $namespace . 'LandingPage.ss' => 'CUSTOM SHELL [$Layout]',
    'themes/site/templates/Shared/Layout/Promo.ss' => 'PROMO',
    'themes/inactive/templates/' . $namespace . 'Layout/LandingPageHidden.ss' => 'HIDDEN',
    'themes/admin/templates/' . $namespace . 'Layout/LandingPageAdmin.ss' => 'ADMIN',
];
foreach ($files as $relative => $content) {
    $file = $base . '/' . $relative;
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0777, true);
    }
    file_put_contents($file, $content);
}
Config::modify()->set(Director::class, 'alternate_base_folder', $base);
Config::modify()->set(SSViewer::class, 'theme_enabled', true);
$loader = new ThemeResourceLoader($base);
$loader->addSet('$default', new class implements ThemeList {
    public function getThemes()
    {
        return ['/app'];
    }
});
ThemeResourceLoader::set_instance($loader);
$themes = ['site', '$default'];
SSViewer::set_themes($themes);
LandingPage::add_extension(TemplateSelector::class);
$page = new LandingPage();
$page->Template = 'LandingPageGallery'; // Existing persisted basename, loaded into a record.
$options = $page->getTemplateOptions();
check(count($options) === 3, 'active themes and app are discovered once; defaults and inactive themes excluded');
check(isset($options[$id]), 'namespace derives from the actual page class');
check($options[$id] === 'Gallery', 'CMS label strips the short class name');
check($page->getSelectedTemplate() === $id, 'legacy saved value resolves to its render identifier');
check($loader->findTemplate($id) === $base . '/themes/site/templates/' . $id . '.ss', 'theme override wins over app template');

HTMLEditorConfig::setThemes($themes);
SSViewer::set_themes(['admin']);
$fields = FieldList::create(TabSet::create('Root', Tab::create('Settings')));
$page->updateSettingsFields($fields);
$source = $fields->dataFieldByName('Template')->getSource();
check(isset($source['LandingPageGallery']), 'CMS dropdown preserves an existing saved custom layout');
check(count($source) === 3, 'CMS dropdown uses public themes instead of admin themes');
SSViewer::set_themes($themes);

// No asset publication is needed for these text-only template fixtures.
\SilverStripe\View\Requirements::set_backend(new \SilverStripe\View\Requirements_Backend());
$controller = new ContentController($page);
$extension = new TemplateSelectorController();
$extension->setOwner($controller);
$result = $extension->index(new HTTPRequest('GET', '/'));
check(trim((string) $result['Layout']) === 'THEME GALLERY', 'saved custom layout renders the theme override');
$html = $controller->getViewer('index')->process($controller->customise($result));
check(trim((string) $html) === 'CUSTOM SHELL [THEME GALLERY]', 'normal page-specific outer template is preserved');
$page->Template = $id;
check($page->getSelectedTemplate() === $id, 'new dropdown value is the exact render identifier');

SSViewer::set_themes(['$default']);
check($page->getSelectedTemplate() === $id, 'lowercase app templates remain discoverable without a theme');
check(trim((string) $extension->index(new HTTPRequest('GET', '/'))['Layout']) === 'APP GALLERY', 'app-only custom layout renders on a case-sensitive filesystem');
SSViewer::set_themes($themes);

Config::modify()->set(LandingPage::class, 'template_selector_templates', ['Shared/Layout/Promo' => 'Promotion']);
$page->Template = 'Shared/Layout/Promo';
check($page->getTemplateOptions() === ['Shared/Layout/Promo' => 'Promotion'], 'explicit mapping supports unrelated namespaces and filenames');
check(trim((string) $extension->index(new HTTPRequest('GET', '/'))['Layout']) === 'PROMO', 'mapped identifier is rendered unchanged');
$page->Template = '../../outside';
check($extension->index(new HTTPRequest('GET', '/')) === [], 'unknown saved values fall back to normal rendering');
$fields = FieldList::create(TabSet::create('Root', Tab::create('Settings')));
$page->updateSettingsFields($fields);
check(isset($fields->dataFieldByName('Template')->getSource()['../../outside']), 'unavailable selection remains visible in the CMS');
$page->Template = '';
check($extension->index(new HTTPRequest('GET', '/')) === [], 'empty selection uses the default layout');
check($page->getFreindlyTemplateNamesList() === [], 'empty friendly-name list is safe');
echo "All integration checks passed.\n";
