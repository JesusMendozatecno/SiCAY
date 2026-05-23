function createBubble() {
    const container = document.getElementById('bubble-container');
    if (!container) return;
    const bubble = document.createElement('div');
    const size = Math.random() * 60 + 20 + 'px';
    bubble.classList.add('bubble');
    bubble.style.width = size;
    bubble.style.height = size;
    bubble.style.left = Math.random() * 100 + 'vw';
    bubble.style.animationDuration = Math.random() * 5 + 7 + 's';
    container.appendChild(bubble);
    setTimeout(() => { bubble.remove(); }, 10000);
}
setInterval(createBubble, 800);

function togglePass(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

function checkReqs(val) {
    return {
        length: val.length >= 8,
        upper: /[A-Z]/.test(val),
        number: /[0-9]/.test(val),
        symbol: /[^A-Za-z0-9]/.test(val)
    };
}

var reqLabels = {
    length: 'Mínimo 8 caracteres',
    upper: 'Una mayúscula',
    number: 'Un número',
    symbol: 'Un símbolo especial'
};

function soloLetras(e) {
    var charCode = e.which || e.keyCode;
    var char = String.fromCharCode(charCode);
    if (!/[A-Za-zÁÉÍÓÚáéíóúñÑ ]/.test(char) && charCode !== 8 && charCode !== 46 && charCode !== 37 && charCode !== 39) {
        e.preventDefault();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var nombreInput = document.getElementById('nombre');
    if (nombreInput) {
        nombreInput.addEventListener('keypress', soloLetras);
        nombreInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúñÑ ]/g, '');
        });
    }
    var passInput = document.getElementById('pass');
    var pass2Input = document.getElementById('pass2');
    var reqsList = document.getElementById('passReqs');
    var form = passInput ? passInput.closest('form') : null;
    var prevState = { length: false, upper: false, number: false, symbol: false };

    if (!passInput) return;

    passInput.addEventListener('input', function() {
        var val = this.value;
        var current = checkReqs(val);

        Object.keys(current).forEach(function(req) {
            if (current[req] && !prevState[req]) {
                showToast(reqLabels[req]);
            }
            prevState[req] = current[req];
        });

        var confirmGroup = document.getElementById('confirm-pass-group');
        if (confirmGroup) {
            var show = val.length > 0;
            confirmGroup.style.display = show ? 'block' : 'none';
            var pass2 = document.getElementById('pass2');
            if (pass2) {
                pass2.required = show;
                if (!show) pass2.value = '';
            }
        }
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            var val = passInput.value;
            var current = checkReqs(val);

            if (pass2Input && val !== pass2Input.value) {
                showToast('Las contraseñas no coinciden', true);
            }

            var failed = Object.keys(current).filter(function(k) { return !current[k]; });
            if (failed.length > 0) {
                e.preventDefault();
                showReqsErrors(failed);
            }
        });
    }

    var confirmGroup = document.getElementById('confirm-pass-group');
    if (confirmGroup) {
        var hasVal = passInput.value.length > 0;
        confirmGroup.style.display = hasVal ? 'block' : 'none';
        var pass2 = document.getElementById('pass2');
        if (pass2) pass2.required = hasVal;
    }

    if (typeof registroErrores !== 'undefined' && registroErrores) {
        var reqKeys = ['length', 'upper', 'number', 'symbol'];
        var isReqArray = registroErrores.every(function(e) { return reqKeys.indexOf(e) !== -1; });
        if (isReqArray) {
            showReqsErrors(registroErrores);
        } else {
            registroErrores.forEach(function(msg) { showToast(msg, true); });
        }
    }
});

function showToast(msg, isError) {
    var container = document.getElementById('toast-container');
    if (!container) return;
    var toast = document.createElement('div');
    toast.className = isError ? 'toast-error' : 'req-toast';
    toast.innerHTML = (isError ? '<i class="fas fa-exclamation-circle"></i> ' : '<i class="fas fa-check-circle"></i> ') + msg;
    container.appendChild(toast);
    setTimeout(function() {
        toast.classList.add('fade-out');
        setTimeout(function() { toast.remove(); }, 400);
    }, 3000);
}

function showReqsErrors(failed) {
    var list = document.getElementById('passReqs');
    if (!list) return;
    list.style.display = 'block';
    list.querySelectorAll('li').forEach(function(li) {
        var req = li.getAttribute('data-req');
        if (failed.indexOf(req) !== -1) {
            li.classList.add('error');
            li.classList.remove('met');
        } else {
            li.classList.add('met');
            li.classList.remove('error');
        }
    });
    setTimeout(function() {
        list.style.display = 'none';
    }, 3000);
}
