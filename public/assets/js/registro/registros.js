(function () {
    var sidebarNav = document.querySelector('.reg-sidebar-nav');
    var dashboard = document.getElementById('regDashboard');
    var loaded = document.getElementById('regLoaded');
    var loading = document.getElementById('regLoading');
    var activeRoute = null;
    var loadedStyles = new Set();

    // ── Burbujas ──
    (function () {
        var container = document.getElementById('bubble-container');
        if (!container) return;
        function create() {
            var b = document.createElement('div');
            b.className = 'bubble';
            var s = Math.random() * 60 + 20 + 'px';
            b.style.width = s;
            b.style.height = s;
            b.style.left = Math.random() * 100 + 'vw';
            b.style.animationDuration = Math.random() * 5 + 7 + 's';
            container.appendChild(b);
            setTimeout(function () { b.remove(); }, 10000);
        }
        create();
        setInterval(create, 1200);
    })();

    // ── Tema claro/oscuro (usa clase dark-mode de theme.css) ──
    (function () {
        var toggle = document.getElementById('themeToggle');
        var icon = toggle ? toggle.querySelector('i') : null;

        function setTheme(mode) {
            if (mode === 'light') {
                document.body.classList.remove('dark-mode');
                if (icon) { icon.className = 'fas fa-moon'; }
            } else {
                document.body.classList.add('dark-mode');
                if (icon) { icon.className = 'fas fa-sun'; }
            }
            try { localStorage.setItem('registros-theme', mode); } catch (e) {}
        }

        var saved = 'dark';
        try { saved = localStorage.getItem('registros-theme') || 'dark'; } catch (e) {}
        setTheme(saved);

        if (toggle) {
            toggle.addEventListener('click', function () {
                var isDark = document.body.classList.contains('dark-mode');
                setTheme(isDark ? 'light' : 'dark');
            });
        }
    })();

    // ── Toggle grupos ──
    sidebarNav.addEventListener('click', function (e) {
        var head = e.target.closest('.reg-nav-head');
        if (head) {
            var grupo = head.closest('.reg-nav-grupo');
            if (grupo) grupo.classList.toggle('abierto');
        }
    });

    // ── Versión para cache-busting de CSS inyectado ──
    function pad2(n) { return n < 10 ? '0' + n : n; }
    (function () {
        var d = new Date();
        window.__cssV = 'v=' + d.getFullYear() + pad2(d.getMonth()+1) + pad2(d.getDate()) + pad2(d.getHours()) + pad2(d.getMinutes());
    })();

    // ── Inyectar CSS desde el documento cargado ──
    function injectStyles(doc) {
        var links = doc.querySelectorAll('link[rel="stylesheet"]');
        links.forEach(function (link) {
            var href = link.getAttribute('href');
            if (!href || loadedStyles.has(href)) return;
            loadedStyles.add(href);
            var newLink = document.createElement('link');
            newLink.rel = 'stylesheet';
            newLink.href = href + (href.indexOf('?') >= 0 ? '&' : '?') + window.__cssV;
            document.head.appendChild(newLink);
        });
    }

    // ── Ejecutar scripts del documento cargado ──
    function execScripts(doc) {
        var scripts = doc.querySelectorAll('script');
        scripts.forEach(function (oldScr) {
            var newScr = document.createElement('script');
            if (oldScr.src) {
                newScr.src = oldScr.src;
                newScr.async = false;
            } else {
                newScr.textContent = oldScr.textContent;
            }
            try {
                document.body.appendChild(newScr);
            } catch (e) {}
        });
    }

    // ── Cargar ruta via AJAX ──
    function loadRoute(route) {
        if (route === activeRoute) return;
        activeRoute = route;

        dashboard.style.display = 'none';
        loading.style.display = 'flex';
        loaded.innerHTML = '';
        loaded.style.display = 'block';

        fetch('index.php?route=' + encodeURIComponent(route) + '&embed=1')
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');

                var contenedor = doc.querySelector('.contenedor, .container');
                var bodyChildren = doc.body;

                loading.style.display = 'none';

                if (contenedor) {
                    loaded.innerHTML = contenedor.outerHTML;
                } else if (bodyChildren) {
                    loaded.innerHTML = bodyChildren.innerHTML;
                } else {
                    loaded.innerHTML = html;
                }

                var modals = doc.querySelectorAll('.modal-overlay');
                document.querySelectorAll('.modal-overlay').forEach(function (old) {
                    old.remove();
                });
                modals.forEach(function (m) {
                    document.body.appendChild(m);
                });

                injectStyles(doc);
                execScripts(doc);
            })
            .catch(function () {
                loading.style.display = 'none';
                loaded.innerHTML = '<div class="alerta-error" style="margin-top:40px;text-align:center;"><i class="fas fa-exclamation-triangle"></i> Error al cargar el formulario. Intente de nuevo.</div>';
            });
    }

    // ── Ir al inicio ──
    function goHome() {
        activeRoute = null;
        loaded.style.display = 'none';
        loaded.innerHTML = '';
        dashboard.style.display = 'block';
    }

    // ── Enviar formulario via AJAX ──
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.closest('#regLoaded')) return;
        e.preventDefault();

        var data = new FormData(form);

        loading.style.display = 'flex';

        fetch('index.php?route=' + encodeURIComponent(activeRoute) + '&embed=1', {
            method: 'POST',
            body: data
        })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var contenedor = doc.querySelector('.contenedor, .container');
                var bodyChildren = doc.body;

                loading.style.display = 'none';

                if (contenedor) {
                    loaded.innerHTML = contenedor.outerHTML;
                } else if (bodyChildren) {
                    loaded.innerHTML = bodyChildren.innerHTML;
                } else {
                    loaded.innerHTML = html;
                }

                var modals = doc.querySelectorAll('.modal-overlay');
                document.querySelectorAll('.modal-overlay').forEach(function (old) {
                    old.remove();
                });
                modals.forEach(function (m) {
                    document.body.appendChild(m);
                });

                injectStyles(doc);
                execScripts(doc);
            })
            .catch(function () {
                loading.style.display = 'none';
                loaded.innerHTML += '<div class="alerta-error" style="margin-top:16px;"><i class="fas fa-exclamation-triangle"></i> Error al procesar el formulario. Intente de nuevo.</div>';
            });
    });

    // ── Click en subitems ──
    sidebarNav.addEventListener('click', function (e) {
        var sub = e.target.closest('.reg-nav-subitem');
        if (!sub) {
            var home = e.target.closest('#btnHome');
            if (home) {
                document.querySelectorAll('.reg-nav-subitem.activo').forEach(function (el) {
                    el.classList.remove('activo');
                });
                goHome();
            }
            return;
        }

        var route = sub.getAttribute('data-route');
        if (!route) return;

        document.querySelectorAll('.reg-nav-subitem.activo').forEach(function (el) {
            el.classList.remove('activo');
        });
        sub.classList.add('activo');

        var grupo = sub.closest('.reg-nav-grupo');
        if (grupo && !grupo.classList.contains('abierto')) {
            grupo.classList.add('abierto');
        }

        loadRoute(route);
    });
})();
