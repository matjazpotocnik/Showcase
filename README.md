# ProcessShowcase

A boilerplate / reference module for building **backend administrative
interfaces** in ProcessWire. It does nothing useful on its own — its sole
purpose is to demonstrate, in one place, the patterns you will reach for
when writing your own admin Process modules.

## Install

Requirements:

- ProcessWire 3.0.270 or newer.
- PHP 8.0.0 or newer.

1. Copy the `ProcessShowcase/` directory to `site/modules/`.
2. In the ProcessWire admin go to **Modules > Refresh**.
3. Click **Install** next to "Showcase".
4. Navigate to **Setup > Showcase**.

The module installs a single admin page at `setup/showcase/` and registers
twelve child subpages via `useNavJSON` + `nav`. It declares one PHP class,
owns focused field/template/page fixtures for native Repeater and media tests.

![Showcase dashboard screenshot](screenshot-processshowcase-module.png)

## What it demonstrates

### File: `ProcessShowcase.module.php`

| Pattern                                            | Implementation                                                                   |
| -------------------------------------------------- | -------------------------------------------------------------------------------- |
| **Module configuration page**                      | `getModuleConfigInputfields()`                                                   |
| **Admin landing page**                             | `___execute()`                                                                   |
| **Custom subpages with menu entries**              | `nav` + `useNavJSON` in `getModuleInfo()` and matching `___executeXxx()` methods |
| **Dashboard layout**                               | Stat cards + quick-action card grid in `___execute()`                            |
| **Basic numeric, date and utility Inputfields**    | `___executeForms()`                                                              |
| **Selection controls**                             | `___executeSelectionControls()`                                                  |
| **Native file and image management**               | `___executeFilesImages()`                                                        |
| **Text Inputfields and rich-text editors**         | `___executeText()`                                                               |
| **Checkboxes, radios and toggles**                 | `___executeChoices()`                                                            |
| **Buttons, button-group, submit + dropdown menu**  | `___executeActions()`                                                            |
| **Dynamic repeater (add / remove / drag-reorder)** | `___executeRepeater()`                                                           |
| **Data tables (`MarkupAdminDataTable` and UIkit)** | `___executeTables()`                                                             |
| **Description / item / nav lists**                 | `___executeLists()`                                                              |
| **Card-grid layouts**                              | `___executeCards()`                                                              |
| **PageList state and action contracts**             | `___executePagelist()`                                                           |
| **Notices, alerts, confirms and iframe modals**     | `___executeFeedback()`                                                           |

### Subpages

| URL                        | Method                 | What you'll see                                                                                                                                                                                                                                                                                                                         |
| -------------------------- | ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `setup/showcase/`          | `___execute()`         | Stat cards, quick-action tiles, live config summary                                                                                                                                                                                                                                                                                     |
| `setup/showcase/forms/`    | `___executeForms()`    | Integer, float, date-only, time-only and date-time controls; hidden and markup utilities |
| `setup/showcase/selection-controls/` | `___executeSelectionControls()` | Select, SelectMultiple, AsmSelect, PageListSelect, PageListSelectMultiple, Icon picker and Selector builder controls |
| `setup/showcase/page-references/` | `___executePageReferences()` | Every core `InputfieldPage` delegate: Select, Radios, SelectMultiple, Checkboxes, AsmSelect, PageListSelect, PageListSelectMultiple, PageAutocomplete and TextTags, using real existing pages |
| `setup/showcase/files-images/` | `___executeFilesImages()` | Genuine `InputfieldFile` and `InputfieldImage` fields with seeded files and images for testing their native controls |
| `setup/showcase/text/`     | `___executeText()`     | Text-family Inputfields, language tabs, CKEditor, TinyMCE and optional InputfieldJson, with a textarea fallback when neither rich-text editor is installed |
| `setup/showcase/choices/`  | `___executeChoices()`  | `InputfieldCheckbox`, `InputfieldCheckboxes`, `InputfieldRadios`, and `InputfieldToggle`, with documented setup and processed scalar/array value contracts |
| `setup/showcase/actions/`  | `___executeActions()`  | `InputfieldSubmit` and `InputfieldButton` hierarchy, an enhanced submit action menu, `InputfieldToggle` segmented control, grouped navigation links, and a standalone form submit |
| `setup/showcase/repeater/` | `___executeRepeater()` | Dynamic repeater: add new rows, remove individual rows, drag-to-reorder via jQuery UI `.sortable()`, with the row count capped by the module's `maxItems` config                                                                                                                                                                        |
| `setup/showcase/tables/`   | `___executeTables()`   | `MarkupAdminDataTable` with sortable columns, status labels, per-row action button group + a striped UIkit table                                                                                                                                                                                                                        |
| `setup/showcase/lists/`    | `___executeLists()`    | `uk-description-list`, `uk-list` with badges, `uk-nav`                                                                                                                                                                                                                                                                                  |
| `setup/showcase/cards/`    | `___executeCards()`    | Responsive `uk-card` grid with header / body / footer + action buttons                                                                                                                                                                                                                                                                  |
| `setup/showcase/pagelist/` | `___executePagelist()` | Representative open, hidden, unpublished, locked, loading and placeholder PageList states |
| `setup/showcase/feedback/` | `___executeFeedback()` | ProcessWire message, warning and error notices; alert and confirm helpers; and an iframe modal                                                                                                                                                                                                                                           |

