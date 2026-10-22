(() => {
	const mount = document.getElementById('showcase-heading-comparison');
	if (!mount) return;
	const head = [...document.head.querySelectorAll('link[rel="stylesheet"],style')]
		.filter(element => !element.textContent.includes('.showcase-html-fixture'))
		.map(element => element.outerHTML).join('');
	const levels = Array.from({ length: 6 }, (_, index) => index + 1);
	const headings = levels.map(level => '<div class="heading-pair"><h' + level + '>H' + level + ' native heading</h' + level + '><div class="uk-h' + level + '">.uk-h' + level + ' class heading</div></div>').join('');
	const samples = '<section><h2>Ordinary headings</h2>' + headings + '</section>' +
		'<section><h2>Inheritance diagnostic: muted parent</h2><div style="color:var(--pw-muted-color)">' + headings + '</div></section>' +
		'<section><h2>Card titles</h2>' + ['default', 'primary', 'secondary'].map(variant =>
			'<div class="uk-card uk-card-' + variant + ' uk-card-body"><h2 class="uk-card-title">' + variant + ' H2 title</h2><h4 class="uk-card-title">' + variant + ' H4 title</h4><div class="uk-h4 uk-card-title">' + variant + ' .uk-h4 title</div><p>Ordinary card text</p></div>').join('') + '</section>' +
		'<section><h2>jQuery UI accordion headers</h2><div class="heading-accordion"><h3><a href="#">H3 accordion header</a></h3><div><h4>H4 inside accordion content</h4><p>Accordion content</p></div><h4><a href="#">H4 accordion header</a></h4><div><p>Second panel</p></div></div></section>';
	for (const mode of ['light', 'dark']) {
		const column = document.createElement('div');
		const title = document.createElement('h2');
		title.textContent = mode === 'light' ? 'Light' : 'Dark';
		const frame = document.createElement('iframe');
		frame.title = mode + ' heading comparison';
		frame.style.cssText = 'width:100%;height:1000px;border:1px solid var(--pw-border-color);display:block';
		frame.addEventListener('load', () => {
			const doc = frame.contentDocument;
			if (window.jQuery && window.jQuery.fn.accordion) {
				window.jQuery(doc.querySelector('.heading-accordion')).accordion({ heightStyle: 'content', collapsible: true });
			}
		});
		frame.srcdoc = '<!doctype html><html class="pw" lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' + head +
			'<style>html,body{color-scheme:' + mode + '!important}body{display:block;padding:16px;margin:0;background:var(--pw-main-background)}section{margin-bottom:28px}.heading-pair{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;align-items:baseline}.heading-pair>*{min-width:0;overflow-wrap:anywhere;margin-top:0}.uk-card{margin-bottom:16px}section>h2{margin-top:0}@media(max-width:460px){.heading-pair{grid-template-columns:1fr}}</style></head><body class="' + mode + '-theme">' + samples + '</body></html>';
		column.append(title, frame);
		mount.append(column);
	}
})();
