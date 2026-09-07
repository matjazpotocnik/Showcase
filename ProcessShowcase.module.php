<?php namespace ProcessWire;

/**
 * ProcessShowcase
 *
 * A boilerplate / reference module that showcases how to build backend
 * administrative interfaces in ProcessWire.
 *
 * It demonstrates:
 *   - Module configuration page (getModuleConfigInputfields)
 *   - Custom admin subpages with their own menu entries (useNavJSON + nav)
 *   - A dashboard-style landing page with stat cards & quick actions
	 *   - Focused forms for text/editors, numeric/date/content utilities,
	 *     selection controls,
	 *     and checkbox/radio/toggle Inputfields
 *   - Native InputfieldRepeater regression fixture and the idiomatic
 *     numbered-fieldset pattern used in standalone Process forms
 *   - Action UI: button groups, primary/secondary buttons, header buttons,
 *     and a single submit button with a dropdown of multiple actions
 *     (InputfieldSubmit::addActionValue)
 *   - Data rendering: MarkupAdminDataTable, description / item lists, and
 *     UIkit card grids
 *
 * Most form submissions are echoed back. Native Repeater and media examples
 * use disposable fixtures that are removed when the module is uninstalled.
 *
 * Install: copy this directory to /site/modules/ProcessShowcase/ then go
 * to Modules > Refresh and click Install on "Showcase".
 *
 * Visit: Setup > Showcase
 *
 * @license MIT
 *
 * @property string $environment
 * @property string $apiKey
 * @property int    $maxItems
 * @property int    $enableFeatureX
 * @property string $tags
 * @property string $notes
 *
 */
class ProcessShowcase extends Process implements ConfigurableModule
{
	protected const REPEATER_FIXTURE_FIELD = 'showcase_repeater';
	protected const REPEATER_FIXTURE_GREEN_FIELD = 'showcase_repeater_green';
	protected const REPEATER_FIXTURE_LIGHT_FIELD = 'showcase_repeater_light';
	protected const REPEATER_FIXTURE_DARK_FIELD = 'showcase_repeater_dark';
	protected const REPEATER_FIXTURE_TEXT_FIELD = 'showcase_repeater_text';
	protected const REPEATER_FIXTURE_TEMPLATE = 'showcase-repeater-fixture';
	protected const REPEATER_FIXTURE_PAGE = 'showcase-repeater-fixture';
	protected const MEDIA_FIXTURE_TEMPLATE = 'showcase-media-fixture';
	protected const MEDIA_FIXTURE_PAGE = 'showcase-media-fixture';
	protected const MEDIA_FIXTURE_FILE_FIELD = 'showcase_files';
	protected const MEDIA_FIXTURE_IMAGE_FIELD = 'showcase_images';
	protected const MEDIA_FIXTURE_INITIALIZED = 'showcaseDemoInitialized';

	/**
	 * @return array<string, mixed>
	 */
	public static function getModuleInfo()
	{
		return [
			'title' => 'Showcase',
			'summary' => 'Boilerplate / reference module that showcases ProcessWire admin UI patterns.',
			'version' => 2,
			'author' => 'webmanufaktur, Matjaž Potočnik, AI',
			'icon' => 'flask',
			'autoload' => false,
			'singular' => true,
			'requires' => 'ProcessWire>=3.0.0, PHP>=8.0.0',
			'page' => [
				'name' => 'showcase',
				'parent' => 'setup',
				'title' => 'Showcase',
			],
			// useNavJSON + nav adds child menu entries under the module's
			// admin page. Each entry corresponds to an executeXxx() method.
			'useNavJSON' => true,
			'nav' => [
				['url' => '',           'label' => 'Dashboard',         'icon' => 'tachometer'],
				['url' => 'text/',      'label' => 'Text & editors',    'icon' => 'font'],
				['url' => 'choices/',   'label' => 'Checkboxes, radios & toggles', 'icon' => 'check-square-o'],
				['url' => 'actions/',   'label' => 'Buttons & action menus', 'icon' => 'bolt'],
				['url' => 'forms/',     'label' => 'Basic inputs',      'icon' => 'list-alt'],
				['url' => 'selection-controls/', 'label' => 'Selection controls', 'icon' => 'list'],
				['url' => 'files-images/', 'label' => 'Files & images',  'icon' => 'picture-o'],
				['url' => 'repeater/',  'label' => 'Repeaters',    'icon' => 'clone'],
				['url' => 'page-references/', 'label' => 'Page references', 'icon' => 'sitemap'],
				['url' => 'tables/',    'label' => 'Tables',            'icon' => 'table'],
				['url' => 'lists/',     'label' => 'Lists',             'icon' => 'list-ul'],
				['url' => 'cards/',     'label' => 'Cards',             'icon' => 'th'],
				['url' => 'html-elements/', 'label' => 'HTML elements', 'icon' => 'html5'],
				['url' => 'pagelist/',  'label' => 'PageList states',   'icon' => 'sitemap'],
				['url' => 'feedback/',  'label' => 'Notices & dialogs', 'icon' => 'commenting'],
			],
		];
	}

	/**
	 * Create schema used by the native Repeater and media fixtures.
	 *
	 * @return void
	 */
	public function ___install()
	{
		$this->ensureNativeRepeaterFixture();
		$this->ensureNativeMediaFixture();
	}

	/**
	 * Remove schema and stored values owned by the native fixtures.
	 *
	 * @return void
	 */
	public function ___uninstall()
	{
		$this->removeNativeMediaFixture();
		$page = $this->pages->get('include=all, template=' . self::REPEATER_FIXTURE_TEMPLATE . ', name=' . self::REPEATER_FIXTURE_PAGE);
		if ($page->id) $this->pages->delete($page, true);

		$template = $this->templates->get(self::REPEATER_FIXTURE_TEMPLATE);
		if (!$template instanceof Template) $template = null;
		$textField = $this->fields->get(self::REPEATER_FIXTURE_TEXT_FIELD);
		if (!$textField instanceof Field) $textField = null;
		$repeaterFields = [];
		foreach ($this->getNativeRepeaterFixtureSpecs() as $fieldName => $spec) {
			$repeaterField = $this->fields->get($fieldName);
			if ($repeaterField instanceof Field) $repeaterFields[] = $repeaterField;
		}

		if ($template) {
			$fieldgroup = $template->fieldgroup;
			if (!$fieldgroup instanceof Fieldgroup) return;
			foreach ($repeaterFields as $repeaterField) {
				if ($fieldgroup->hasField($repeaterField)) {
					$fieldgroup->remove($repeaterField);
				}
			}
			if ($textField && $fieldgroup->hasField($textField)) {
				$fieldgroup->remove($textField);
			}
			$fieldgroup->save();
		}

		foreach ($repeaterFields as $repeaterField) {
			$this->fields->delete($repeaterField);
		}
		if ($template) {
			$fieldgroup = $template->fieldgroup;
			if (!$fieldgroup instanceof Fieldgroup) return;
			$this->templates->_callHookMethod('delete', [$template]);
			$this->fieldgroups->delete($fieldgroup);
		}
		if ($textField) $this->fields->delete($textField);
	}

	/**
	 * Default values for module configuration
	 *
	 * Used both by getModuleConfigInputfields() and to seed instance
	 * properties so accessing $this->apiKey etc. always works.
	 *
	 * @var array{
	 *     environment: string,
	 *     apiKey: string,
	 *     maxItems: int,
	 *     enableFeatureX: int,
	 *     tags: string,
	 *     notes: string
	 * }
	 */
	public static $defaults = [
		'environment'    => 'development',
		'apiKey'         => 'dummyapikey',
		'maxItems'       => 3,
		'enableFeatureX' => 1,
		'tags'           => 'Showcase',
		'notes'          => 'My notes',
	];

	public function __construct()
	{
		parent::__construct();
		foreach (self::$defaults as $k => $v) $this->set($k, $v);
	}

	/**
	 * Restrict the entire backend to superusers
	 *
	 * @return void
	 */
	public function init()
	{
		if (!$this->user->isSuperuser()) {
			throw new WirePermissionException('Superuser is required to view the Showcase.');
		}
		parent::init();
	}

	/* ---------------------------------------------------------------------
	 * Dashboard (default landing page)
	 * ------------------------------------------------------------------- */