The Page references subpage does not create fixture pages. Each `InputfieldPage`
uses a small selector over existing pages, and remains non-AJAX-collapsed because
an AJAX-collapsed `InputfieldPage` intentionally defers delegate configuration.

## Key APIs referenced

- **`Process`** base class — `___execute()`, `___executeXxx()`, `$this->headline()`, `$this->breadcrumb()`, `$this->browserTitle()`
- **`ConfigurableModule`** — `getModuleConfigInputfields(InputfieldWrapper $inputfields)`
- **`InputfieldForm`** — `processInput()`, `getValueByName()`, magic `$form->Inputfield…` shortcuts
- **`InputfieldSubmit`** — `addActionValue($value, $label, $icon)` for the dropdown action menu, `showInHeader(true)` for header-pinned submit
- **`InputfieldButton`** — `setSecondary()`, `href`, `icon`
- **`InputfieldText` and specializations** — length/count behavior plus Email, URL, Password, PageName, PageTitle and Name validation/sanitization contracts
- **`InputfieldTextarea`** — multi-line text and the base contract used by rich-text editors
- **`InputfieldPage`** — delegates page selection to Select, Radios, SelectMultiple, Checkboxes, AsmSelect, PageListSelect, PageListSelectMultiple, PageAutocomplete or TextTags
- **`InputfieldCKEditor` and `InputfieldTinyMCE`** — rich-text rendering and standalone form processing
- **`InputfieldCheckbox`** — `checked()` controls state independently from `checkedValue` and `uncheckedValue`
- **`InputfieldCheckboxes`** — multiple selected options are processed as an array value
- **`InputfieldRadios`** — one selected option is processed as a scalar string value
- **`InputfieldToggle`** — named constants and label types define its binary or optional third state
- **`InputfieldFile` and `InputfieldImage`** — genuine Page-bound AJAX uploads, metadata, sorting, deletion and image editing
- **`ProcessShowcase::getTestPage()`** — supplies the module-owned disposable saved Page required by native file and image fields
- **`MarkupAdminDataTable`** — `setSortable()`, `setEncodeEntities(false)`, `headerRow()`, `row()`
- **`InputfieldMarkup`** + raw HTML rows + jQuery UI `.sortable()` — the recipe used for the dynamic repeater
- **Advanced Inputfields** — AsmSelect and LanguageTabs
- **ProcessWire UI contracts** — PageList states/actions, file-list states, notices and modal helpers
- **`Modules::getConfig` / `Modules::saveConfig`** — persisting module settings

## File and image lifecycle

The Files & images subpage intentionally uses real ProcessWire persistence.
Native file/image AJAX handling, image variations, crop and focus tools require
a saved Page with genuine FieldtypeFile and FieldtypeImage fields; standalone
Inputfields cannot demonstrate the complete behavior.

- ProcessShowcase creates and owns the hidden `showcase-media-fixture`
  page and template. Its `getTestPage()` method mirrors the essential saved-page
  behavior previously supplied by WireTests without requiring that module.
- It does not reuse the core `wire_test_file` and `wire_test_image` fields.
  Their automated tests deliberately delete and replace all values, so sharing
  them would erase showcase uploads and make the tests interfere with this page.
- When each media field is first created, the module copies its default examples
  from `assets/demo/` into the page's normal files directory. It does this only
  once, so a default file or image that you delete does not return after a page
  refresh. The original demo files remain in the module for a future reinstall.
- The document field holds at most four files: three default examples plus one
  upload. The image field holds at most three images: two defaults plus one
  upload. Deleting an item frees a place for another upload.
- An AJAX upload is temporary until **Save** is submitted. After
  that save, uploaded files/images, descriptions, ordering, focus data and image
  variations persist on the ProcessShowcase media page across reloads.
- Deleting an item in the Inputfield and saving removes that stored item using
  ProcessWire's normal Pagefiles/Pageimages lifecycle.
- Uninstalling ProcessShowcase deletes every stored item in its two media fields,
  including user uploads and generated image variations, then deletes the media
  page, template and fields. Reinstalling recreates the complete fixture and
  copies in the default demo assets.

## Safe by design

- Restricted to superusers in `init()`.
- Most standalone form submissions are echoed back and not persisted.
- The native Repeater and file/image examples use explicitly named disposable
  fixtures because those Inputfields require real ProcessWire persistence.
- Uninstalling removes the admin page automatically (handled by the `page` key
  in `getModuleInfo()`), removes the Repeater fixture, and performs the media
  cleanup described above.

## Prompt

Write a complete ProcessWire module designed exclusively as a boilerplate reference for building backend administrative interfaces. Please provide the necessary PHP code and file structure to demonstrate how to implement module configuration pages, custom admin subpages with dedicated menu entries, and dashboard-style layouts. The module must include practical examples of forms utilizing all available ProcessWire input field types, specifically focusing on repeater fields, dropdowns, button groups, and buttons with dropdown menus for executing multiple actions. Additionally, incorporate examples of rendering data using tables, lists, and card layouts within the ProcessWire admin UI.


### HTML elements

The **HTML elements** dashboard card opens `setup/showcase/html-elements/`,
rendering the local upstream HTML5 Test Page body directly inside a UIkit card.
See [fixture update instructions](fixtures/html5-test-page/README.md).
The upstream HTML is unchanged on disk; its image URLs remain remote.