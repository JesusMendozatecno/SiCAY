/**
 * sistemas.js — Interactividad para la página "Sistema de Archivos" (SICAY)
 *
 * Esta página funciona con un sistema de tabs (pestañas) en el sidebar.
 * Al hacer clic en un botón del menú lateral, se oculta el panel actual
 * y se muestra el panel correspondiente.
 *
 * Funcionamiento:
 *   1. Cada botón del sidebar tiene un atributo data-tab con el id del panel
 *      que debe mostrar (ej: data-tab="tab-dashboard").
 *   2. Cada panel tiene un id que coincide con ese valor (ej: id="tab-dashboard").
 *   3. Al hacer clic, se remueve la clase "active" de todos los botones y paneles,
 *      y se agrega solo al botón/panel seleccionado.
 *
 * Esto permite navegar entre los distintos módulos sin recargar la página.
 */

(function () {
    "use strict";

    // Selecciona todos los botones de navegación del sidebar
    var btns = document.querySelectorAll('.sys-nav-btn');

    // Recorre cada botón y asigna el evento click
    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {

            // 1. Remueve la clase 'active' de todos los botones
            btns.forEach(function (b) {
                b.classList.remove('active');
            });

            // 2. Agrega 'active' solo al botón que se clickeó
            this.classList.add('active');

            // 3. Oculta todos los paneles (tab-panel)
            var panels = document.querySelectorAll('.tab-panel');
            panels.forEach(function (p) {
                p.classList.remove('active');
            });

            // 4. Muestra el panel cuyo id está en data-tab del botón clickeado
            var targetId = this.getAttribute('data-tab');
            var targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });

})();