	/**
	 * Landing page: a compact dashboard with stat cards + quick actions
	 *
	 * @return string
	 */
	public function ___execute()
	{
		$pages   = $this->pages;
		$users   = $this->users;
		$adminUrl = $this->page->url;

		$this->headline($this->_('Showcase dashboard'));
		$this->browserTitle($this->_('Showcase'));

		// Live stats from the actual install — purely illustrative.
		$stats = [
			['label' => $this->_('Pages'),    'value' => $pages->count('include=all'), 'icon' => 'file-o',    'href' => $this->config->urls->admin . 'page/'],
			['label' => $this->_('Users'),    'value' => $users->count(),               'icon' => 'user',      'href' => $this->config->urls->admin . 'access/users/'],
			['label' => $this->_('Templates'), 'value' => $this->templates->getAll()->count(), 'icon' => 'cube',     'href' => $this->config->urls->admin . 'setup/template/'],
			['label' => $this->_('Fields'),   'value' => $this->fields->getAll()->count(),  'icon' => 'cogs',      'href' => $this->config->urls->admin . 'setup/field/'],
		];

		$out = '<div class="uk-grid-small uk-child-width-1-2 uk-child-width-1-4@m" uk-grid>';
		foreach ($stats as $s) {
			$out .=
				'<div>' .
				'<a href="' . $this->sanitizer->entities($s['href']) . '" class="uk-link-reset">' .
				'<div class="uk-card uk-card-default uk-card-body uk-card-small uk-text-center">' .
				'<i class="fa fa-2x fa-' . $s['icon'] . ' uk-text-muted"></i>' .
				'<div class="uk-text-large uk-margin-small-top">' . (int) $s['value'] . '</div>' .
				'<div class="uk-text-meta">' . $s['label'] . '</div>' .
				'</div>' .
				'</a>' .
				'</div>';
		}
		$out .= '</div>';

		// Quick action tiles linking to each subpage.
		$tiles = [
			['url' => 'forms/',    'icon' => 'list-alt',   'label' => $this->_('Basic inputs'), 'desc' => $this->_('Numeric, date and utility Inputfields.')],
			['url' => 'selection-controls/', 'icon' => 'list', 'label' => $this->_('Selection controls'), 'desc' => $this->_('Dropdowns, listboxes, ordered selections and visual pickers.')], // MP
			['url' => 'page-references/', 'icon' => 'sitemap', 'label' => $this->_('Page references'), 'desc' => $this->_('Choose pages using dropdowns, lists, search, tags, or the page tree.')],
			['url' => 'files-images/', 'icon' => 'picture-o', 'label' => $this->_('Files & images'), 'desc' => $this->_('File and image upload Inputfields, including rendered gallery states.')],
			['url' => 'text/',     'icon' => 'font',       'label' => $this->_('Text & editors'), 'desc' => $this->_('Single-line, multi-line, sanitized and rich-text Inputfields.')],
			['url' => 'choices/',  'icon' => 'check-square-o', 'label' => $this->_('Checkboxes, radios & toggles'), 'desc' => $this->_('Single and multiple choices with their submitted value contracts.')],
			['url' => 'actions/',  'icon' => 'bolt',       'label' => $this->_('Buttons & action menus'), 'desc' => $this->_('Button hierarchy, grouped controls and submit buttons with dropdown actions.')],
			['url' => 'repeater/', 'icon' => 'clone',      'label' => $this->_('Repeaters'), 'desc' => $this->_('Test native InputfieldRepeater and Process-module repeating groups.')],
			['url' => 'tables/',   'icon' => 'table',      'label' => $this->_('Tables'),         'desc' => $this->_('Sortable data tables via MarkupAdminDataTable.')],
			['url' => 'lists/',    'icon' => 'list-ul',    'label' => $this->_('Lists'),          'desc' => $this->_('Description lists, nav lists and badge lists.')],
			['url' => 'cards/',    'icon' => 'th',         'label' => $this->_('Cards'),          'desc' => $this->_('Card grid layouts using UIkit.')],
			['url' => 'html-elements/', 'icon' => 'html5', 'label' => $this->_('HTML elements'), 'desc' => $this->_('The upstream HTML5 test page rendered with the admin theme.')],
			['url' => 'pagelist/', 'icon' => 'sitemap',    'label' => $this->_('PageList states'), 'desc' => $this->_('Open, hidden, unpublished, locked and loading page-tree states.')],
			['url' => 'feedback/', 'icon' => 'commenting', 'label' => $this->_('Notices & dialogs'), 'desc' => $this->_('Admin notices, confirms, alerts and iframe modals.')],
		];

		$out .= '<h2 class="uk-margin-large-top">' . $this->_('Explore the patterns') . '</h2>';
		$out .= '<div class="uk-grid-small uk-grid-match uk-child-width-1-2 uk-child-width-1-3@m" uk-grid>';
		foreach ($tiles as $t) {
			$href = $adminUrl . $t['url'];
			$out .=
				'<div>' .
				'<a href="' . $this->sanitizer->entities($href) . '" class="uk-link-reset uk-display-block uk-height-1-1">' .
				'<div class="uk-card uk-card-default uk-card-hover uk-card-body uk-card-small uk-height-1-1">' .
				'<h3 class="uk-card-title uk-margin-remove-bottom">' .
				'<i class="fa fa-fw fa-' . $t['icon'] . ' uk-margin-small-right uk-text-muted"></i>' .
				$t['label'] .
				'</h3>' .
				'<p class="uk-text-meta uk-margin-small-top">' . $t['desc'] . '</p>' .
				'</div>' .
				'</a>' .
				'</div>';
		}
		$out .= '</div>';

		// Inline cheat-sheet using a small data table
		$out .= '<h2 class="uk-margin-large-top">' . $this->_('Module configuration') . '</h2>';
		$out .= '<p>' .
			sprintf(
				$this->_('Current values from %1$sgetModuleConfigInputfields()%2$s — change them via Modules > Configure > Showcase.'),
				'<code>',
				'</code>'
			) .
			'</p>';

		$table = $this->requireModule(MarkupAdminDataTable::class);
		$table->setEncodeEntities(false);
		$table->headerRow([$this->_('Setting'), $this->_('Value')]);
		$table->row([$this->_('Environment'),  '<code>' . htmlspecialchars((string) $this->environment) . '</code>']);
		$table->row([$this->_('API key'),      $this->apiKey ? '<span class="uk-label uk-label-success">' . $this->_('Set') . '</span>' : '<span class="uk-label">' . $this->_('Not set') . '</span>']);
		$table->row([$this->_('Max items'),    (int) $this->maxItems]);
		$table->row([$this->_('Feature X'),    $this->enableFeatureX ? '<span class="uk-label uk-label-success">' . $this->_('On') . '</span>' : '<span class="uk-label uk-label-warning">' . $this->_('Off') . '</span>']);
		$table->row([$this->_('Tags'),         htmlspecialchars((string) $this->tags)]);
		$out .= $table->render();

		// Header actions: a primary "Configure" button + a secondary link
		$btn = $this->requireModule(InputfieldButton::class);
		$btn->href = $this->config->urls->admin . 'module/edit?name=' . $this->className();
		$btn->icon = 'cog';
		$btn->val($this->_('Configure module'));
		$btn->showInHeader(true);
		$out .= $btn->render();

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Basic inputs showcase
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase common numeric, date and utility Inputfield types.
	 *
	 * @return string
	 */
	public function ___executeForms()
	{
		$input   = $this->input;
		$session = $this->session;

		// $this->headline($this->_('Form fields')); // MP: renamed to distinguish this page from other Inputfield showcases
		$this->headline($this->_('Basic inputs'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$form = $this->requireModule(InputfieldForm::class);
		$form->attr('method', 'post');
		$form->attr('action', './');
		// $form->description = $this->_('Numeric, date, selection and utility Inputfields.'); // MP: selection controls moved to their own page
		$form->description = $this->_('Numeric, date and utility Inputfields.');

		/* --- Numeric --- */

		$fs = $this->requireModule(InputfieldFieldset::class);
		$fs->label = $this->_('Numeric fields');
		$fs->icon  = 'calculator';
		$form->add($fs);

		$f = $this->requireModule(InputfieldInteger::class);
		$f->attr('name', 'integer');
		$f->label = $this->_('InputfieldInteger');
		$f->description = $this->_('A whole number constrained to the range 0–100.');
		$f->inputType = 'number';
		$f->min = 0;
		$f->max = 100;
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $this->requireModule(InputfieldFloat::class);
		$f->attr('name', 'float');
		$f->label = $this->_('InputfieldFloat');
		$f->description = $this->_('A floating-point number without fixed precision.');
		$f->inputType = 'number';
		$f->precision = -1;
		$f->columnWidth = 50;
		$fs->add($f);

		/* --- Date and time --- */

		$fs = $this->requireModule(InputfieldFieldset::class);
		$fs->label = $this->_('Date and time fields');
		$fs->icon = 'calendar';
		$form->add($fs);

		$f = $this->requireModule(InputfieldDatetime::class);
		$f->attr('name', 'date');
		$f->label = $this->_('Date only');
		$f->description = $this->_('InputfieldDatetime with a datepicker opened from the calendar icon.');
		$f->datepicker = InputfieldDatetime::datepickerClick;
		$f->dateInputFormat = 'Y-m-d';
		$f->timeInputFormat = '';
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $this->requireModule(InputfieldDatetime::class);
		$f->attr('name', 'time');
		$f->label = $this->_('Time only');
		$f->description = $this->_('InputfieldDatetime with the native timepicker opened when the field receives focus.');
		$f->datepicker = InputfieldDatetime::datepickerFocus;
		$f->dateInputFormat = '';
		$f->timeInputFormat = 'H:i';
		$f->datepickerOptions(['timeOnly' => true]);
		$f->columnWidth = 33;
		$fs->add($f);

		$f = $this->requireModule(InputfieldDatetime::class);
		$f->attr('name', 'datetime');
		$f->label = $this->_('Date and time');
		$f->description = $this->_('InputfieldDatetime combining the datepicker and timepicker.');
		$f->datepicker = InputfieldDatetime::datepickerClick;
		$f->dateInputFormat = 'Y-m-d';
		$f->timeInputFormat = 'H:i';
		$f->columnWidth = 34;
		$fs->add($f);

		/* MP: expose native ProcessWire and UIkit validation states for theme testing */

		$fs = $this->requireModule(InputfieldFieldset::class);
		$fs->label = $this->_('Validation state');
		$fs->icon = 'exclamation-circle';
		$form->add($fs);

		$f = $this->requireModule(InputfieldText::class);
		$f->attr('name', 'required_text');
		$f->label = $this->_('Required text');
		$f->description = $this->_("Submit this field empty to display ProcessWire's native validation error state.");
		$f->required = true;
		$f->requiredAttr = false;
		$fs->add($f);

		$f = $this->requireModule(InputfieldText::class);
		$f->attr('name', 'success_text');
		$f->label = $this->_('Successful input');
		$f->attr('value', $this->_('Valid value'));
		$f->addClass('uk-form-success');
		$f->columnWidth = 50;
		$fs->add($f);

		$f = $this->requireModule(InputfieldText::class);
		$f->attr('name', 'danger_text');
		$f->label = $this->_('Invalid input');
		$f->attr('value', $this->_('Invalid value'));
		$f->addClass('uk-form-danger');
		$f->columnWidth = 50;
		$fs->add($f);

		/* --- Hidden + markup --- */

		$f = $this->requireModule(InputfieldHidden::class);
		$f->attr('name', 'token');
		$f->attr('value', uniqid('tok_', true));
		$form->add($f);

		$f = $this->requireModule(InputfieldMarkup::class);
		$f->label = $this->_('Markup field (read-only block)');
		$f->icon = 'info-circle';
		$f->val(
			'<p>' .
				$this->_('InputfieldMarkup is useful for embedding arbitrary HTML inside a form — help text, computed values, charts, etc.') .
				'</p>'
		);
		$form->add($f);

		/* --- Empty header-hidden markup (runtime AJAX container) --- */

		$f = $this->requireModule(InputfieldMarkup::class);
		$f->attr('name', 'markup_ajax_container');
		// no label set — InputfieldMarkup adds InputfieldHeaderHidden when the label is blank
		$f->description = $this->_('Runtime-only container for AJAX responses — header hidden, no value yet.');
		$form->add($f);

		/* --- Submit --- */

		$f = $this->requireModule(InputfieldSubmit::class);
		$f->attr('name', 'submit_forms');
		$f->val($this->_('Submit'));
		$f->icon = 'check';
		$f->showInHeader(true);
		$form->add($f);

		// Process submitted values
		if ($input->requestMethod('post') && $input->post('submit_forms')) {
			$session->CSRF()->validate();
			$form->processInput($input->post);
			return $this->renderSubmittedValues($form) . $form->render();
		}

		return $form->render();
	}

	/* ---------------------------------------------------------------------
	 * Selection controls
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase dropdowns, listboxes, ordered selections and visual pickers.
	 *
	 * @return string
	 */
	public function ___executeSelectionControls()
	{
		$modules = $this->modules;
		$input = $this->input;
		$session = $this->session;
		$selectedPages = $this->pages->find('id>1, include=hidden, sort=id, limit=3');
		$singlePage = $selectedPages->first();

		$this->headline($this->_('Selection controls'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$form = $this->requireModule(InputfieldForm::class);
		$form->attr('method', 'post');
		$form->attr('action', './');
		$form->description = $this->_('Dropdowns, listboxes, ordered selections and visual pickers.');

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Option selection');
		$fieldset->icon = 'list';
		$form->add($fieldset);

		$colors = [
			'red' => $this->_('Red'),
			'green' => $this->_('Green'),
			'blue' => $this->_('Blue'),
			'yellow' => $this->_('Yellow'),
		];

		$field = $this->requireModule(InputfieldSelect::class);
		$field->attr('name', 'select');
		$field->label = $this->_('InputfieldSelect');
		$field->description = $this->_('A standard single-choice dropdown.');
		$field->addOption('', $this->_('— pick one —'));
		foreach ($colors as $value => $label) $field->addOption($value, $label);
		$field->columnWidth = 50;
		$fieldset->add($field);

		$field = $this->requireModule(InputfieldSelectMultiple::class);
		$field->attr('name', 'select_multiple');
		$field->label = $this->_('InputfieldSelectMultiple');
		$field->description = $this->_('A native multiple-choice listbox.');
		foreach ($colors as $value => $label) $field->addOption($value, $label);
		$field->columnWidth = 50;
		$fieldset->add($field);

		if ($modules->isInstalled('InputfieldAsmSelect')) {
			$field = $this->requireModule(InputfieldAsmSelect::class);
			$field->attr('name', 'asm');
			$field->label = $this->_('InputfieldAsmSelect');
			$field->description = $this->_('A searchable multiple selection with sortable selected items.');
			foreach ($colors as $value => $label) $field->addOption($value, $label);
			$field->setAttribute('value', ['green', 'blue']);
			$fieldset->add($field);
		}

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Page-tree selection');
		$fieldset->icon = 'sitemap';
		$form->add($fieldset);

		if ($modules->isInstalled('InputfieldPageListSelect')) {
			$field = $this->requireModule(InputfieldPageListSelect::class);
			$field->attr('name', 'page_list_select');
			$field->label = $this->_('InputfieldPageListSelect');
			$field->description = $this->_('Select one page from the page tree.');
			$field->parent_id = 1;
			$field->labelFieldName = 'title';
			if ($singlePage instanceof Page) $field->setAttribute('value', $singlePage);
			$field->columnWidth = 50;
			$fieldset->add($field);
		}

		if ($modules->isInstalled('InputfieldPageListSelectMultiple')) {
			$field = $this->requireModule(InputfieldPageListSelectMultiple::class);
			$field->attr('name', 'page_list_select_multiple');
			$field->label = $this->_('InputfieldPageListSelectMultiple');
			$field->description = $this->_('Select and sort multiple pages from the page tree.');
			$field->parent_id = 1;
			$field->labelFieldName = 'title';
			$field->setAttribute('value', $selectedPages->explode('id'));
			$field->columnWidth = 50;
			$fieldset->add($field);
		}

		if ($modules->isInstalled('InputfieldIcon')) {
			$field = $this->requireModule(InputfieldIcon::class);
			$field->attr('name', 'icon');
			$field->label = $this->_('InputfieldIcon');
			$field->description = $this->_('Search and select an icon.');
			$form->add($field);
		}

		if ($modules->isInstalled('InputfieldSelector')) {
			$fieldset = $this->requireModule(InputfieldFieldset::class);
			$fieldset->label = $this->_('Selector builder');
			$fieldset->icon = 'search';
			$form->add($fieldset);

			$field = $this->requireModule(InputfieldSelector::class);
			$field->attr('name', 'selector');
			$field->label = $this->_('InputfieldSelector');
			$field->description = $this->_('Visually compose a ProcessWire selector string.');
			$field->icon = 'search';
			$fieldset->add($field);
		}

		$field = $this->requireModule(InputfieldSubmit::class);
		$field->attr('name', 'submit_selection_controls');
		$field->val($this->_('Submit'));
		$field->icon = 'check';
		$field->showInHeader(true);
		$form->add($field);

		if ($input->requestMethod('post') && $input->post('submit_selection_controls')) {
			$session->CSRF()->validate();
			$form->processInput($input->post);
			return $this->renderSubmittedValues($form) . $form->render();
		}

		return $form->render();
	}

	/* ---------------------------------------------------------------------
	 * Page references
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase every core page-selection delegate through InputfieldPage.
	 *
	 * @return string
	 */
	public function ___executePageReferences()
	{
		$modules = $this->modules;
		$pages = $this->pages;
		$fields = $this->fields;
		$selectedPages = $pages->find('id>1, include=hidden, sort=id, limit=3');
		$singlePage = $selectedPages->first();

		$this->headline($this->_('Page references'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$form = $this->requireModule(InputfieldForm::class);
		$form->attr('method', 'post');
		$form->attr('action', './');
		$form->description = $this->_('InputfieldPage delegates selection and processing to a configured native Inputfield. All examples use existing pages and remain expanded so their delegates can be fully configured.');

		$single = $this->requireModule(InputfieldFieldset::class);
		$single->label = $this->_('Select-based page pickers: single page');
		$single->icon = 'dot-circle-o';
		$form->add($single);

		$singleDelegates = [
			['InputfieldSelect', $this->_('InputfieldSelect'), $this->_('Plain dropdown.')],
			['InputfieldRadios', $this->_('InputfieldRadios'), $this->_('Radio buttons.')],
			['InputfieldPageListSelect', $this->_('InputfieldPageListSelect'), $this->_('Page-tree modal picker.')],
		];
		foreach ($singleDelegates as $index => [$inputfield, $label, $description]) {
			$f = $this->requireModule(InputfieldPage::class);
			$f->attr('name', 'page_reference_single_' . $index);
			$f->label = $label;
			$f->description = $description;
			$f->inputfield = $inputfield;
			$f->findPagesSelector = 'id>1, include=hidden, sort=id, limit=12';
			$f->labelFieldName = 'title';
			$f->derefAsPage = 1;
			if ($singlePage instanceof Page) $f->setAttribute('value', $singlePage);
			$f->columnWidth = 33;
			$single->add($f);
		}

		$multiple = $this->requireModule(InputfieldFieldset::class);
		$multiple->label = $this->_('Select-based page pickers: multiple pages');
		$multiple->icon = 'check-square-o';
		$form->add($multiple);

		$multipleDelegates = [
			['InputfieldSelectMultiple', $this->_('InputfieldSelectMultiple'), $this->_('Multi-select listbox.')],
			['InputfieldCheckboxes', $this->_('InputfieldCheckboxes'), $this->_('Checkbox list.')],
			['InputfieldAsmSelect', $this->_('InputfieldAsmSelect'), $this->_('Sortable selected pages.')],
			['InputfieldPageListSelectMultiple', $this->_('InputfieldPageListSelectMultiple'), $this->_('Multi-select page-tree modal picker.')],
		];
		foreach ($multipleDelegates as $index => [$inputfield, $label, $description]) {
			if (!$modules->isInstalled($inputfield)) continue;
			$f = $this->requireModule(InputfieldPage::class);
			$f->attr('name', 'page_reference_multiple_' . $index);
			$f->label = $label;
			$f->description = $description;
			$f->inputfield = $inputfield;
			$f->findPagesSelector = 'id>1, include=hidden, sort=id, limit=12';
			$f->labelFieldName = 'title';
			$f->setAttribute('value', $selectedPages);
			$f->columnWidth = 50;
			$multiple->add($f);
		}

		$adaptive = $this->requireModule(InputfieldFieldset::class);
		$adaptive->label = $this->_('Search and tag page pickers');
		$adaptive->icon = 'search';
		$form->add($adaptive);

		$adaptiveDelegates = [
			['InputfieldPageAutocomplete', $this->_('InputfieldPageAutocomplete'), $this->_('AJAX-backed typeahead configured for multiple pages.')],
			['InputfieldTextTags', $this->_('InputfieldTextTags'), $this->_('Tag-style input configured for multiple pages.')],
		];
		foreach ($adaptiveDelegates as $index => [$inputfield, $label, $description]) {
			if (!$modules->isInstalled($inputfield)) continue;
			$f = $this->requireModule(InputfieldPage::class);
			$f->attr('name', 'page_reference_adaptive_' . $index);
			$f->label = $label;
			$f->description = $description;
			$f->inputfield = $inputfield;
			$f->findPagesSelector = 'id>1, include=hidden, sort=id, limit=20';
			$f->labelFieldName = 'title';
			$f->set('maxSelectedItems', 0);
			$f->setAttribute('value', $selectedPages);
			$f->columnWidth = 50;
			$adaptive->add($f);
		}

		$pageTable = $this->requireModule(InputfieldFieldset::class);
		$pageTable->label = $this->_('PageTable');
		$pageTable->icon = 'table';
		$form->add($pageTable);

		$pageTableInputfield = null;
		if ($modules->isInstalled('InputfieldPageTable') && $modules->isInstalled('FieldtypePageTable')) {
			foreach ($fields as $field) {
				if (!$field->type || $field->type->className() !== 'FieldtypePageTable') continue;
				$fieldTemplates = $field->getTemplates();
				if (!$fieldTemplates instanceof TemplatesArray) continue;
				foreach ($fieldTemplates as $template) {
					if (!$template instanceof Template) continue;
					$hostPages = $pages->find('template=' . $template->name . ', include=all, limit=20');
					foreach ($hostPages as $hostPage) {
						$value = $hostPage->getUnformatted($field->name);
						if (!$value instanceof PageArray || !$value->count()) continue;
						$pageTableInputfield = $field->getInputfield($hostPage);
						break 3;
					}
				}
			}
		}

		if ($pageTableInputfield) {
			$pageTableInputfield->attr('name', 'page_references_page_table');
			$pageTableInputfield->label = $this->_('PageTable (read-only)');
			$pageTableInputfield->description = $this->_('A real configured PageTable rendered in locked value mode so edit and delete actions remain disabled in the Showcase.');
			$pageTableInputfield->collapsed = Inputfield::collapsedNoLocked;
			$pageTable->add($pageTableInputfield);
		} else {
			$f = $this->requireModule(InputfieldMarkup::class);
			$f->label = $this->_('PageTable');
			$f->val('<p class="uk-text-muted">' . $this->_('No populated, configured PageTable field is available on this installation.') . '</p>');
			$pageTable->add($f);
		}

		$submit = $this->requireModule(InputfieldSubmit::class);
		$submit->attr('name', 'submit_page_references');
		$submit->val($this->_('Show processed values'));
		$submit->icon = 'check';
		$form->add($submit);

		if ($form->isSubmitted('submit_page_references') && $form->process()) {
			return $this->renderSubmittedValues($form) . $form->render();
		}

		return $form->render();
	}

	/* ---------------------------------------------------------------------
	 * Files and images
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase genuine file and image Inputfields on a module-owned scratch page.
	 *
	 * @return string
	 */
	public function ___executeFilesImages()
	{
		$input = $this->input;
		$session = $this->session;

		$this->headline($this->_('Files & images'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$form = $this->requireModule(InputfieldForm::class);
		$form->attr('method', 'post');
		$form->attr('action', './');
		$form->attr('enctype', 'multipart/form-data');
		// $form->description = $this->_('File and image Inputfields backed by the disposable WireTests page.'); // MP: removed WireTests dependency
		$form->description = $this->_('File and image Inputfields backed by a disposable ProcessShowcase page.');

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Files & images');
		$fieldset->icon = 'picture-o';
		$form->add($fieldset);

		$fixture = $this->ensureNativeMediaFixture();
		// if (!$fixture) throw new WireException($this->_('Unable to prepare the WireTests media fixture.')); // MP: media fixture is now module-owned
		if (!$fixture) throw new WireException($this->_('Unable to prepare the ProcessShowcase media fixture.'));
		$fixturePage = $fixture['page'];
		$fixturePage->of(false);

		$fileInputfield = $fixturePage->getInputfield(self::MEDIA_FIXTURE_FILE_FIELD);
		if (!$fileInputfield instanceof InputfieldFile) {
			throw new WireException($this->_('Unable to create the file showcase Inputfield.'));
		}
		$fileInputfield->label = $this->_('InputfieldFile');
		$fileInputfield->description = $this->_('Upload and manage up to four documents, including descriptions, tags, sorting and deletion.');
		$fieldset->add($fileInputfield);

		$imageInputfield = $fixturePage->getInputfield(self::MEDIA_FIXTURE_IMAGE_FIELD);
		if (!$imageInputfield instanceof InputfieldImage) {
			throw new WireException($this->_('Unable to create the image showcase Inputfield.'));
		}
		$imageInputfield->label = $this->_('InputfieldImage');
		$imageInputfield->description = $this->_('Upload and manage up to three images, including sorting, crop, resize and editing in grid, left and list modes.');
		$imageInputfield->addHookAfter('renderUpload', function(HookEvent $event): void {
			$markup = $event->return;
			if (!is_string($markup)) return;
			$label = $this->sanitizer->entities($this->_('Choose image'));
			$updatedMarkup = preg_replace(
				"~(<div class='InputMask[^>]*>\\s*<span class='ui-button-text'>.*?</i>)\\s*[^<]*(</span>)~s",
				'$1' . $label . '$2',
				$markup,
				1
			);
			if (is_string($updatedMarkup)) $event->return = $updatedMarkup;
		});
		$fieldset->add($imageInputfield);

		$ajaxPageId = $this->requireModule(InputfieldHidden::class);
		$ajaxPageId->attr('id+name', 'id');
		$ajaxPageId->attr('value', $fixturePage->id);
		$ajaxPageId->addClass('InputfieldAllowAjaxUpload');
		$ajaxPageId->set('showcaseSkipResult', true);
		$fieldset->add($ajaxPageId);

		$submit = $this->requireModule(InputfieldSubmit::class);
		$submit->attr('name', 'submit_files_images');
		$submit->val($this->_('Save'));
		$submit->icon = 'check';
		$submit->showInHeader(true);
		$form->add($submit);

		if ($input->requestMethod('post') && $input->post('submit_files_images')) {
			$session->CSRF()->validate();
			$form->processInput($input->post);
			$fixturePage->set(self::MEDIA_FIXTURE_FILE_FIELD, $fileInputfield->val());
			$fixturePage->save(self::MEDIA_FIXTURE_FILE_FIELD);
			$fixturePage->set(self::MEDIA_FIXTURE_IMAGE_FIELD, $imageInputfield->val());
			$fixturePage->save(self::MEDIA_FIXTURE_IMAGE_FIELD);
			$submittedValues = $this->renderSubmittedValues($form);
			return $submittedValues . $form->render();
		}

		return $form->render();
	}

	/**
	 * Get or create the hidden page used by native media Inputfields.
	 *
	 * @param bool $create Create the template and page when missing
	 * @return Page|null
	 */
	public function getTestPage(bool $create = true): ?Page
	{
		$template = $this->templates->get(self::MEDIA_FIXTURE_TEMPLATE);
		if (!$template instanceof Template) {
			if (!$create) return null;
			$template = $this->templates->new(self::MEDIA_FIXTURE_TEMPLATE);
			$template->noChildren = 1;
			$template->noParents = -1;
			$template->save();
		} elseif (!$template->fieldgroup) {
			$template->save();
		}
		$fieldgroup = $template->fieldgroup;
		$titleField = $this->fields->get('title');
		if (!$fieldgroup instanceof Fieldgroup || !$titleField instanceof Field) return null;
		if (!$fieldgroup->hasField($titleField)) {
			$fieldgroup->add($titleField);
			$fieldgroup->save();
		}

		$page = $this->pages->get('include=all, template=' . self::MEDIA_FIXTURE_TEMPLATE . ', name=' . self::MEDIA_FIXTURE_PAGE);
		if ($page->id) {
			if ($page->isUnpublished()) {
				$page->removeStatus(Page::statusUnpublished);
				$page->save();
			}
			return $page;
		}
		if (!$create) return null;

		return $this->pages->new([
			'template' => $template,
			'parent' => 1,
			'name' => self::MEDIA_FIXTURE_PAGE,
			'title' => $this->_('ProcessShowcase media fixture'),
			'status' => Page::statusHidden,
		]);
	}

	/**
	 * Ensure genuine file and image fields exist on the module-owned scratch page.
	 *
	 * @return array{fields: array<string, Field>, page: Page}|null
	 */
	protected function ensureNativeMediaFixture(): ?array
	{
		$fileFieldtype = $this->modules->get('FieldtypeFile');
		$imageFieldtype = $this->modules->get('FieldtypeImage');
		if (!$fileFieldtype instanceof FieldtypeFile || !$imageFieldtype instanceof FieldtypeImage) {
			return null;
		}
		// $page = $wireTests->getTestPage(); // MP: replaced by the module-owned fixture page
		$page = $this->getTestPage();
		if (!$page instanceof Page || !$page->id) return null;

		$specs = [
			self::MEDIA_FIXTURE_FILE_FIELD => [
				'type' => $fileFieldtype,
				'label' => $this->_('Showcase files'),
				'extensions' => 'pdf docx txt',
				'inputfieldClass' => InputfieldFile::class,
				'maxFiles' => 4,
			],
			self::MEDIA_FIXTURE_IMAGE_FIELD => [
				'type' => $imageFieldtype,
				'label' => $this->_('Showcase images'),
				'extensions' => 'jpg png gif',
				'inputfieldClass' => InputfieldImage::class,
				'maxFiles' => 3,
			],
		];
		$fixtureFields = [];
		foreach ($specs as $fieldName => $spec) {
			$field = $this->fields->get($fieldName);
			if (!$field instanceof Field) {
				$field = $this->fields->new($spec['type'], $fieldName, ['label' => $spec['label']]);
			}
			$field->label = $spec['label'];
			$field->set('extensions', $spec['extensions']);
			$field->set('inputfieldClass', $spec['inputfieldClass']);
			$field->set('maxFiles', $spec['maxFiles']);
			$field->set('descriptionRows', 1);
			$field->set('outputFormat', FieldtypeFile::outputFormatArray);
			$field->set('useTags', FieldtypeFile::useTagsNormal);
			if ($fieldName === self::MEDIA_FIXTURE_IMAGE_FIELD) {
				$field->set('focusMode', 'on');
				$field->set('gridMode', 'grid');
			}
			$field->save();
			$fixtureFields[$fieldName] = $field;
		}

		$template = $this->templates->get($page->templates_id);
		if (!$template instanceof Template) return null;
		$fieldgroup = $template->fieldgroup;
		if (!$fieldgroup instanceof Fieldgroup) return null;
		$fieldgroupChanged = false;
		foreach ($fixtureFields as $field) {
			if ($fieldgroup->hasField($field)) continue;
			$fieldgroup->add($field);
			$fieldgroupChanged = true;
		}
		if ($fieldgroupChanged) $fieldgroup->save();

		$this->seedNativeMediaFixture($page, $fixtureFields);
		return ['fields' => $fixtureFields, 'page' => $page];
	}

	/**
	 * Add module-owned demo assets when each media field is first initialized.
	 *
	 * @param Page $page
	 * @param array<string, Field> $fields
	 * @return void
	 */
	protected function seedNativeMediaFixture(Page $page, array $fields): void
	{
		$assetPath = __DIR__ . '/assets/demo/';
		$seeds = [
			self::MEDIA_FIXTURE_FILE_FIELD => [
				['project-brief.pdf', $this->_('Project scope, goals and delivery milestones.')],
				['interface-notes.docx', $this->_('Interface notes and implementation details.')],
				['release-notes.txt', $this->_('Highlights and changes included in this release.')],
			],
			self::MEDIA_FIXTURE_IMAGE_FIELD => [
				['modern-facade.jpg', $this->_('Colorful architecture for crop and focus testing.')],
				['creative-workspace.png', $this->_('Collaborative workspace for grid and resize testing.')],
			],
		];
		$page->of(false);
		foreach ($seeds as $fieldName => $items) {
			if (!isset($fields[$fieldName])) continue;
			$field = $fields[$fieldName];
			$value = $page->get($fieldName);
			if (!$value instanceof Pagefiles) continue;
			$changed = false;
			if (!$field->get(self::MEDIA_FIXTURE_INITIALIZED)) {
				foreach ($items as [$basename, $description]) {
					if ($value->get($basename)) continue;
					$source = $assetPath . $basename;
					if (!is_file($source)) continue;
					$value->add($source);
					$added = $value->last();
					if ($added instanceof Pagefile) $added->description = $description;
					$changed = true;
				}
				$field->set(self::MEDIA_FIXTURE_INITIALIZED, 1);
				$field->save();
			}
			if ($changed) $page->save($fieldName);
		}
	}

	/**
	 * Remove the ProcessShowcase media page, template, fields and stored files.
	 *
	 * @return void
	 */
	protected function removeNativeMediaFixture(): void
	{
		$page = $this->getTestPage(false);
		if ($page instanceof Page && $page->id) $this->pages->delete($page, true);
		$template = $this->templates->get(self::MEDIA_FIXTURE_TEMPLATE);
		if (!$template instanceof Template) $template = null;
		$fixtureFields = [];
		foreach ([self::MEDIA_FIXTURE_FILE_FIELD, self::MEDIA_FIXTURE_IMAGE_FIELD] as $fieldName) {
			$field = $this->fields->get($fieldName);
			if (!$field instanceof Field) continue;
			foreach ($this->fieldgroups as $fieldgroup) {
				if (!$fieldgroup->hasField($field)) continue;
				$fieldgroup->remove($field);
				$fieldgroup->save();
			}
			$fixtureFields[] = $field;
		}
		foreach ($fixtureFields as $field) $this->fields->delete($field);
		if ($template) {
			$fieldgroup = $template->fieldgroup;
			if (!$fieldgroup instanceof Fieldgroup) return;
			$this->templates->_callHookMethod('delete', [$template]);
			$this->fieldgroups->delete($fieldgroup);
		}
	}

	/* ---------------------------------------------------------------------
	 * Text and editors
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase text-family Inputfields and rich-text editors.
	 *
	 * @return string
	 */
	public function ___executeText()
	{
		$modules = $this->modules;

		$this->headline($this->_('Text & editors'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$form = $this->requireModule(InputfieldForm::class);
		$form->attr('id', 'ShowcaseTextForm');
		$form->attr('method', 'post');
		$form->attr('action', './');
		$form->description = $this->_('InputfieldForm: text and rich-text Inputfields.');

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Single-line and multi-line text');
		$fieldset->icon = 'pencil';
		$form->add($fieldset);

		$field = $this->requireModule(InputfieldText::class);
		$field->attr('name', 'text');
		$field->label = $this->_('InputfieldText');
		$field->description = $this->_('Extends Inputfield with text validation, placeholders and character counters.');
		$field->attr('placeholder', $this->_('e.g. Project Phoenix'));
		$field->showCount = InputfieldText::showCountChars;
		$field->columnWidth = 50;
		$fieldset->add($field);

		$field = $this->requireModule(InputfieldEmail::class);
		$field->attr('name', 'email');
		$field->label = $this->_('InputfieldEmail');
		$field->description = $this->_('Extends InputfieldText with email sanitization and validation.');
		$field->columnWidth = 50;
		$fieldset->add($field);

		$field = $this->requireModule(InputfieldURL::class);
		$field->attr('name', 'url');
		$field->label = $this->_('InputfieldURL');
		$field->description = $this->_('Extends InputfieldText with URL sanitization and validation.');
		$field->columnWidth = 50;
		$fieldset->add($field);

		$field = $this->requireModule(InputfieldPassword::class);
		$field->attr('name', 'password');
		$field->label = $this->_('InputfieldPassword');
		//$field->description = $this->_('Extends InputfieldText with confirmation and strength validation.');
		$field->collapsed = Inputfield::collapsedNever;
		$field->columnWidth = 50;
		$fieldset->add($field);

		$field = $this->requireModule(InputfieldTextarea::class);
		$field->attr('name', 'textarea');
		$field->attr('rows', 6);
		$field->label = $this->_('InputfieldTextarea');
		$field->description = $this->_('Extends InputfieldText with rows and content-type behavior.');
		$fieldset->add($field);

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Sanitized names and titles');
		$fieldset->icon = 'tag';
		$form->add($fieldset);

		$field = $this->requireModule(InputfieldName::class);
		$field->attr('name', 'var_name');
		$field->label = $this->_('InputfieldName');
		$field->description = $this->_('Extends InputfieldText for identifiers such as field, template and module names.');
		$field->columnWidth = 34;
		$fieldset->add($field);

		$field = $this->requireModule(InputfieldPageName::class);
		$field->attr('name', 'page_name');
		$field->label = $this->_('InputfieldPageName');
		$field->description = $this->_('Extends InputfieldName and sanitizes input to a valid page name (URL segment).');
		$field->columnWidth = 33;
		$fieldset->add($field);

		$field = $this->requireModule(InputfieldPageTitle::class);
		$field->attr('name', 'page_title');
		$field->label = $this->_('InputfieldPageTitle');
		$field->description = $this->_('Extends InputfieldText and coordinates title-to-name generation.');
		$field->showCount = InputfieldText::showCountChars;
		$field->columnWidth = 33;
		$fieldset->add($field);

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Language tabs');
		$fieldset->icon = 'language';
		$form->add($fieldset);

		if ($modules->isInstalled('LanguageSupport') && $modules->isInstalled('LanguageTabs')) {
			$field = $this->requireModule(InputfieldText::class);
			$field->attr('name', 'translated_title');
			$field->label = $this->_('Translated title');
			$field->description = $this->_('Switch between populated and empty language tabs.');
			$field->useLanguages = true;
			$field->val($this->_('Default language value'));
			foreach ($this->languages as $language) {
				if ($language->isDefault()) continue;
				$field->set("value{$language->id}", $language->id % 2 ? $this->_('Translated value') : '');
			}
			$fieldset->add($field);
		} else {
			$field = $this->requireModule(InputfieldMarkup::class);
			$field->label = $this->_('Language tabs');
			$field->val('<p class="uk-text-muted">' . $this->_('LanguageSupport and LanguageTabs are not installed.') . '</p>');
			$fieldset->add($field);
		}

		/* MP: expose heading code sizes covered by ProcessWire's admin typography */
		$field = $this->requireModule(InputfieldMarkup::class);
		$field->label = $this->_('Code in headings');
		$field->val(
			'<h2>' . $this->_('Level two') . ' <code>ProcessPageEdit</code></h2>' .
			'<h3>' . $this->_('Level three') . ' <code>InputfieldText</code></h3>' .
			'<h4>' . $this->_('Level four') . ' <code>FieldtypeText</code></h4>'
		);
		$fieldset->add($field);

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Rich-text editors');
		$fieldset->icon = 'paragraph';
		$form->add($fieldset);

		if ($modules->isInstalled('InputfieldCKEditor')) {
			$field = $this->requireModule(InputfieldCKEditor::class);
			$field->attr('name', 'rich_ckeditor');
			$field->label = $this->_('InputfieldCKEditor');
			$field->description = $this->_('Extends InputfieldTextarea with CKEditor rich-text editing.');
			$fieldset->add($field);
		}

		if ($modules->isInstalled('InputfieldTinyMCE')) {
			$field = $this->requireModule(InputfieldTinyMCE::class);
			$field->attr('name', 'rich_tinymce');
			$field->label = $this->_('InputfieldTinyMCE');
			$field->description = $this->_('Extends InputfieldTextarea with TinyMCE rich-text editing.');
			$fieldset->add($field);
		}

		if (!$modules->isInstalled('InputfieldCKEditor') && !$modules->isInstalled('InputfieldTinyMCE')) {
			$field = $this->requireModule(InputfieldTextarea::class);
			$field->attr('name', 'rich');
			$field->attr('rows', 8);
			$field->label = $this->_('InputfieldTextarea: rich-text fallback');
			$field->description = $this->_('Extends InputfieldText; used here when no rich-text editor is installed.');
			$fieldset->add($field);
		}

		if ($modules->isInstalled('InputfieldJson')) {
			$field = $this->requireModule(InputfieldJson::class);
			$field->attr('name', 'json');
			$field->label = $this->_('InputfieldJSON');
			$field->description = $this->_('Edit nested object, array, string, number and boolean values in the native tree view.');
			$field->mode = 'tree';
			$field->useNavigationBar = true;
			$field->val(json_encode([
				'project' => 'ProcessWire Showcase',
				'enabled' => true,
				'priority' => 3,
				'tags' => ['admin', 'inputfield', 'json'],
				'options' => ['theme' => 'Konkat', 'mode' => 'adaptive'],
			], JSON_THROW_ON_ERROR));
			$fieldset->add($field);
		}

		$submit = $this->requireModule(InputfieldSubmit::class);
		$submit->attr('name', 'submit_text');
		$submit->val($this->_('Show processed values'));
		$submit->icon = 'check';
		$submit->notes = $this->_('InputfieldSubmit: processes this form and displays captured values above it.');
		$submit->showInHeader(true);
		$form->add($submit);

		$pageNamePreviewScript = <<<'HTML'
<script>
jQuery(function($) {
	var $input = $('#ShowcaseTextForm input[name="page_name"]');
	var $preview = $('#' + $input.attr('id') + '_path');
	if($input.length && $preview.length) $preview.insertAfter($input);
});
</script>
HTML;

		if ($form->isSubmitted('submit_text') && $form->process()) {
			return $this->renderSubmittedValues($form) . $form->render() . $pageNamePreviewScript;
		}

		return $form->render() . $pageNamePreviewScript;
	}

	/* ---------------------------------------------------------------------
	 * Checkboxes, radios and toggles
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase choice Inputfields and their submitted value contracts.
	 *
	 * @return string
	 */
	public function ___executeChoices()
	{
		$this->headline($this->_('Checkboxes, radios & toggles'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$form = $this->requireModule(InputfieldForm::class);
		$form->attr('id', 'ShowcaseChoicesForm');
		$form->attr('method', 'post');
		$form->attr('action', './');
		$form->description = $this->_('InputfieldForm: single and multiple choice controls.');

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Checkboxes');
		$fieldset->icon = 'check-square-o';
		$form->add($fieldset);

		$single = $this->requireModule(InputfieldCheckbox::class);
		$single->attr('name', 'email_updates');
		$single->label = $this->_('InputfieldCheckbox');
		$single->description = $this->_('A single checkbox with separate values for its checked and unchecked states.');
		$single->label2 = $this->_('Receive email updates');
		$single->checkedValue = 'subscribed';
		$single->uncheckedValue = 'not_subscribed';
		$single->checked(true);
		$single->columnWidth = 50;
		$fieldset->add($single);

		$multiple = $this->requireModule(InputfieldCheckboxes::class);
		$multiple->attr('name', 'notification_channels');
		$multiple->label = $this->_('InputfieldCheckboxes');
		$multiple->description = $this->_('Extends InputfieldSelectMultiple and allows selecting any number of options.');
		$multiple->addOptions([
			'email' => $this->_('Email'),
			'browser' => $this->_('Browser'),
			'mobile' => $this->_('Mobile'),
		]);
		$multiple->optionWidth = '12em';
		$multiple->columnWidth = 50;
		$fieldset->add($multiple);

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Radios');
		$fieldset->icon = 'dot-circle-o';
		$form->add($fieldset);

		$radios = $this->requireModule(InputfieldRadios::class);
		$radios->attr('name', 'interface_density');
		$radios->label = $this->_('InputfieldRadios: fixed option width');
		$radios->description = $this->_('Extends InputfieldSelect with fixed-width, auto-wrapping options.');
		$radios->addOptions([
			'compact' => $this->_('Compact'),
			'comfortable' => $this->_('Comfortable'),
			'spacious' => $this->_('Spacious'),
			'automatic' => $this->_('Automatic'),
		]);
		$radios->optionWidth = '14em';
		$radios->val('comfortable');
		$radios->columnWidth = 50;
		$fieldset->add($radios);

		$radioColumns = $this->requireModule(InputfieldRadios::class);
		$radioColumns->attr('name', 'delivery_speed');
		$radioColumns->label = $this->_('InputfieldRadios: equal columns');
		$radioColumns->description = $this->_('Extends InputfieldSelect and arranges options in equal-width columns.');
		$radioColumns->addOptions([
			'standard' => $this->_('Standard'),
			'express' => $this->_('Express'),
			'pickup' => $this->_('Pickup'),
		]);
		$radioColumns->optionColumns = 3;
		$radioColumns->val('standard');
		$radioColumns->columnWidth = 50;
		$fieldset->add($radioColumns);

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Toggles');
		$fieldset->icon = 'toggle-on';
		$form->add($fieldset);

		$toggle = $this->requireModule(InputfieldToggle::class);
		$toggle->attr('name', 'feature_status');
		$toggle->label = $this->_('InputfieldToggle: enabled state');
		$toggle->description = $this->_('Extends Inputfield with built-in Enabled and Disabled states.');
		$toggle->labelType = InputfieldToggle::labelTypeEnabled;
		$toggle->defaultOption = 'yes';
		$toggle->columnWidth = 50;
		$fieldset->add($toggle);

		$toggleCustom = $this->requireModule(InputfieldToggle::class);
		$toggleCustom->attr('name', 'approval_status');
		$toggleCustom->label = $this->_('InputfieldToggle: custom third state');
		$toggleCustom->description = $this->_('Extends Inputfield with custom labels and an optional third state.');
		$toggleCustom->labelType = InputfieldToggle::labelTypeCustom;
		$toggleCustom->yesLabel = $this->_('Approved');
		$toggleCustom->noLabel = $this->_('Rejected');
		$toggleCustom->otherLabel = $this->_('Pending');
		$toggleCustom->useOther = true;
		$toggleCustom->defaultOption = 'other';
		$toggleCustom->columnWidth = 50;
		$fieldset->add($toggleCustom);

		$submit = $this->requireModule(InputfieldSubmit::class);
		$submit->attr('name', 'submit_choices');
		$submit->val($this->_('Show processed values'));
		$submit->icon = 'check';
		$submit->notes = $this->_('InputfieldSubmit: processes this form and displays the normalized values above it.');
		$submit->showInHeader(true);
		$form->add($submit);

		$resultOut = '';
		if ($form->isSubmitted('submit_choices') && $form->process()) {
			$values = [
				'InputfieldCheckbox: email_updates' => $this->formatSubmittedValue($single->val()),
				'InputfieldCheckboxes: notification_channels' => $this->formatSubmittedValue($multiple->val()),
				'InputfieldRadios: interface_density' => $this->formatSubmittedValue($radios->val()),
				'InputfieldRadios: delivery_speed' => $this->formatSubmittedValue($radioColumns->val()),
				'InputfieldToggle: feature_status' => $this->formatSubmittedValue($toggle->val()),
				'InputfieldToggle: approval_status' => $this->formatSubmittedValue($toggleCustom->val()),
			];
			$table = $this->requireModule(MarkupAdminDataTable::class);
			$table->setEncodeEntities(true);
			$table->headerRow([$this->_('Inputfield'), $this->_('Processed value')]);
			foreach ($values as $inputfieldClass => $value) {
				$displayValue = $value !== '' ? $value : $this->_('(none)');
				$table->row([$inputfieldClass, $displayValue]);
			}
			$resultOut = '<h2>' . $this->_('Processed values') . '</h2>' . $table->render();
		}

		return $resultOut . $form->render();
	}

	/* ---------------------------------------------------------------------
	 * PageList states
	 * ------------------------------------------------------------------- */

	/**
	 * Render stable PageList state specimens and the live page picker
	 *
	 * @return string
	 */
	public function ___executePagelist()
	{
		// MP: static PageList specimens need the core stylesheet for inline actions and tree layout.
		$this->wire()->config->styles->add($this->wire()->config->urls->modules . 'Process/ProcessPageList/ProcessPageList.css');
		$this->headline($this->_('PageList states'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$states = [
			['class' => 'PageListHasChildren PageListItemOpen', 'icon' => 'home', 'title' => $this->_('Open page with children'), 'note' => $this->_('3 children'), 'count' => '3'],
			['class' => 'PageListHasChildren', 'icon' => 'folder', 'title' => $this->_('Closed page with children'), 'note' => $this->_('12 children'), 'count' => '12'],
			['class' => 'PageListNoChildren', 'icon' => 'file-o', 'title' => $this->_('Published leaf page'), 'note' => $this->_('Modified recently'), 'count' => ''],
			['class' => 'PageListNoChildren PageListStatusHidden secondary', 'icon' => 'eye-slash', 'title' => $this->_('Hidden page'), 'note' => $this->_('Hidden from lists'), 'count' => ''],
			['class' => 'PageListNoChildren PageListStatusUnpublished secondary', 'icon' => 'ban', 'title' => $this->_('Unpublished page'), 'note' => $this->_('Draft'), 'count' => ''],
			['class' => 'PageListNoChildren PageListStatusLocked', 'icon' => 'lock', 'title' => $this->_('Locked page'), 'note' => $this->_('Editing restricted'), 'count' => ''],
		];

		$out = '<p>' . $this->_('Stable visual specimens for states that are otherwise transient or difficult to reproduce in the live tree.') . '</p>';
		$out .= '<div class="PageListRoot"><div class="PageList">';
		foreach ($states as $index => $state) {
			$out .= '<div class="PageListItem PageListID' . (9000 + $index) . ' ' . $state['class'] . '">';
			$out .= '<a class="PageListPage label" href="#"><i class="icon fa fa-' . $state['icon'] . '"></i><span>' . htmlspecialchars($state['title']) . '</span></a>';
			if ($state['count'] !== '') $out .= '<span class="PageListNumChildren">' . $state['count'] . '</span>';
			$out .= '<span class="PageListNote detail">' . htmlspecialchars($state['note']) . '</span>';
			$out .= '<ul class="PageListActions actions">';
			$out .= '<li class="PageListActionEdit"><a href="#">' . $this->_('Edit') . '</a></li>';
			$out .= '<li class="PageListActionView"><a href="#">' . $this->_('View') . '</a></li>';
			$out .= '<li class="PageListActionExtras"><a href="#">' . $this->_('More') . '</a></li>';
			$out .= '</ul>';
			$out .= '</div>';
			if ($index === 0) {
				$out .= '<div class="PageList"><div class="PageListItem PageListNoChildren"><a class="PageListPage label" href="#"><i class="icon fa fa-file-o"></i><span>' . $this->_('Nested child page') . '</span></a></div></div>';
			}
		}
		$out .= '<div class="PageListItem PageListHasChildren PageListItemOpen"><span class="PageListLoading"><i class="fa fa-spin fa-spinner"></i></span><a class="PageListPage label" href="#"><i class="icon fa fa-folder-open"></i><span>' . $this->_('Loading children') . '</span></a></div>';
		$out .= '<div class="PageListPlaceholder"><div class="PageListPlaceholderItem PageListSortPlaceholder">' . $this->_('Drag-and-drop placeholder') . '</div></div>';
		$out .= '</div></div>';

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Notices and dialogs
	 * ------------------------------------------------------------------- */

	/**
	 * Render ProcessWire notices and theme-neutral dialog adapters
	 *
	 * @return string
	 */
	public function ___executeFeedback()
	{
		$input = $this->input;
		$notice = $input->get->name('notice');

		$this->headline($this->_('Notices and dialogs'));
		$this->breadcrumb('../', $this->_('Showcase'));

		if ($notice === 'message') {
			$this->message($this->_('The operation completed successfully.'));
		} elseif ($notice === 'warning') {
			$this->warning($this->_('Review these settings before continuing.'));
		} elseif ($notice === 'error') {
			$this->error($this->_('The operation could not be completed.'));
		} elseif ($notice === 'all') {
			$this->message($this->_('Example message notice.'));
			$this->warning($this->_('Example warning notice.'));
			$this->warning($this->_('Additional warning detail.')); // MP: exercise the native grouped-notice toggle
			$this->error($this->_('Example error notice.'));
		}

		$this->requireModule(JqueryUI::class)->use('modal');

		$out = '<h2>' . $this->_('Server notices') . '</h2>';
		$out .= '<p>' . $this->_('Each action reloads this page through the normal Process notice lifecycle.') . '</p>';
		$out .= '<div class="uk-button-group">';
		$out .= '<a class="ui-button ui-state-default" href="?notice=message"><i class="fa fa-check"></i> ' . $this->_('Message') . '</a>';
		$out .= '<a class="ui-button ui-state-default" href="?notice=warning"><i class="fa fa-exclamation-triangle"></i> ' . $this->_('Warning') . '</a>';
		$out .= '<a class="ui-button ui-state-default" href="?notice=error"><i class="fa fa-times-circle"></i> ' . $this->_('Error') . '</a>';
		$out .= '<a class="ui-button ui-state-default" href="?notice=all"><i class="fa fa-list"></i> ' . $this->_('All notices') . '</a>';
		$out .= '</div>';

		$out .= '<h2 class="uk-margin-large-top">' . $this->_('Dialogs') . '</h2>';
		$out .= '<div class="uk-button-group">';
		$out .= '<button type="button" id="ShowcaseAlert" class="ui-button ui-state-default"><i class="fa fa-info-circle"></i> ' . $this->_('Alert') . '</button>';
		$out .= '<button type="button" id="ShowcaseConfirm" class="ui-button ui-state-default"><i class="fa fa-question-circle"></i> ' . $this->_('Confirm') . '</button>';
		$out .= '<a class="ui-button ui-state-default pw-modal pw-modal-medium" href="../cards/?modal=1"><i class="fa fa-window-maximize"></i> ' . $this->_('Iframe modal') . '</a>';
		$out .= '</div>';

		$out .= <<<'SCRIPT'
<script>
jQuery(function($) {
	$('#ShowcaseAlert').on('click', function() {
		ProcessWire.alert('This alert uses the active admin theme dialog adapter.');
	});
	$('#ShowcaseConfirm').on('click', function() {
		ProcessWire.confirm({
			message: 'Continue with this showcase action?',
			labelOk: 'Continue',
			funcOk: function() {
				ProcessWire.alert('Confirmed.');
			}
		});
	});
});
</script>
SCRIPT;

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Buttons, button groups, dropdown menus
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase button hierarchy, grouped controls, and submit action menus.
	 *
	 * @return string
	 */
	public function ___executeActions()
	{
		$input   = $this->input;
		$session = $this->session;

		$this->headline($this->_('Buttons & action menus'));
		$this->breadcrumb('../', $this->_('Showcase'));

		// Process form first so we can show the result above the next form.
		$resultOut = '';
		if ($input->requestMethod('post')) {
			$session->CSRF()->validate();
			$action = $this->sanitizer->text($this->formatSubmittedValue($input->post('_action_value'))); // dropdown action button populates this
			$submittedValue = $input->post('submit_actions');
			if (!$submittedValue) $submittedValue = $input->post('submit_save');
			if ($submittedValue || $action) {
				$processedAction = $action !== '' ? $action : $this->sanitizer->text($this->formatSubmittedValue($submittedValue));
				$table = $this->requireModule(MarkupAdminDataTable::class);
				$table->setEncodeEntities(true);
				$table->headerRow([$this->_('Inputfield'), $this->_('Processed value')]);
				$table->row([$this->_('Submitted action'), $processedAction]);
				$resultOut = '<h2>' . $this->_('Processed values') . '</h2>' . $table->render();
			}
		}

		$form = $this->requireModule(InputfieldForm::class);
		$form->attr('method', 'post');
		$form->attr('action', './');

		/* --- 1. Button hierarchy --- */

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Button hierarchy');
		$fieldset->icon = 'mouse-pointer';
		$form->add($fieldset);

		$secondary = $this->requireModule(InputfieldButton::class);
		$secondary->val($this->_('Secondary action'));
		$secondary->icon = 'adjust';
		$secondary->setSecondary();

		$small = $this->requireModule(InputfieldButton::class);
		$small->val($this->_('Small utility action'));
		$small->icon = 'wrench';
		$small->setSmall();

		$disabled = $this->requireModule(InputfieldButton::class);
		$disabled->val($this->_('Unavailable action'));
		$disabled->icon = 'ban';
		$disabled->attr('disabled', 'disabled');

		$iconButton = $this->requireModule(InputfieldButton::class);
		$iconButton->val($this->_('Refresh data'));
		$iconButton->icon = 'refresh';

		$linkButton = $this->requireModule(InputfieldButton::class);
		// $linkButton->val($this->_('Open form fields')); // MP: Form fields renamed to Basic inputs
		$linkButton->val($this->_('Open basic inputs'));
		$linkButton->icon = 'external-link';
		$linkButton->href = '../forms/';

		$enhancedSubmit = $this->requireModule(InputfieldSubmit::class);
		$enhancedSubmit->attr('name', 'submit_actions');
		$enhancedSubmit->val($this->_('Save with menu'));
		$enhancedSubmit->icon = 'check';
		$enhancedSubmit->addActionValue('save_exit', $this->_('Save + Exit'), 'sign-out');
		$enhancedSubmit->addActionValue('save_continue', $this->_('Save + Continue'), 'arrow-right');
		$enhancedSubmit->addActionValue('save_copy', $this->_('Save + Duplicate'), 'copy');
		$enhancedSubmit->addActionLink('../', $this->_('Return to dashboard'), 'tachometer');

		$buttonExamples = [
			[$enhancedSubmit->render(), $this->_('Submits form — enhanced'), $this->_('InputfieldSubmit with dropdown actions can submit alternate values or open a direct link.')],
			[$secondary->render(), $this->_('Does not submit — secondary'), $this->_('InputfieldButton with setSecondary() gives a supporting action less emphasis.')],
			[$small->render(), $this->_('Does not submit — small'), $this->_('InputfieldButton with setSmall() creates a compact utility control.')],
			[$disabled->render(), $this->_('Does not submit — unavailable'), $this->_('A disabled InputfieldButton cannot be activated.')],
			[$iconButton->render(), $this->_('Does not submit — icon and text'), $this->_('InputfieldButton can trigger client-side behavior such as refreshing data.')],
			[$linkButton->render(), $this->_('Navigates — link button'), $this->_('InputfieldButton with href opens another page rather than submitting the form.')],
		];
		$buttonMarkup = '<div class="uk-grid-small uk-child-width-1-2@s uk-child-width-1-3@m" uk-grid>';
		foreach ($buttonExamples as [$button, $label, $description]) {
			$buttonMarkup .=
				'<div>' . $button .
				'<div class="uk-text-meta uk-margin-small-top"><strong>' . $label . '</strong><br>' . $description . '</div>' .
				'</div>';
		}
		$buttonMarkup .= '</div>';

		$markup = $this->requireModule(InputfieldMarkup::class);
		$markup->val($buttonMarkup);
		$fieldset->add($markup);

		/* --- 2. Grouped controls --- */

		$fieldset = $this->requireModule(InputfieldFieldset::class);
		$fieldset->label = $this->_('Grouped controls');
		$fieldset->icon = 'object-group';
		$form->add($fieldset);

		$toggle = $this->requireModule(InputfieldToggle::class);
		$toggle->attr('name', 'view_mode');
		$toggle->label = $this->_('InputfieldToggle: view mode');
		$toggle->description = $this->_('A single-choice button group. Select an option to see the value this Inputfield would submit.');
		$toggle->addOption(1, 'icon-th-large ' . $this->_('Grid'));
		$toggle->addOption(2, 'icon-list ' . $this->_('List'));
		$toggle->addOption(3, 'icon-table ' . $this->_('Table'));
		$toggle->val('1');
		$fieldset->add($toggle);

		$selectionStatus = $this->requireModule(InputfieldMarkup::class);
		$selectionStatus->val(
			'<p id="ShowcaseViewModeStatus" class="uk-text-meta uk-margin-small-top" aria-live="polite">' .
			$this->_('Current InputfieldToggle selection:') . ' <strong id="ShowcaseViewModeLabel">' . $this->_('Grid') . '</strong> ' .
			'(' . $this->_('submitted value:') . ' <code id="ShowcaseViewModeValue">1</code>)' .
			'</p>'
		);
		$fieldset->add($selectionStatus);

		$markup = $this->requireModule(InputfieldMarkup::class);
		$markup->label = $this->_('Compact navigation group');
		$markup->description = $this->_('UIkit groups related links when they navigate rather than submit a form value.');
		$markup->val(
			'<div class="uk-button-group">' .
				'<a class="ui-button ui-state-default" href="../" aria-label="' . $this->_('Dashboard') . '" title="' . $this->_('Dashboard') . '"><i class="fa fa-fw fa-tachometer"></i><span class="uk-visible@s"> ' . $this->_('Dashboard') . '</span></a>' .
				// MP: Form fields renamed to Basic inputs.
				'<a class="ui-button ui-state-default" href="../forms/" aria-label="' . $this->_('Basic inputs') . '" title="' . $this->_('Basic inputs') . '"><i class="fa fa-fw fa-list-alt"></i><span class="uk-visible@s"> ' . $this->_('Basic inputs') . '</span></a>' .
				'<a class="ui-button ui-state-default" href="./" aria-label="' . $this->_('Refresh') . '" title="' . $this->_('Refresh') . '"><i class="fa fa-fw fa-refresh"></i><span class="uk-visible@s"> ' . $this->_('Refresh') . '</span></a>' .
				'</div>'
		);
		$fieldset->add($markup);

		/* --- Form action --- */

		$save = $this->requireModule(InputfieldSubmit::class);
		$save->attr('name', 'submit_save');
		$save->val($this->_('Save'));
		$save->icon = 'check';
		$form->add($save);

		$out = $resultOut . $form->render();
		$out .= <<<'SCRIPT'
<script>
jQuery(function($) {
	var $toggle = $('#wrap_Inputfield_view_mode');
	var $status = $('#ShowcaseViewModeStatus');
	if(!$toggle.length || !$status.length) return;

	function updateViewModeStatus() {
		var $input = $toggle.find('input[name="view_mode"]:checked');
		if(!$input.length) return;
		var label = $toggle.find('label[for="' + $input.attr('id') + '"]').text().trim();
		$('#ShowcaseViewModeLabel').text(label);
		$('#ShowcaseViewModeValue').text($input.val());
	}

	$toggle.on('change', 'input[name="view_mode"]', updateViewModeStatus);
	updateViewModeStatus();
});
</script>
SCRIPT;

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * "Repeater" pattern
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase native InputfieldRepeater and the repeater pattern for Process modules.
	 *
	 * The native regression fixture uses an existing Repeater Field/Page.
	 * The standalone example uses numbered rows because Process forms do
	 * not otherwise have the schema required by InputfieldRepeater.
	 *
	 * @return string
	 */
	public function ___executeRepeater()
	{

		$input     = $this->input;
		$session   = $this->session;
		$sanitizer = $this->sanitizer;

		$this->headline($this->_('Repeaters'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$nativeRepeaterOut = $this->renderNativeRepeaterTestCase();

		$maxRows = max(1, (int) $this->maxItems);

		// jQuery UI provides .sortable() used by the JS below.
		$this->requireModule(JqueryUI::class);

		// Default example rows shown on first load.
		$rows = [
			['title' => $this->_('Refactor caching layer'), 'type' => 'chore',   'priority' => 3],
			['title' => $this->_('Login redirect bug'),     'type' => 'bug',     'priority' => 8],
			['title' => $this->_('Two-factor auth'),        'type' => 'feature', 'priority' => 5],
		];

		$resultOut = '';

		// Process POST: read $_POST['rep'] as an indexed array of rows.
		// Inputfield::processInput() is not used here because the rows are
		// rendered as plain HTML with bracketed names (rep[N][field]).
		if ($input->requestMethod('post') && $input->post('submit_repeater')) {
			$session->CSRF()->validate();
			$posted = $input->post('rep');
			$captured = [];
			if (is_array($posted)) {
				foreach ($posted as $row) {
					if (!is_array($row)) continue;
					$title    = trim($sanitizer->text($this->formatSubmittedValue($row['title'] ?? '')));
					$type     = $sanitizer->name($this->formatSubmittedValue($row['type'] ?? ''));
					$priority = $sanitizer->int($row['priority'] ?? 0);
					if ($title === '' && $type === '' && $priority === 0) continue;
					$captured[] = compact('title', 'type', 'priority');
				}
			}
			if ($captured) {
				$table = $this->requireModule(MarkupAdminDataTable::class);
				$table->setEncodeEntities(false);
				$table->headerRow([$this->_('#'), $this->_('Title'), $this->_('Type'), $this->_('Priority')]);
				foreach ($captured as $i => $r) {
					$table->row([
						(string) ($i + 1),
						$sanitizer->entities($r['title']),
						$sanitizer->entities($r['type']),
						(string) $r['priority'],
					]);
				}
				$resultOut .= '<h2>' . $this->_('Captured rows') . '</h2>' . $table->render();
				// Re-render the form with the submitted rows so the user
				// can continue editing where they left off.
				$rows = $captured;
			} else {
				$this->message($this->_('No rows submitted.'));
				$rows = [];
			}
		}

		// Build the rows HTML and the hidden <template> for cloning.
		$rowsHtml = '';
		foreach ($rows as $i => $r) $rowsHtml .= $this->renderRepeaterRow((string) $i, $r);
		$rowTemplate = $this->renderRepeaterRow('__INDEX__', ['title' => '', 'type' => '', 'priority' => '']);

		$form = $this->requireModule(InputfieldForm::class);
		$form->attr('method', 'post');
		$form->attr('id', 'ShowcaseRepeaterForm');
		// InputfieldForm::description is rendered as plain text (entity-encoded),
		// so HTML tags here would show through literally — use plain text only.
		$form->description = sprintf(
			$this->_('Click "Add row", drag the handle to reorder, or remove individual rows. Configurable limit: %d rows.'),
			$maxRows
		);

		$rowsMarkup = $this->requireModule(InputfieldMarkup::class);
		$rowsMarkup->label = $this->_('Items');
		$rowsMarkup->icon = 'list';
		$rowsMarkup->val(
			'<div id="ShowcaseRepeaterContainer" data-max="' . $maxRows . '">' . $rowsHtml . '</div>' .
				// <template> elements are inert containers; their innerHTML is
				// cloned into the live DOM by JS when the user clicks "Add row".
				'<template id="ShowcaseRepeaterTemplate">' . $rowTemplate . '</template>' .
				'<p class="uk-margin-small-top">' .
				'<button type="button" id="ShowcaseRepeaterAdd" class="ui-button ui-state-default">' .
				'<i class="fa fa-plus"></i> ' . $this->_('Add row') .
				'</button> ' .
				'<span id="ShowcaseRepeaterCount" class="uk-text-meta uk-margin-small-left"></span>' .
				'</p>'
		);
		$form->add($rowsMarkup);

		$submit = $this->requireModule(InputfieldSubmit::class);
		$submit->attr('name', 'submit_repeater');
		$submit->val($this->_('Save rows'));
		$submit->icon = 'check';
		$submit->showInHeader(true);
		$form->add($submit);

		// JS + CSS for add / remove / reorder. In production, move this to
		// a separate ProcessShowcase.js file and load it via $config->scripts.
		$js = <<<'JS'
<script>
jQuery(function($) {
	var $container = $('#ShowcaseRepeaterContainer');
	if(!$container.length) return;
	var $tpl   = $('#ShowcaseRepeaterTemplate');
	var $add   = $('#ShowcaseRepeaterAdd');
	var $count = $('#ShowcaseRepeaterCount');
	var max    = parseInt($container.attr('data-max'), 10) || 99;

	function reindex() {
		var $rows = $container.children('.ShowcaseRepeaterRow');
		$rows.each(function(i) {
			$(this).attr('data-index', i);
			$(this).find('.ShowcaseRepeaterIndex').text(i + 1);
			$(this).find('input, select, textarea').each(function() {
				var name = $(this).attr('name');
				if(!name) return;
				$(this).attr('name', name.replace(/rep\[\d+\]/, 'rep[' + i + ']'));
			});
		});
		var n = $rows.length;
		$count.text(n + ' / ' + max);
		$add.prop('disabled', n >= max);
	}

	$add.on('click', function() {
		var n = $container.children('.ShowcaseRepeaterRow').length;
		if(n >= max) return;
		// Clone the template's inner HTML and replace the placeholder index.
		var html = $tpl.prop('content')
			? $('<div></div>').append($($tpl.prop('content')).clone()).html()
			: $tpl.html();
		html = html.replace(/__INDEX__/g, String(n));
		$container.append(html);
		reindex();
	});

	$container.on('click', '.ShowcaseRepeaterRemove', function() {
		$(this).closest('.ShowcaseRepeaterRow').remove();
		reindex();
	});

	if($.fn.sortable) {
		$container.sortable({
			handle: '.ShowcaseRepeaterHandle',
			axis: 'y',
			placeholder: 'ShowcaseRepeaterPlaceholder',
			tolerance: 'pointer',
			update: reindex
		});
	}

	reindex();
});
</script>
<style>
	.ShowcaseRepeaterRow {
		background: #fff;
		border: 1px solid #e5e5e5;
		padding: .5em .75em;
		margin-left: 0;
		margin-bottom: .4em;
		border-radius: 3px;
	}
	.ShowcaseRepeaterRow .ShowcaseRepeaterHandle {
		cursor: move;
		color: #999;
		padding: 0 .35em;
	}
	.ShowcaseRepeaterRow .ShowcaseRepeaterIndex {
		min-width: 1.6em;
		text-align: center;
	}
	.ShowcaseRepeaterPlaceholder {
		background: #fff8e1;
		border: 1px dashed #ddd;
		height: 60px;
		margin-bottom: .4em;
		border-radius: 3px;
	}
</style>
JS;

		return $nativeRepeaterOut . $resultOut . $form->render() . $js;
	}

	/**
	 * Render deterministic native InputfieldRepeater label test cases.
	 *
	 * @return string
	 */
	protected function renderNativeRepeaterTestCase(): string
	{
		$fixture = $this->ensureNativeRepeaterFixture();
		if (!$fixture) {
			return '<div class="uk-alert-warning" uk-alert>' .
				$this->_('Unable to create the native InputfieldRepeater fixture.') .
				'</div>';
		}

		$ownerPage = $fixture['page'];

		$form = $this->requireModule(InputfieldForm::class);
		$form->attr('id', 'ShowcaseNativeRepeaterForm');
		foreach ($fixture['fields'] as $fieldName => $repeaterField) {
			$value = $ownerPage->getUnformatted($fieldName);
			$inputfield = $repeaterField->getInputfield($ownerPage);
			$inputfield->label = $repeaterField->label;
			$inputfield->description = $fieldName === self::REPEATER_FIXTURE_FIELD
				? $this->_('Native short, clipped, and icon labels. Hover the trash icon to preview deletion.')
				: $this->_('Native custom label background. Hover the trash icon to verify its preview state.');
			$inputfield->set('repeaterMaxItems', is_countable($value) ? count($value) : 0);
			$form->add($inputfield);
		}

		return $form->render();
	}

	/**
	 * Define the dedicated native Repeater fields and item labels.
	 *
	 * @return array<string, array{label: string, title: string, items: string[]}>
	 */
	protected function getNativeRepeaterFixtureSpecs(): array
	{
		$textToken = '{' . self::REPEATER_FIXTURE_TEXT_FIELD . '}';

		return [
			self::REPEATER_FIXTURE_FIELD => [
				'label' => $this->_('Native labels'),
				'title' => $textToken,
				'items' => [
					$this->_('Short label'),
					$this->_('Long clipped label with deliberately wide text: WWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWW'),
					$this->_('icon-star Label containing a native icon'),
				],
			],
			self::REPEATER_FIXTURE_GREEN_FIELD => [
				'label' => $this->_('Custom green background'),
				'title' => "Long green #60a917 $textToken",
				'items' => [$this->_('label WWWWWWWWWWWWWWWWWWWW WWWWWWWWWWWWWWWWWWWW WWWWWWWWWWWWWWWWWWWW WWWWWWWWWWWWWWWWWWWW')],
			],
			self::REPEATER_FIXTURE_LIGHT_FIELD => [
				'label' => $this->_('Light custom background'),
				'title' => "$textToken #f5f5f5",
				'items' => [$this->_('Light custom background label')],
			],
			self::REPEATER_FIXTURE_DARK_FIELD => [
				'label' => $this->_('Dark custom background'),
				'title' => "$textToken #202020",
				'items' => [$this->_('Dark custom background label')],
			],
		];
	}

	/**
	 * Ensure the dedicated native Repeater fixture exists and has six items.
	 *
	 * @return array{fields: array<string, Field>, page: Page}|null
	 */
	protected function ensureNativeRepeaterFixture(): ?array
	{
		$fieldtype = $this->modules->get('FieldtypeRepeater');
		$textFieldtype = $this->modules->get('FieldtypeText');
		if (!$fieldtype instanceof FieldtypeRepeater || !$textFieldtype instanceof FieldtypeText) return null;

		$textField = $this->fields->get(self::REPEATER_FIXTURE_TEXT_FIELD);
		if (!$textField instanceof Field) {
			$textField = $this->fields->new($textFieldtype, self::REPEATER_FIXTURE_TEXT_FIELD, [
				'label' => $this->_('Showcase Repeater item title'),
			]);
		}

		$fieldtype->getFieldClass();
		$fixtureSpecs = $this->getNativeRepeaterFixtureSpecs();
		$repeaterFields = [];
		foreach ($fixtureSpecs as $fieldName => $spec) {
			$repeaterField = $this->fields->get($fieldName);
			if (!$repeaterField instanceof Field) {
				$repeaterField = $this->fields->new($fieldtype, $fieldName, [
					'label' => $spec['label'],
					'repeaterTitle' => $spec['title'],
				]);
			}
			$repeaterField->label = $spec['label'];
			$repeaterField->set('repeaterTitle', $spec['title']);
			$repeaterField->save();

			$repeaterTemplate = $fieldtype->_getRepeaterTemplate($repeaterField);
			$repeaterFieldgroup = $repeaterTemplate->fieldgroup;
			if (!$repeaterFieldgroup instanceof Fieldgroup) return null;
			if (!$repeaterFieldgroup->hasField($textField)) {
				$repeaterFieldgroup->add($textField);
				$repeaterFieldgroup->save();
			}
			$repeaterField->set('repeaterFields', [$textField->id]);
			$repeaterField->save();
			$repeaterFields[$fieldName] = $repeaterField;
		}

		$template = $this->templates->get(self::REPEATER_FIXTURE_TEMPLATE);
		if (!$template instanceof Template) {
			// $template = $this->templates->new(self::REPEATER_FIXTURE_TEMPLATE, ['fields' => array_values($repeaterFields), 'noChildren' => 1, 'noParents' => 1]); // MP: save first so the template has a fieldgroup
			$template = $this->templates->new(self::REPEATER_FIXTURE_TEMPLATE);
			$template->noChildren = 1;
			$template->noParents = 1;
			$template->save();
		} elseif (!$template->fieldgroup) {
			$template->save();
		}
		$fieldgroup = $template->fieldgroup;
		if (!$fieldgroup instanceof Fieldgroup) return null;
		$fieldgroupChanged = false;
		foreach ($repeaterFields as $repeaterField) {
			if ($fieldgroup->hasField($repeaterField)) continue;
			$fieldgroup->add($repeaterField);
			$fieldgroupChanged = true;
		}
		if ($fieldgroupChanged) $fieldgroup->save();

		$page = $this->pages->get('include=all, template=' . self::REPEATER_FIXTURE_TEMPLATE . ', name=' . self::REPEATER_FIXTURE_PAGE);
		if (!$page->id) {
			$page = $this->pages->new([
				'template' => $template,
				'parent' => 1,
				'name' => self::REPEATER_FIXTURE_PAGE,
				'status' => Page::statusHidden | Page::statusUnpublished,
			]);
		}

		foreach ($fixtureSpecs as $fieldName => $spec) {
			$this->ensureNativeRepeaterFixtureItems($page, $repeaterFields[$fieldName], $spec['items']);
		}

		return ['fields' => $repeaterFields, 'page' => $page];
	}

	/**
	 * Normalize persisted items for one dedicated native Repeater field.
	 *
	 * @param Page $page
	 * @param Field $repeaterField
	 * @param string[] $seedLabels
	 * @return void
	 */
	protected function ensureNativeRepeaterFixtureItems(Page $page, Field $repeaterField, array $seedLabels): void
	{
		$page->of(false);
		$items = $page->get($repeaterField->name);
		if (!$items instanceof RepeaterPageArray) return;
		$fixtureItems = [];
		foreach ($items as $item) {
			if (!$item instanceof RepeaterPage) continue;
			if ($item->isHidden() && $item->isUnpublished()) continue;
			$fixtureItems[] = $item;
		}
		while (count($fixtureItems) > count($seedLabels)) {
			$items->remove(array_pop($fixtureItems));
			$page->save($repeaterField->name);
		}
		foreach ($seedLabels as $itemIndex => $seedLabel) {
			$item = $fixtureItems[$itemIndex] ?? $items->getNewItem();
			if (!$item instanceof RepeaterPage) continue;
			if ($item->get(self::REPEATER_FIXTURE_TEXT_FIELD) === $seedLabel) continue;
			$item->set(self::REPEATER_FIXTURE_TEXT_FIELD, $seedLabel);
			$item->save();
			$page->save($repeaterField->name);
		}
	}

	/**
	 * Render a single repeater row as raw HTML.
	 *
	 * Used both for initial rendering of stored rows and to seed the
	 * hidden <template> that the JS clones for new rows. Pass the literal
	 * placeholder `__INDEX__` as $idx for the template version.
	 *
	 * @param string $idx Numeric index or the placeholder `__INDEX__`
	 * @param array{title?: string, type?: string, priority?: int|string} $values Row values
	 * @return string
	 */
	protected function renderRepeaterRow(string $idx, array $values): string
	{
		$sanitizer = $this->sanitizer;

		$title    = (string) ($values['title']    ?? '');
		$type     = (string) ($values['type']     ?? '');
		$priority = (string) ($values['priority'] ?? '');

		$typeOpts = [
			''        => '—',
			'feature' => $this->_('Feature'),
			'bug'     => $this->_('Bug'),
			'chore'   => $this->_('Chore'),
		];
		$opts = '';
		foreach ($typeOpts as $k => $v) {
			$sel = ($k === $type) ? ' selected' : '';
			$opts .= '<option value="' . $sanitizer->entities($k) . '"' . $sel . '>' . $sanitizer->entities($v) . '</option>';
		}

		return
			'<div class="ShowcaseRepeaterRow uk-grid-small uk-flex-middle" uk-grid data-index="' . $idx . '">' .
			'<div class="uk-width-auto">' .
			'<span class="ShowcaseRepeaterHandle" title="' . $this->_('Drag to reorder') . '">' .
			'<i class="fa fa-bars"></i>' .
			'</span>' .
			'<span class="ShowcaseRepeaterIndex uk-label"></span>' .
			'</div>' .
			'<div class="uk-width-expand">' .
			'<input type="text" class="uk-input" name="rep[' . $idx . '][title]" ' .
			'placeholder="' . $this->_('Title') . '" ' .
			'value="' . $sanitizer->entities($title) . '">' .
			'</div>' .
			'<div class="uk-width-1-6">' .
			'<select class="uk-select" name="rep[' . $idx . '][type]">' . $opts . '</select>' .
			'</div>' .
			'<div class="uk-width-1-6">' .
			'<input type="number" class="uk-input" name="rep[' . $idx . '][priority]" ' .
			'min="0" max="9" placeholder="' . $this->_('Priority') . '" ' .
			'value="' . $sanitizer->entities($priority) . '">' .
			'</div>' .
			'<div class="uk-width-auto">' .
			'<button type="button" class="ShowcaseRepeaterRemove ui-button ui-state-default ui-priority-secondary" ' .
			'title="' . $this->_('Remove') . '">' .
			'<i class="fa fa-trash-o"></i>' .
			'</button>' .
			'</div>' .
			'</div>';
	}

	/* ---------------------------------------------------------------------
	 * Tables
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase MarkupAdminDataTable
	 *
	 * @return string
	 */
	public function ___executeTables()
	{
		$this->headline($this->_('Tables'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$rows = $this->getDemoData();

		$table = $this->requireModule(MarkupAdminDataTable::class);
		$table->setEncodeEntities(false);
		$table->setSortable(true);
		$table->headerRow([
			$this->_('Name'),
			$this->_('Role'),
			$this->_('Status'),
			$this->_('Created'),
			$this->_('Actions'),
		]);

		foreach ($rows as $r) {
			if ($r['status'] === 'active') {
				$status = '<span class="uk-label uk-label-success">' . $this->_('Active') . '</span>';
			} elseif ($r['status'] === 'pending') {
				$status = '<span class="uk-label uk-label-warning">' . $this->_('Pending') . '</span>';
			} else {
				$status = '<span class="uk-label uk-label-danger">' . $this->_('Archived') . '</span>';
			}

			// Per-row actions: a small button-group of inline icons.
			$actions =
				'<div class="uk-button-group">' .
				'<a class="ui-button ui-state-default" href="#" title="' . $this->_('Edit')   . '"><i class="fa fa-pencil"></i></a>' .
				'<a class="ui-button ui-state-default" href="#" title="' . $this->_('Clone')  . '"><i class="fa fa-copy"></i></a>' .
				'<a class="ui-button ui-state-default" href="#" title="' . $this->_('Delete') . '"><i class="fa fa-trash-o"></i></a>' .
				'</div>';

			$table->row([
				'<strong>' . htmlspecialchars($r['name']) . '</strong>',
				htmlspecialchars($r['role']),
				$status,
				$this->datetime->date('Y-m-d', $r['created']),
				$actions,
			]);
		}

		$out = $table->render();

		// A second, more compact look using plain UIkit table classes.
		$out .= '<h2 class="uk-margin-large-top">' . $this->_('Striped UIkit table') . '</h2>';
		$out .= '<table class="uk-table uk-table-striped uk-table-small uk-table-divider">';
		$out .= '<thead><tr><th>' . $this->_('Name') . '</th><th>' . $this->_('Role') . '</th><th class="uk-text-right">' . $this->_('Score') . '</th></tr></thead><tbody>';
		foreach ($rows as $r) {
			$out .=
				'<tr>' .
				'<td>' . htmlspecialchars($r['name']) . '</td>' .
				'<td>' . htmlspecialchars($r['role']) . '</td>' .
				'<td class="uk-text-right">' . (int) $r['score'] . '</td>' .
				'</tr>';
		}
		$out .= '</tbody></table>';

		// 3. Drag-to-reorder sortable table (jQuery UI sortable on <tbody>).
		// MarkupAdminDataTable::setSortable() above already enables the
		// header-click column sorter. This third table demonstrates the
		// other common pattern: row reordering via a drag handle. In a
		// real module you'd POST the new order to a saveOrder endpoint;
		// here we just display the new order in a notice for clarity.
		// Ensure jQuery UI (which provides .sortable()) is loaded.
		// In AdminThemeUikit it is loaded by default, but this is the
		// canonical way to require it from within a Process module.
		$this->requireModule(JqueryUI::class);

		$out .= '<h2 class="uk-margin-large-top">' . $this->_('Drag to reorder rows') . '</h2>';
		$out .= '<p class="uk-text-meta">' .
			$this->_('Grab a row by its handle in the first column to reorder. The new order is shown in a notice — wire it to an AJAX endpoint to persist.') .
			'</p>';

		$out .= '<table class="uk-table uk-table-divider uk-table-small uk-table-middle ShowcaseSortableTable">';
		$out .= '<thead><tr>' .
			'<th class="uk-table-shrink"></th>' .
			'<th>' . $this->_('Name')   . '</th>' .
			'<th>' . $this->_('Role')   . '</th>' .
			'<th class="uk-text-right">' . $this->_('Score') . '</th>' .
			'</tr></thead><tbody>';
		foreach ($rows as $r) {
			$id = $this->sanitizer->pageName($r['name']);
			$out .=
				'<tr data-id="' . $this->sanitizer->entities($id) . '">' .
				'<td class="sortable-handle" style="cursor: move;" title="' . $this->_('Drag to reorder') . '">' .
				'<i class="fa fa-bars uk-text-muted"></i>' .
				'</td>' .
				'<td>' . htmlspecialchars($r['name']) . '</td>' .
				'<td>' . htmlspecialchars($r['role']) . '</td>' .
				'<td class="uk-text-right">' . (int) $r['score'] . '</td>' .
				'</tr>';
		}
		$out .= '</tbody></table>';
		$out .= '<button type="button" class="ui-button ui-state-default" id="ShowcaseSaveOrder">' .
			'<i class="fa fa-save"></i> ' . $this->_('Save order') .
			'</button>';

		// Inline JS that wires up jQuery UI sortable on the <tbody>.
		// In production code put this in a separate ProcessShowcase.js file
		// and load it via $config->scripts->add(...).
		$out .= <<<'JS'
<script>
jQuery(function($) {
	var $tbody = $('table.ShowcaseSortableTable tbody');
	if(!$tbody.length || !$tbody.sortable) return;
	$tbody.sortable({
		items: '> tr',
		handle: '.sortable-handle',
		axis: 'y',
		helper: function(e, tr) {
			// preserve cell widths while dragging
			var $orig = $(tr);
			var $helper = $orig.clone();
			$helper.children().each(function(i) {
				$(this).width($orig.children().eq(i).width());
			});
			return $helper;
		},
		update: function() {
			$tbody.find('tr').each(function(i) {
				$(this).find('td').first()
					.find('.order-index').remove();
				$(this).find('td').first()
					.append(' <small class="order-index uk-text-muted">' + (i + 1) + '</small>');
			});
		}
	});
	$('#ShowcaseSaveOrder').on('click', function() {
		var order = $tbody.find('tr').map(function() {
			return $(this).attr('data-id');
		}).get();
		ProcessWire.alert('New order: ' + order.join(', '));
		// In production:
		// $.post('./save-order/', { order: order, _csrf: ... });
	});
});
</script>
JS;

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Lists
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase list-style data rendering
	 *
	 * @return string
	 */
	public function ___executeLists()
	{
		$this->headline($this->_('Lists'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$rows = $this->getDemoData();

		// 1. Description list (key/value pairs)
		$out = '<h2>' . $this->_('Description list') . '</h2>';
		$out .= '<dl class="uk-description-list uk-description-list-divider">';
		foreach ($rows as $r) {
			$out .=
				'<dt>' . htmlspecialchars($r['name']) . '</dt>' .
				'<dd>' .
				'<span class="uk-text-muted">' . htmlspecialchars($r['role']) . '</span> · ' .
				htmlspecialchars($r['email']) .
				'</dd>';
		}
		$out .= '</dl>';

		// 2. UIkit list with badges/icons (item list)
		$out .= '<h2 class="uk-margin-large-top">' . $this->_('Item list (with status badges)') . '</h2>';
		$out .= '<ul class="uk-list uk-list-striped uk-list-large">';
		foreach ($rows as $r) {
			$badge = $r['status'] === 'active'
				? '<span class="uk-label uk-label-success">' . $this->_('Active')   . '</span>'
				: '<span class="uk-label">'                  . $this->_($r['status']) . '</span>';
			$out .=
				'<li>' .
				'<div class="uk-flex uk-flex-middle uk-flex-between">' .
				'<div>' .
				'<i class="fa fa-fw fa-' . $r['icon'] . ' uk-margin-small-right uk-text-muted"></i>' .
				'<a href="#" class="uk-link-reset"><strong>' . htmlspecialchars($r['name']) . '</strong></a>' .
				' <span class="uk-text-meta">— ' . htmlspecialchars($r['role']) . '</span>' .
				'</div>' .
				'<div>' . $badge . '</div>' .
				'</div>' .
				'</li>';
		}
		$out .= '</ul>';

		// 3. Nav-style list (interactive)
		$out .= '<h2 class="uk-margin-large-top">' . $this->_('Nav list') . '</h2>';
		$out .= '<ul class="uk-nav uk-nav-default">';
		$out .= '<li class="uk-nav-header">' . $this->_('Sections') . '</li>';
		foreach (['Dashboard' => 'tachometer', 'Forms' => 'list-alt', 'Tables' => 'table', 'Cards' => 'th'] as $label => $icon) {
			$out .= '<li><a href="#"><i class="fa fa-fw fa-' . $icon . ' uk-margin-small-right"></i>' . $label . '</a></li>';
		}
		$out .= '<li class="uk-nav-divider"></li>';
		$out .= '<li><a href="#"><i class="fa fa-fw fa-cog uk-margin-small-right"></i>' . $this->_('Settings') . '</a></li>';
		$out .= '</ul>';

		return $out;
	}

	/** Render the upstream HTML fixture inside the active admin theme. */
	public function ___executeHtmlElements(): string
	{
		$this->headline($this->_('HTML elements'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$html = file_get_contents(__DIR__ . '/fixtures/html5-test-page/index.html');
		if ($html === false || !preg_match('~<body\b[^>]*>(.*)</body\s*>~is', $html, $matches)) {
			throw new WireException('Unable to read the HTML5 test page body.');
		}

		// Preserve the upstream file; only extract its body and resolve local references.
		$moduleUrl = $this->wire()->config->urls->get('ProcessShowcase');
		if (!is_string($moduleUrl)) throw new WireException('Unable to resolve the Showcase URL.');
		$url = $moduleUrl . 'fixtures/html5-test-page/index.html';
		$url = $this->wire()->sanitizer->entities($url);
		$body = str_replace(
			['src="index.html"', 'data="index.html"'],
			['src="' . $url . '"', 'data="' . $url . '"'],
			$matches[1]
		);

		return '<div class="uk-card uk-card-default uk-card-body">' . $body . '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Cards
	 * ------------------------------------------------------------------- */

	/**
	 * Showcase card-grid data rendering
	 *
	 * @return string
	 */
	public function ___executeCards()
	{
		$this->headline($this->_('Cards'));
		$this->breadcrumb('../', $this->_('Showcase'));

		$rows = $this->getDemoData();

		$out = '<div class="uk-grid-small uk-child-width-1-2 uk-child-width-1-3@m uk-child-width-1-4@l" uk-grid>';

		foreach ($rows as $r) {
			if ($r['status'] === 'active') {
				$badgeClass = 'uk-label-success';
			} elseif ($r['status'] === 'pending') {
				$badgeClass = 'uk-label-warning';
			} else {
				$badgeClass = 'uk-label-danger';
			}

			$out .=
				'<div>' .
				'<div class="uk-card uk-card-default uk-card-small">' .
				'<div class="uk-card-header">' .
				'<div class="uk-grid-small uk-flex-middle" uk-grid>' .
				'<div class="uk-width-auto">' .
				'<span class="uk-icon uk-icon-link" uk-icon="icon: user; ratio: 1.4"></span>' .
				'</div>' .
				'<div class="uk-width-expand">' .
				'<h3 class="uk-card-title uk-margin-remove-bottom">' . htmlspecialchars($r['name']) . '</h3>' .
				'<p class="uk-text-meta uk-margin-remove-top">' . htmlspecialchars($r['role']) . '</p>' .
				'</div>' .
				'</div>' .
				'</div>' .
				'<div class="uk-card-body">' .
				'<p class="uk-margin-remove">' . htmlspecialchars($r['email']) . '</p>' .
				'<p class="uk-margin-small-top"><span class="uk-label ' . $badgeClass . '">' . $this->_(ucfirst($r['status'])) . '</span></p>' .
				'</div>' .
				'<div class="uk-card-footer">' .
				'<div class="uk-button-group">' .
				'<a class="ui-button ui-state-default" href="#"><i class="fa fa-pencil"></i> ' . $this->_('Edit') . '</a>' .
				'<a class="ui-button ui-state-default" href="#"><i class="fa fa-trash-o"></i></a>' .
				'</div>' .
				'</div>' .
				'</div>' .
				'</div>';
		}
		$out .= '</div>';

		/* --- Themed cards (default / primary / secondary) --- */

		$sanitizer = $this->sanitizer;

		$themed = [
			['style' => 'uk-card-default',   'icon' => 'file-text-o', 'title' => $this->_('Document card'),    'body' => $this->_('A standard card for document-like content.'),    'cta' => $this->_('Read more')],
			['style' => 'uk-card-primary',   'icon' => 'star',        'title' => $this->_('Primary card'),     'body' => $this->_('A primary-styled card for featured content.'),   'cta' => $this->_('Explore')],
			['style' => 'uk-card-secondary', 'icon' => 'cog',         'title' => $this->_('Secondary card'),   'body' => $this->_('A secondary-styled card for tools or settings.'), 'cta' => $this->_('Configure')],
			['style' => 'uk-card-default',   'icon' => 'users',       'title' => $this->_('Team card'),        'body' => $this->_('Manage team members and permissions.'),          'cta' => $this->_('Manage')],
			['style' => 'uk-card-default',   'icon' => 'bar-chart',   'title' => $this->_('Analytics card'),   'body' => $this->_('View traffic and conversion statistics.'),       'cta' => $this->_('View report')],
			['style' => 'uk-card-primary',   'icon' => 'bell',        'title' => $this->_('Notifications'),    'body' => $this->_('Review recent alerts and system messages.'),     'cta' => $this->_('View all')],
		];

		$out .= '<h2 class="uk-margin-large-top">' . $this->_('Themed cards') . '</h2>';
		$out .= '<div class="uk-child-width-1-3@m uk-grid-match" uk-grid>';
		foreach ($themed as $c) {
			$out .=
				'<div>' .
				'<div class="uk-card ' . $sanitizer->entities($c['style']) . '">' .
				'<div class="uk-card-header">' .
				'<div class="uk-grid-small uk-flex-middle" uk-grid>' .
				'<div class="uk-width-auto"><span uk-icon="icon: ' . $sanitizer->name($c['icon']) . '"></span></div>' .
				'<div class="uk-width-expand">' .
				'<h3 class="uk-card-title uk-margin-remove-bottom">' . $sanitizer->entities($c['title']) . '</h3>' .
				'</div>' .
				'</div>' .
				'</div>' .
				'<div class="uk-card-body"><p>' . $sanitizer->entities($c['body']) . '</p></div>' .
				'<div class="uk-card-footer">' .
				'<a href="#" class="uk-button uk-button-text">' . $sanitizer->entities($c['cta']) . '</a>' .
				'</div>' .
				'</div>' .
				'</div>';
		}
		$out .= '</div>';

		/* --- Media card (image at top) --- */

		// Inline SVG placeholder keeps the showcase fully self-contained
		// (no external image requests, no admin asset dependencies).
		$placeholder = 'data:image/svg+xml;utf8,'
			. rawurlencode(
				'<svg xmlns="http://www.w3.org/2000/svg" width="800" height="300" viewBox="0 0 800 300">'
					. '<rect width="800" height="300" fill="#e0e0e0"/>'
					. '<text x="400" y="160" text-anchor="middle" font-family="sans-serif" font-size="22" fill="#999">'
					. 'Image placeholder'
					. '</text></svg>'
			);

		$out .= '<h2 class="uk-margin-large-top">' . $this->_('Media card') . '</h2>';
		$out .=
			'<div class="uk-card uk-card-default uk-width-1-2@m">' .
			'<div class="uk-card-media-top">' .
			'<img src="' . $placeholder . '" alt="' . $sanitizer->entities($this->_('Placeholder')) . '">' .
			'</div>' .
			'<div class="uk-card-body">' .
			'<h3 class="uk-card-title">' . $this->_('Featured article') . '</h3>' .
			'<p>' . $this->_('A media card with an image at the top and content below — useful for articles, products, or portfolio entries.') . '</p>' .
			'</div>' .
			'<div class="uk-card-footer">' .
			'<a href="#" class="uk-button uk-button-text">' . $this->_('Read article') . '</a>' .
			'</div>' .
			'</div>';

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Get an installed module and preserve its concrete class for static analysis.
	 *
	 * @template T of Module
	 * @param class-string<T> $className
	 * @return T
	 */
	protected function requireModule(string $className): Module
	{
		$module = $this->modules->get($className);
		if (!$module instanceof $className) {
			throw new WireException("Required module $className is not available.");
		}
		return $module;
	}

	/**
	 * Render the values captured by a posted form, as a key/value table
	 *
	 * @param InputfieldForm $form
	 * @return string
	 */
	protected function renderSubmittedValues(InputfieldForm $form): string
	{
		$table = $this->requireModule(MarkupAdminDataTable::class);
		$table->setEncodeEntities(true);
		$table->headerRow([$this->_('Inputfield'), $this->_('Processed value')]);

		foreach ($form->getAll() as $f) {
			/** @var Inputfield $f */
			if ($f instanceof InputfieldSubmit) continue;
			if ($f instanceof InputfieldFieldset) continue;
			if ($f instanceof InputfieldMarkup) continue;
			if ($f->get('showcaseSkipResult')) continue;
			$value = $this->formatSubmittedValue($f->attr('value'));
			if ($f instanceof InputfieldPassword && $value !== '') $value = '******';
			$table->row([
				$this->formatSubmittedValue($f->attr('name')),
				$value,
			]);
		}

		return '<h2>' . $this->_('Captured form values') . '</h2>' . $table->render();
	}

	/**
	 * Convert an Inputfield attribute to display text without assuming it is scalar.
	 *
	 * @param mixed $value
	 * @return string
	 */
	protected function formatSubmittedValue($value): string
	{
		if (is_array($value)) {
			return implode(', ', array_map([$this, 'formatSubmittedValue'], $value));
		}
		if ($value === null) return '';
		if (is_bool($value)) return $value ? '1' : '0';
		if (is_scalar($value) || $value instanceof \Stringable) return (string) $value;
		return get_debug_type($value);
	}

	/**
	 * Get a small fixed dataset used by the table / list / card pages
	 *
	 * @return list<array{
	 *     name: string,
	 *     role: string,
	 *     email: string,
	 *     status: string,
	 *     created: int,
	 *     score: int,
	 *     icon: string
	 * }>
	 */
	protected function getDemoData(): array
	{
		return [
			['name' => 'Ada Lovelace',      'role' => 'Engineer',   'email' => 'ada@example.com',      'status' => 'active',   'created' => time() - 3 * 86400,   'score' => 92, 'icon' => 'female'],
			['name' => 'Alan Turing',       'role' => 'Architect',  'email' => 'alan@example.com',     'status' => 'active',   'created' => time() - 30 * 86400,  'score' => 88, 'icon' => 'male'],
			['name' => 'Grace Hopper',      'role' => 'Maintainer', 'email' => 'grace@example.com',    'status' => 'pending',  'created' => time() - 14 * 86400,  'score' => 81, 'icon' => 'female'],
			['name' => 'Linus Torvalds',    'role' => 'Reviewer',   'email' => 'linus@example.com',    'status' => 'archived', 'created' => time() - 180 * 86400, 'score' => 73, 'icon' => 'male'],
			['name' => 'Margaret Hamilton', 'role' => 'Engineer',   'email' => 'margaret@example.com', 'status' => 'active',   'created' => time() - 2 * 86400,   'score' => 95, 'icon' => 'female'],
		];
	}

	/* ---------------------------------------------------------------------
	 * Module configuration
	 * ------------------------------------------------------------------- */

	/**
	 * Render the module's configuration page (Modules > Configure > Showcase)
	 *
	 * Demonstrates how to attach a config form to a Process module — the
	 * exact same Inputfield API as the in-page forms above. Values are
	 * stored automatically by ProcessWire in the modules table.
	 *
	 * @param InputfieldWrapper $inputfields
	 * @return InputfieldWrapper
	 */
	public function getModuleConfigInputfields(InputfieldWrapper $inputfields)
	{
		$modules = $this->modules;
		$moduleConfig = $modules->getConfig($this);
		if (!is_array($moduleConfig)) $moduleConfig = [];
		$data = array_merge(self::$defaults, $moduleConfig);
		$environment = $this->formatSubmittedValue($data['environment']);
		if (!in_array($environment, ['development', 'staging', 'production'], true)) {
			$environment = self::$defaults['environment'];
		}
		$apiKey = $this->formatSubmittedValue($data['apiKey']);
		$maxItems = max(1, min(50, $this->sanitizer->int($data['maxItems'])));
		$tags = $this->formatSubmittedValue($data['tags']);
		$notes = $this->formatSubmittedValue($data['notes']);

		$f = $this->requireModule(InputfieldRadios::class);
		$f->attr('name', 'environment');
		$f->label = $this->_('Environment');
		$f->description = $this->_('Used as a label across the dashboard.');
		$f->addOption('development', $this->_('Development'));
		$f->addOption('staging',     $this->_('Staging'));
		$f->addOption('production',  $this->_('Production'));
		$f->optionColumns = 3;
		$f->val($environment);
		$inputfields->add($f);

		$f = $this->requireModule(InputfieldText::class);
		$f->attr('name', 'apiKey');
		$f->attr('type', 'password');
		$f->label = $this->_('API key');
		$f->description = $this->_('Demonstrates a sensitive setting (rendered as a password input).');
		$f->columnWidth = 50;
		$f->val($apiKey);
		$inputfields->add($f);

		$f = $this->requireModule(InputfieldInteger::class);
		$f->attr('name', 'maxItems');
		$f->label = $this->_('Maximum repeater rows');
		$f->description = $this->_('Drives the row count on the Repeater pattern page.');
		$f->min = 1;
		$f->max = 50;
		$f->columnWidth = 50;
		$f->setAttribute('value', $maxItems);
		$inputfields->add($f);

		$f = $this->requireModule(InputfieldCheckbox::class);
		$f->attr('name', 'enableFeatureX');
		$f->label = $this->_('Feature X');
		$f->label2 = $this->_('Enable Feature X');
		$f->attr('checked', !empty($data['enableFeatureX']) ? 'checked' : '');
		$inputfields->add($f);

		if ($modules->isInstalled('InputfieldTextTags')) {
			$f = $this->requireModule(InputfieldTextTags::class);
			$f->attr('name', 'tags');
			$f->label = $this->_('Default tags');
			$f->allowUserTags = true;
			$f->val($tags);
			$inputfields->add($f);
		}

		$f = $this->requireModule(InputfieldTextarea::class);
		$f->attr('name', 'notes');
		$f->label = $this->_('Notes');
		$f->attr('rows', 4);
		$f->val($notes);
		$inputfields->add($f);

		return $inputfields;
	}
}
