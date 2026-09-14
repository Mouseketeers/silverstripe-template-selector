<?php

// Point this at a disposable project containing a copy of a Silverstripe 5
// vendor directory. Do not use a production application or database.
$project = getenv('TEMPLATE_SELECTOR_TEST_PROJECT');
if (!$project || !is_dir($project . '/vendor')) {
    throw new RuntimeException('Set TEMPLATE_SELECTOR_TEST_PROJECT to an isolated project with vendor dependencies.');
}
putenv('SS_IGNORE_DOT_ENV=1');
putenv('SS_TEMP_PATH=' . $project . '/cache');
define('BASE_PATH', realpath($project));
require BASE_PATH . '/vendor/autoload.php';

// Required by silverstripe/errorpage in standard CMS installations.
class Page extends \SilverStripe\CMS\Model\SiteTree
{
}
class PageController extends \SilverStripe\CMS\Controllers\ContentController
{
}

$kernel = new \SilverStripe\Core\CoreKernel(BASE_PATH);
$kernel->setBootDatabase(false);
$kernel->boot(true);
