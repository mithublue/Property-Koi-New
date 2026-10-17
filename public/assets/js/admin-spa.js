/**
 * Aftabnagar Direct Marketplace - Admin SPA & AJAX Pagination Engine
 * Enables instant SPA-style navigation for admin submenus and AJAX pagination without full page reloads.
 */
(function() {
    'use strict';

    if (window.admSpaInitialized) return;
    window.admSpaInitialized = true;

    // Global Message Template Renderer with placeholder syntax support
    window.admRenderTemplate = function(templateKey, vars) {
        var templates = (window.admVars && window.admVars.messageTemplates) ? window.admVars.messageTemplates : {};
        var text = templates[templateKey] || '';
        vars = vars || {};
        if (!vars['%site_name%']) {
            vars['%site_name%'] = (window.admVars && window.admVars.siteName) ? window.admVars.siteName : 'PropertyKoi.com';
        }
        for (var k in vars) {
            var placeholder = (k.charAt(0) === '%' && k.charAt(k.length - 1) === '%') ? k : ('%' + k + '%');
            var val = (vars[k] != null) ? vars[k] : '';
            text = text.split(placeholder).join(val);
        }
        return text;
    };

    // Inject top progress bar element and styles
    var progressBar = document.createElement('div');
    progressBar.id = 'adm-spa-progress';
    progressBar.style.cssText = 'position:fixed;top:0;left:0;height:3px;width:0%;background:linear-gradient(90deg,#10b981,#059669);z-index:999999;transition:width 0.25s ease,opacity 0.25s ease;opacity:0;box-shadow:0 0 10px #10b981;pointer-events:none;';
    document.body.appendChild(progressBar);

    var progressTimer = null;

    function startProgress() {
        if (progressTimer) clearInterval(progressTimer);
        progressBar.style.opacity = '1';
        progressBar.style.width = '20%';
        progressTimer = setInterval(function() {
            var currentW = parseFloat(progressBar.style.width) || 20;
            if (currentW < 80) {
                progressBar.style.width = (currentW + Math.random() * 15) + '%';
            }
        }, 150);
    }

    function finishProgress() {
        if (progressTimer) clearInterval(progressTimer);
        progressBar.style.width = '100%';
        setTimeout(function() {
            progressBar.style.opacity = '0';
            setTimeout(function() {
                progressBar.style.width = '0%';
            }, 250);
        }, 200);
    }

    function isAdmUrl(urlStr) {
        try {
            var parsed = new URL(urlStr, window.location.origin);
            if (parsed.origin !== window.location.origin) return false;
            var page = parsed.searchParams.get('page');
            return page && page.indexOf('aftabnagar-') === 0;
        } catch (e) {
            return false;
        }
    }

    function updateAdminMenu(targetUrl) {
        try {
            var parsed = new URL(targetUrl, window.location.origin);
            var targetPage = parsed.searchParams.get('page');
            if (!targetPage) return;

            var menu = document.getElementById('adminmenu');
            if (!menu) return;

            // Find parent li for Aftabnagar Direct
            var aftabnagarParent = menu.querySelector('li.toplevel_page_aftabnagar-direct');
            if (!aftabnagarParent) {
                var parentLink = menu.querySelector('a[href*="page=aftabnagar-direct"]');
                if (parentLink) {
                    aftabnagarParent = parentLink.closest('li.menu-top');
                }
            }

            if (aftabnagarParent) {
                // Remove 'current' from old submenu items
                var currentItems = aftabnagarParent.querySelectorAll('.current');
                currentItems.forEach(function(el) {
                    el.classList.remove('current');
                });

                // Find matching submenu link
                var matchingLink = aftabnagarParent.querySelector('a[href*="page=' + targetPage + '"]');
                if (matchingLink) {
                    matchingLink.classList.add('current');
                    var li = matchingLink.closest('li');
                    if (li) li.classList.add('current');
                }
            }
        } catch (e) {
            console.warn('[ADM SPA] Error updating admin menu active state', e);
        }
    }

    var isLoading = false;

    function loadAdmPage(url, pushState, isPagination) {
        if (isLoading) return;
        isLoading = true;

        startProgress();

        var currentWrap = document.querySelector('.adm-admin-wrap');
        if (currentWrap) {
            currentWrap.style.opacity = '0.5';
            currentWrap.style.pointerEvents = 'none';
            currentWrap.style.transition = 'opacity 0.15s ease';
        }

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.text();
        })
        .then(function(html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var newWrap = doc.querySelector('.adm-admin-wrap');

            if (!newWrap) {
                // Fallback to full reload if wrapper not found
                window.location.href = url;
                return;
            }

            // Update page title
            if (doc.title) {
                document.title = doc.title;
            }

            // Update admin menu highlight
            updateAdminMenu(url);

            // Update URL pushState if requested
            if (pushState && window.history && window.history.pushState) {
                window.history.pushState({ admSpa: true, url: url }, '', url);
            }

            // 1. Collect all scripts from the fetched page
            var scriptsToRun = [];
            var docBody = doc.querySelector('#wpbody-content') || doc.body;
            if (docBody) {
                docBody.querySelectorAll('script').forEach(function(s) {
                    var src = s.getAttribute('src') || '';
                    if (src.indexOf('tailwindcss') !== -1 || src.indexOf('alpinejs') !== -1 || src.indexOf('admin-spa') !== -1) {
                        return;
                    }
                    scriptsToRun.push(s);
                });
            }
            newWrap.querySelectorAll('script').forEach(function(s) {
                if (scriptsToRun.indexOf(s) === -1) {
                    scriptsToRun.push(s);
                }
            });

            // 2. Clean up Alpine reactivity on the old element before removal
            if (currentWrap && window.Alpine && typeof window.Alpine.destroyTree === 'function') {
                try {
                    window.Alpine.destroyTree(currentWrap);
                } catch (e) {
                    console.warn('[ADM SPA] Alpine.destroyTree error:', e);
                }
            }

            // 3. EXECUTE SCRIPTS FIRST so all window functions, Alpine.data, and variables exist
            scriptsToRun.forEach(function(oldScript) {
                try {
                    var newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(function(attr) {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;
                    document.body.appendChild(newScript);
                    if (newScript.parentNode) {
                        newScript.parentNode.removeChild(newScript);
                    }
                } catch (scriptErr) {
                    console.error('[ADM SPA] Error executing page script:', scriptErr);
                }
            });

            // 4. Swap out the wrap element
            if (currentWrap && currentWrap.parentNode) {
                currentWrap.parentNode.replaceChild(newWrap, currentWrap);
            } else {
                var wpbodyContent = document.getElementById('wpbody-content');
                if (wpbodyContent) {
                    wpbodyContent.innerHTML = newWrap.outerHTML;
                    newWrap = wpbodyContent.querySelector('.adm-admin-wrap');
                }
            }

            // 5. Initialize Alpine tree on the new content
            setTimeout(function() {
                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    try {
                        window.Alpine.initTree(newWrap);
                    } catch (alpineErr) {
                        console.warn('[ADM SPA] Alpine.initTree error:', alpineErr);
                    }
                }
            }, 20);

            // 6. Scroll position adjustment
            if (isPagination) {
                newWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // Dispatch global event for external scripts
            document.dispatchEvent(new CustomEvent('adm:page-swapped', {
                detail: { url: url, isPagination: !!isPagination }
            }));
        })
        .catch(function(err) {
            console.error('[ADM SPA] Navigation failed, reloading normally:', err);
            window.location.href = url;
        })
        .finally(function() {
            isLoading = false;
            finishProgress();
        });
    }

    // Intercept click on links
    document.addEventListener('click', function(e) {
        var link = e.target.closest('a');
        if (!link) return;

        // Skip non-standard clicks
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;

        var href = link.getAttribute('href');
        if (!href || href === '#' || href.indexOf('javascript:') === 0) return;

        var targetUrl = link.href;
        if (!isAdmUrl(targetUrl)) return;

        // Check if inside admin menu submenu or inside .adm-admin-wrap
        var isMenuLink = !!link.closest('#adminmenu');
        var isWrapLink = !!link.closest('.adm-admin-wrap');
        var isPaginationLink = !!(link.closest('.adm-pagination-bar') || link.closest('[data-adm-pagination]') || href.indexOf('paged=') !== -1);

        if (isMenuLink || isWrapLink || isPaginationLink) {
            e.preventDefault();
            loadAdmPage(targetUrl, true, isPaginationLink);
        }
    });

    // Intercept GET filter forms inside .adm-admin-wrap
    document.addEventListener('submit', function(e) {
        var form = e.target.closest('form');
        if (!form) return;

        var method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method !== 'GET') return; // Let POST forms submit normally for nonces & data integrity

        var isInsideWrap = !!form.closest('.adm-admin-wrap');
        if (!isInsideWrap) return;

        var action = form.getAttribute('action') || window.location.href;
        var formUrl = new URL(action, window.location.origin);

        // Append form parameters
        var formData = new FormData(form);
        formData.forEach(function(val, key) {
            if (val !== '' && val !== 'null') {
                formUrl.searchParams.set(key, val);
            } else {
                formUrl.searchParams.delete(key);
            }
        });

        // Reset paged to 1 on new filter search
        formUrl.searchParams.delete('paged');

        if (isAdmUrl(formUrl.toString())) {
            e.preventDefault();
            loadAdmPage(formUrl.toString(), true, false);
        }
    });

    // Handle browser back and forward buttons
    window.addEventListener('popstate', function(e) {
        if (isAdmUrl(window.location.href)) {
            loadAdmPage(window.location.href, false, false);
        }
    });

})();
