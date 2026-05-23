function showLoading(msg) {
    var overlay = document.getElementById('loading-overlay');
    if (!overlay) return;
    overlay.querySelector('.loading-card').classList.remove('fade-out');
    overlay.querySelector('.loading-icon').className = 'loading-icon';
    overlay.querySelector('.loading-icon').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    overlay.querySelector('.loading-text').textContent = msg || 'Procesando...';
    overlay.classList.add('active');
}

function showResult(tipo, msg) {
    var overlay = document.getElementById('loading-overlay');
    if (!overlay) return;
    var icon = tipo === 'success' ? 'fa-check-circle' : 'fa-times-circle';
    var cls = tipo === 'success' ? 'success' : 'error';
    overlay.querySelector('.loading-icon').className = 'loading-icon ' + cls;
    overlay.querySelector('.loading-icon').innerHTML = '<i class="fas ' + icon + '"></i>';
    overlay.querySelector('.loading-text').textContent = msg || (tipo === 'success' ? 'Operación exitosa.' : 'Error en la operación.');
}

function hideLoading() {
    var overlay = document.getElementById('loading-overlay');
    if (!overlay) return;
    var card = overlay.querySelector('.loading-card');
    card.classList.add('fade-out');
    setTimeout(function() {
        overlay.classList.remove('active');
        card.classList.remove('fade-out');
    }, 350);
}

function flashLifecycle(tipo, msg) {
    showLoading('Procesando...');
    setTimeout(function() {
        showResult(tipo, msg);
        setTimeout(hideLoading, 3000);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof flashSuccess !== 'undefined' && flashSuccess) {
        flashLifecycle('success', flashSuccess);
    } else if (typeof flashError !== 'undefined' && flashError) {
        flashLifecycle('error', flashError);
    }

    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (e.defaultPrevented) return;
            showLoading('Procesando...');
            if (form.getAttribute('target') !== '_blank') {
                e.preventDefault();
                setTimeout(function() { form.submit(); }, 150);
            }
        });
    });
});
