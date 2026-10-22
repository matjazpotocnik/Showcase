(() => {
    const start = () => {
        const root = document.getElementById('showcase-uikit');
        if (!root) return;
        const component = root.querySelector('[data-component]');
        const scheme = root.querySelector('[data-scheme]');
        const inverse = root.querySelector('[data-inverse]');
        const rtl = root.querySelector('[data-rtl]');
        const inspect = root.querySelector('[data-inspect]');
        const inspection = root.querySelector('[data-inspection]');
        let selected = null;
        let frozen = false;
        const colorContext = document.createElement('canvas').getContext('2d', { willReadFrequently: true });
        const colorHex = (color) => {
            colorContext.clearRect(0, 0, 1, 1);
            colorContext.fillStyle = color;
            colorContext.fillRect(0, 0, 1, 1);
            const channels = Array.from(colorContext.getImageData(0, 0, 1, 1).data);
            if (channels[3] === 255) channels.pop();
            return '#' + channels.map(channel => channel.toString(16).padStart(2, '0')).join('');
        };
        const clearInspection = () => {
            if (selected) {
                selected.element.style.setProperty('outline', selected.outline, selected.outlinePriority);
                selected.element.style.setProperty('outline-offset', selected.offset, selected.offsetPriority);
            }
            selected = null;
        };
        const inspectElement = (element, frame) => {
            clearInspection();
            const style = frame.contentWindow.getComputedStyle(element);
            // MP: The inspection label follows the pane, including the frozen comparison.
            // inspection.textContent = `${frame.dataset.preview === 'admin' ? 'Admin theme' : 'Stock UIkit'}: <${element.tagName.toLowerCase()}>${element.id ? ` #${element.id}` : ''}\nClasses: ${Array.from(element.classList).join(' ') || '(none)'}\nColor: ${colorHex(style.color)}; background: ${colorHex(style.backgroundColor)}`;
            inspection.textContent = `${frame.closest('.showcase-pane').querySelector('.showcase-pane-title').textContent}: <${element.tagName.toLowerCase()}>${element.id ? ` #${element.id}` : ''}\nClasses: ${Array.from(element.classList).join(' ') || '(none)'}\nColor: ${colorHex(style.color)}; background: ${colorHex(style.backgroundColor)}`;
            selected = { element, outline: element.style.outline, offset: element.style.outlineOffset, outlinePriority: element.style.getPropertyPriority('outline'), offsetPriority: element.style.getPropertyPriority('outline-offset') };
            element.style.setProperty('outline', '2px solid #e83e8c', 'important');
            element.style.setProperty('outline-offset', '-2px', 'important');
        };
        const frames = Array.from(root.querySelectorAll('iframe[data-preview]'));
        const scroll = root.querySelector('[data-scroll]');
        const scrollSpace = root.querySelector('[data-scroll-space]');
        const expectedScroll = new WeakMap();
        const error = root.querySelector('[data-error]');
        const fixtures = new URL(root.dataset.fixtures, location.href);
        const components = new Set(Array.from(component.options, option => option.value));
        const initial = new URL(location.href).searchParams.get('component');
        if (components.has(initial)) component.value = initial;
        scheme.value = getComputedStyle(document.documentElement).colorScheme === 'dark' || document.body.classList.contains('dark-theme') ? 'dark' : 'light';
        let request = null;

        const frozenStyles = (preview, styles) => {
            const assets = JSON.parse(root.dataset.frozenStyles);
            const pathname = url => new URL(url, location.href).pathname;
            const tokensIndex = styles.findIndex(node => node.tagName === 'LINK' && pathname(node.href) === pathname(assets.tokens));
            if (tokensIndex < 0) throw new Error('The current admin stack has no admin-custom.css token stylesheet.');
            const mergedUrl = new URL(assets.merged, location.href);
            const excluded = new Set([
                pathname(assets.base), pathname(assets.common), pathname(assets.tokens), mergedUrl.pathname,
                new URL('admin-v2.css', mergedUrl).pathname,
                new URL('admin_transfer.css', mergedUrl).pathname,
            ]);
            const retain = node => node.tagName !== 'LINK' || !excluded.has(pathname(node.href));
            const link = href => {
                const node = preview.createElement('link');
                node.rel = 'stylesheet';
                node.href = new URL(href, location.href).href;
                return node;
            };
            return [link(assets.base), link(assets.common), ...styles.slice(0, tokensIndex).filter(retain),
                link(assets.tokens), link(assets.merged), ...styles.slice(tokensIndex + 1).filter(retain)];
        };

        const synchronize = (source) => {
            const maximum = source.scrollHeight - source.clientHeight;
            const ratio = maximum > 0 ? source.scrollTop / maximum : 0;
            const targets = [scroll, ...frames.map(frame => frame.contentDocument?.scrollingElement).filter(Boolean)];
            targets.forEach(target => {
                if (target === source) return;
                const position = ratio * Math.max(0, target.scrollHeight - target.clientHeight);
                expectedScroll.set(target, position);
                target.scrollTop = position;
            });
        };

        const onScroll = (source) => {
            const expected = expectedScroll.get(source);
            expectedScroll.delete(source);
            if (expected !== undefined && Math.abs(source.scrollTop - expected) < 2) return;
            synchronize(source);
        };

        const resizeScroll = () => {
            const maximum = Math.max(0, ...frames.map(frame => {
                const element = frame.contentDocument?.scrollingElement;
                return element ? element.scrollHeight - element.clientHeight : 0;
            }));
            scrollSpace.style.height = `${scroll.clientHeight + maximum}px`;
        };

        const applyTheme = () => {
            frames.forEach(frame => {
            const preview = frame.contentDocument;
            if (!preview?.body) return;
            preview.documentElement.dir = rtl.checked ? 'rtl' : 'ltr';
            preview.documentElement.style.colorScheme = scheme.value;
            preview.body.classList.remove('light-theme', 'dark-theme', 'pw-no-dark-mode', 'uk-light', 'uk-dark');
            preview.body.classList.add(`${scheme.value}-theme`);
            if (inverse.value) preview.body.classList.add(inverse.value);
            if (frame.dataset.preview === 'stock') {
                preview.documentElement.style.backgroundColor = scheme.value === 'dark' ? '#222' : '#fff';
            }
            });
            resizeScroll();
        };

        const load = async () => {
            request?.abort();
            request = new AbortController();
            error.hidden = true;
            frames.forEach(frame => frame.setAttribute('aria-busy', 'true'));
            try {
                const response = await fetch(new URL(`${component.value}.html`, fixtures), { signal: request.signal });
                if (!response.ok) throw new Error(`Unable to load component (${response.status}).`);
                const html = await response.text();
                const runtime = Array.from(document.querySelectorAll('script[src]')).filter(script => /\/uikit(?:-icons)?(?:\.min)?\.js(?:\?|$)/.test(script.src));
                const coreRuntime = runtime.find(script => /\/uikit(?:\.min)?\.js(?:\?|$)/.test(script.src));
                if (!coreRuntime) throw new Error('The active admin theme has not loaded UIkit.');
                frames.forEach(frame => {
                const stock = frame.dataset.preview === 'stock';
                const preview = new DOMParser().parseFromString(html, 'text/html');
                preview.querySelectorAll('script[src]').forEach(script => {
                    if (new URL(script.getAttribute('src'), fixtures).pathname.endsWith('/js/test.js')) script.remove();
                });
                preview.querySelectorAll('base').forEach(base => base.remove());
                const base = preview.createElement('base');
                base.href = fixtures.href;
                preview.head.prepend(base);
                // MP: Only the new right pane rebuilds the historical stack; the live pane remains unchanged.
                // const styles = stock ? [] : Array.from(document.head.querySelectorAll('link[rel="stylesheet"], style'), node => {
                let styles = stock ? [] : Array.from(document.head.querySelectorAll('link[rel="stylesheet"], style'), node => {
                    const copy = node.cloneNode(true);
                    if (copy.tagName === 'LINK') copy.href = node.href;
                    return copy;
                });
                if (frame.dataset.preview === 'frozen') styles = frozenStyles(preview, styles);
                base.after(...styles);
                if (stock) {
                    const stylesheet = preview.createElement('link');
                    stylesheet.rel = 'stylesheet';
                    stylesheet.href = coreRuntime.src.replace(/\/js\/uikit(?:\.min)?\.js(?:\?.*)?$/, '/css/uikit.css');
                    base.after(stylesheet);
                }
                const scrolling = preview.createElement('style');
                scrolling.textContent = 'html { scrollbar-width: none; scroll-behavior: auto !important; } html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }';
                preview.head.append(scrolling);
                if (!stock) {
                    const canvas = preview.createElement('style');
                    // MP: Add preview-only gutters while retaining the admin flex layout and width cap.
                    // canvas.textContent = 'body, body.uk-dark, body.uk-light { background: var(--pw-main-background); } body > * { min-width: 0; max-width: 100% !important; }';
                    // MP: Direct fixture containers must fill the flex body despite UIkit's auto horizontal margins.
                    // canvas.textContent = 'body, body.uk-dark, body.uk-light { background: var(--pw-main-background); padding-inline: 15px !important; box-sizing: border-box; } body > * { min-width: 0; max-width: 100% !important; }';
                    // MP: Viewport expand fixtures must consume remaining space in the admin flex body.
                    // canvas.textContent = 'body, body.uk-dark, body.uk-light { background: var(--pw-main-background); padding-inline: 15px !important; box-sizing: border-box; } body > * { min-width: 0; max-width: 100% !important; } body > .uk-container { width: 100%; box-sizing: border-box; }';
                    canvas.textContent = 'body, body.uk-dark, body.uk-light { background: var(--pw-main-background); padding-inline: 15px !important; box-sizing: border-box; } body > * { min-width: 0; max-width: 100% !important; } body > .uk-container { width: 100%; box-sizing: border-box; } body > [uk-height-viewport*="expand: true"] { flex-grow: 1; }';
                    preview.head.append(canvas);
                }
                const scripts = runtime.map(node => {
                    const script = preview.createElement('script');
                    script.src = node.src;
                    return script;
                });
                preview.head.insertBefore(scripts[0], base.nextSibling);
                scripts.slice(1).forEach(script => scripts[0].after(script));
                preview.documentElement.className = stock ? '' : document.documentElement.className;
                preview.documentElement.dir = rtl.checked ? 'rtl' : 'ltr';
                preview.documentElement.style.colorScheme = scheme.value;
                if (!stock) preview.body.classList.add(...Array.from(document.body.classList).filter(name => /^(?:main-color-|pw-togcbx$)/.test(name)), 'AdminThemeUikit');
                preview.body.classList.remove('light-theme', 'dark-theme');
                preview.body.classList.add(`${scheme.value}-theme`);
                if (inverse.value) preview.body.classList.add(inverse.value);
                if (stock) preview.documentElement.style.backgroundColor = scheme.value === 'dark' ? '#222' : '#fff';
                frame.srcdoc = '<!DOCTYPE html>\n' + preview.documentElement.outerHTML;
                });
                scroll.scrollTop = 0;
                const url = new URL(location.href);
                url.searchParams.set('component', component.value);
                history.replaceState(null, '', url);
            } catch (failure) {
                if (failure.name === 'AbortError') return;
                error.textContent = failure.message;
                error.hidden = false;
                frames.forEach(frame => frame.removeAttribute('aria-busy'));
            }
        };

        frames.forEach(frame => frame.addEventListener('load', () => {
            applyTheme();
            frame.removeAttribute('aria-busy');
            const preview = frame.contentDocument;
            preview.addEventListener('pointerover', event => {
                if (inspect.checked && !frozen && event.target.nodeType === 1) inspectElement(event.target, frame);
            }, true);
            preview.addEventListener('click', event => {
                if (!inspect.checked) return;
                event.preventDefault();
                event.stopImmediatePropagation();
                inspectElement(event.target, frame);
                frozen = true;
            }, true);
            preview.addEventListener('scroll', () => onScroll(preview.scrollingElement), { passive: true });
            const observer = new frame.contentWindow.ResizeObserver(resizeScroll);
            observer.observe(preview.body);
            preview.fonts.ready.then(resizeScroll);
        }));
        scroll.addEventListener('scroll', () => onScroll(scroll), { passive: true });
        new ResizeObserver(resizeScroll).observe(scroll);
        component.addEventListener('change', load);
        scheme.addEventListener('change', applyTheme);
        inverse.addEventListener('change', applyTheme);
        rtl.addEventListener('change', applyTheme);
        inspect.addEventListener('change', () => {
            frozen = false;
            clearInspection();
            inspection.hidden = !inspect.checked;
            inspection.textContent = '';
        });
        component.addEventListener('change', () => {
            frozen = false;
            clearInspection();
            inspection.textContent = '';
        });
        load();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
