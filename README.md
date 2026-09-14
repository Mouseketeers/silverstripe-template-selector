# Template selector

Adds a Layout dropdown to page settings. Custom layouts are discovered from
Silverstripe's effective theme paths, including the application and modules in
`$default`. Theme overrides follow the same precedence as normal rendering.
In the CMS, discovery uses the public theme stack retained by `HTMLEditorConfig`,
rather than the admin theme stack.

For a page class `Acme\Pages\ArticlePage`, automatic discovery includes:

- `templates/Acme/Pages/Layout/ArticlePage*.ss`
- `templates/Layout/ArticlePage*.ss`

The unsuffixed `ArticlePage.ss` is the default and is excluded. The application
folder's name and case come from Silverstripe's configuration. Only active
themes are searched.

For other naming conventions or namespaces, configure an explicit identifier to
label mapping on the page class. A nonempty mapping replaces automatic discovery:

```yaml
Acme\Pages\ArticlePage:
  template_selector_templates:
    'Shared/Layout/Editorial': 'Editorial'
    'Acme/Pages/Layout/ArticlePageGallery': 'Gallery'
```

Identifiers are relative to `templates/`, with no `.ss` extension. The same
identifier is stored in `Template` and passed to `renderWith()`. Only mapped
identifiers that resolve in the effective themes are offered.

Existing basename-only values (for example `ArticlePageGallery`) continue to
resolve against available candidates. Automatic discovery prefers the page's
namespace over the global `Layout` directory for legacy basename collisions.
For explicit mappings, the first matching basename wins. Existing selections
remain selected in the CMS; new selections use full identifiers. Unavailable
selections remain visible as unavailable and render the normal default layout.
The controller retains normal outer-template selection.

Run `dev/build` after upgrading to widen the `Template` column to `Varchar(255)`.
For themes selected dynamically, ensure the same public theme stack is available
before CMS initialization (or set `HTMLEditorConfig::setThemes()` accordingly).

## Integration checks

The regression script uses real Silverstripe dropdowns, data objects, theme
resolution and template rendering. It requires a case-sensitive fixture directory
and an isolated Silverstripe 5 installation; it does not connect to a database.
It checks loaded legacy values, canonical identifiers, public versus admin themes,
app-only discovery, theme precedence, mapped layouts, outer templates and fallback.

Copy an existing compatible CMS `vendor` directory into a **new disposable project**
(do not symlink it: Silverstripe requires framework files beneath `BASE_PATH`):

```sh
mkdir -p /tmp/template-selector-test-project
cp -R /path/to/cms/vendor /tmp/template-selector-test-project/vendor
TEMPLATE_SELECTOR_TEST_PROJECT=/tmp/template-selector-test-project \
  php tests/integration.php tests/bootstrap.php /case-sensitive-volume/selector-fixtures
```

Alternatively, supply an isolated application bootstrap as the first argument.
The script asserts filesystem case sensitivity before testing. No production
records are read or written; database save/publish and browser interaction should
also be checked in the consuming application.
