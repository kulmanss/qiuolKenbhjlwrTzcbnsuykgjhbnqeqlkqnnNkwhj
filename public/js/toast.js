// ===== toast.js - Sistema de Toast & Confirm do Yorkut =====
// Substitui alert() e confirm() nativos por componentes fiéis ao visual do site

(function() {
    'use strict';

    var _container = null;
    var _styleInjected = false;
    var _pendingToasts = [];

    // CSS completo
    var CSS = '\
#urkut-toast-container{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:999999;display:flex;flex-direction:column;align-items:center;gap:10px;pointer-events:none;max-width:420px;width:calc(100% - 40px)}\
.urkut-toast{pointer-events:auto;display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:4px;font-family:Tahoma,Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#333;box-shadow:0 4px 16px rgba(0,0,0,.18),0 1px 4px rgba(0,0,0,.1);opacity:0;transform:translateY(-20px) scale(.95);transition:all .35s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden;cursor:pointer;min-width:280px}\
.urkut-toast.show{opacity:1;transform:translateY(0) scale(1)}\
.urkut-toast.hide{opacity:0;transform:translateY(-20px) scale(.95)}\
.urkut-toast-icon{font-size:16px;flex-shrink:0;margin-top:1px}\
.urkut-toast-body{flex:1;min-width:0}\
.urkut-toast-title{font-weight:bold;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}\
.urkut-toast-msg{word-wrap:break-word}\
.urkut-toast-close{flex-shrink:0;background:none;border:none;font-size:16px;cursor:pointer;padding:0;line-height:1;opacity:.5;transition:opacity .2s;margin-top:-2px;color:inherit}\
.urkut-toast-close:hover{opacity:1}\
.urkut-toast-progress{position:absolute;bottom:0;left:0;height:3px;border-radius:0 0 4px 4px;transition:width linear}\
.urkut-toast-success{background:linear-gradient(to bottom,#eef4fb,#dde8f4);border:1px solid #a5bce3;border-left:4px solid #6d84b4}\
.urkut-toast-success .urkut-toast-title{color:#2f4f87}.urkut-toast-success .urkut-toast-close{color:#2f4f87}.urkut-toast-success .urkut-toast-progress{background:#6d84b4}\
.urkut-toast-error{background:linear-gradient(to bottom,#fdf0f0,#fce2e2);border:1px solid #e09090;border-left:4px solid #cc3333}\
.urkut-toast-error .urkut-toast-title{color:#cc3333}.urkut-toast-error .urkut-toast-close{color:#cc3333}.urkut-toast-error .urkut-toast-progress{background:#cc3333}\
.urkut-toast-warning{background:linear-gradient(to bottom,#fffbdc,#fef8cc);border:1px solid #ffd324;border-left:4px solid #e6a800}\
.urkut-toast-warning .urkut-toast-title{color:#8a6d00}.urkut-toast-warning .urkut-toast-close{color:#8a6d00}.urkut-toast-warning .urkut-toast-progress{background:#e6a800}\
.urkut-toast-info{background:linear-gradient(to bottom,#eef3fb,#dce6f5);border:1px solid #b8cbe6;border-left:4px solid #6d84b4}\
.urkut-toast-info .urkut-toast-title{color:#2f4f87}.urkut-toast-info .urkut-toast-close{color:#2f4f87}.urkut-toast-info .urkut-toast-progress{background:#6d84b4}\
.urkut-confirm-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.45);z-index:999998;display:flex;justify-content:center;align-items:center;opacity:0;transition:opacity .25s ease;font-family:Tahoma,Arial,Helvetica,sans-serif}\
.urkut-confirm-overlay.show{opacity:1}\
.urkut-confirm-box{background:#fff;border:1px solid #b8cbe6;border-radius:4px;box-shadow:0 4px 16px rgba(0,0,0,.18);max-width:420px;width:90%;transform:scale(.9) translateY(-10px);transition:transform .3s cubic-bezier(.4,0,.2,1);overflow:hidden}\
.urkut-confirm-overlay.show .urkut-confirm-box{transform:scale(1) translateY(0)}\
.urkut-confirm-header{background:#bfd0ea;padding:6px 10px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #b8cbe6}\
.urkut-confirm-header-icon{font-size:14px}\
.urkut-confirm-header-text{color:#2f4f87;font-size:11px;font-weight:bold}\
.urkut-confirm-body{padding:16px 10px;font-size:12px;line-height:1.6;color:#333}\
.urkut-confirm-body .confirm-warning-text{color:#cc0000;font-weight:bold;font-size:11px;margin-top:8px}\
.urkut-confirm-footer{display:flex;justify-content:flex-end;gap:8px;padding:8px 10px;background:#f4f7fc;border-top:1px solid #b8cbe6}\
.urkut-confirm-btn{padding:6px 16px;border-radius:3px;font-family:Tahoma,Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;cursor:pointer;transition:all .2s;border:1px solid;min-width:80px;text-align:center}\
.urkut-confirm-btn-no{background:#fff;border-color:#ccc;color:#666}\
.urkut-confirm-btn-no:hover{background:#f5f5f5;border-color:#999;color:#333}\
.urkut-confirm-btn-yes{background:#e4ebf5;border-color:#a5bce3;color:#3b5998}\
.urkut-confirm-btn-yes:hover{background:#dbe3ef;border-color:#8faad4}\
.urkut-confirm-btn-danger{background:#e4ebf5;border-color:#a5bce3;color:#3b5998}\
.urkut-confirm-btn-danger:hover{background:#dbe3ef;border-color:#8faad4}\
@media(max-width:600px){#urkut-toast-container{top:10px;right:10px;left:10px;width:auto!important;max-width:none!important}.urkut-toast{min-width:0}.urkut-confirm-box{width:95%}}\
';

    var ICONS = { success: '<span style="display:inline-block;width:16px;height:16px;border-radius:3px;background:#e4ebf5;border:1px solid #a5bce3;box-sizing:border-box;text-align:center;line-height:16px;font-size:13px;color:#3b5998;font-weight:bold;">✓</span>', error: '\u274C', warning: '\u26A0\uFE0F', info: '\u2139\uFE0F' };
    var TITLES = { success: 'Sucesso', error: 'Erro', warning: 'Aten\u00E7\u00E3o', info: 'Informa\u00E7\u00E3o' };

    function injectCSS() {
        if (_styleInjected) return;
        _styleInjected = true;
        var s = document.createElement('style');
        s.id = 'urkut-toast-styles';
        s.textContent = CSS;
        (document.head || document.documentElement).appendChild(s);
    }

    function getContainer() {
        if (_container && _container.parentNode) return _container;
        if (!document.body) return null;
        _container = document.getElementById('urkut-toast-container');
        if (!_container) {
            _container = document.createElement('div');
            _container.id = 'urkut-toast-container';
            document.body.appendChild(_container);
        }
        return _container;
    }

    function flushPending() {
        if (_pendingToasts.length === 0) return;
        var c = getContainer();
        if (!c) return;
        var pending = _pendingToasts.slice();
        _pendingToasts = [];
        pending.forEach(function(args) { showToast(args[0], args[1], args[2]); });
    }

    // ================================================================
    // TOAST
    // ================================================================
    function showToast(message, type, duration) {
        type = type || 'info';
        if (typeof duration === 'undefined') {
            duration = type === 'error' ? 5000 : type === 'warning' ? 4500 : 3500;
        }

        injectCSS();
        var c = getContainer();
        if (!c) {
            _pendingToasts.push([message, type, duration]);
            return null;
        }

        var toast = document.createElement('div');
        toast.className = 'urkut-toast urkut-toast-' + type;

        toast.innerHTML = '<span class="urkut-toast-icon">' + (ICONS[type] || ICONS.info) + '</span>'
            + '<div class="urkut-toast-body">'
            + '<div class="urkut-toast-title">' + (TITLES[type] || TITLES.info) + '</div>'
            + '<div class="urkut-toast-msg"></div>'
            + '</div>'
            + '<button class="urkut-toast-close">\u2715</button>';

        toast.querySelector('.urkut-toast-msg').textContent = message;

        toast.querySelector('.urkut-toast-close').onclick = function(e) {
            e.stopPropagation();
            removeToast(toast);
        };

        if (duration > 0) {
            var bar = document.createElement('div');
            bar.className = 'urkut-toast-progress';
            bar.style.width = '100%';
            toast.appendChild(bar);
            requestAnimationFrame(function() {
                bar.style.transitionDuration = duration + 'ms';
                bar.style.width = '0%';
            });
        }

        toast.onclick = function() { removeToast(toast); };
        c.appendChild(toast);

        requestAnimationFrame(function() {
            requestAnimationFrame(function() { toast.classList.add('show'); });
        });

        if (duration > 0) {
            toast._timeout = setTimeout(function() { removeToast(toast); }, duration);
        }

        // Máx 5 toasts
        var all = c.querySelectorAll('.urkut-toast');
        if (all.length > 5) removeToast(all[0]);

        return toast;
    }

    function removeToast(toast) {
        if (toast._removed) return;
        toast._removed = true;
        if (toast._timeout) clearTimeout(toast._timeout);
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
    }

    // ================================================================
    // CONFIRM (modal estilo orkut)
    // ================================================================
    function showConfirm(message, onYes, options) {
        options = options || {};
        injectCSS();

        var overlay = document.createElement('div');
        overlay.className = 'urkut-confirm-overlay';

        var isDanger = !!(options.danger || /excluir|apagar|banir|desbanir|bloquear|desfazer|permanente/i.test(message));
        var yesText = options.yesText || (isDanger ? 'Sim, confirmar' : 'Sim');
        var noText = options.noText || 'Cancelar';
        var title = options.title || 'Confirma\u00E7\u00E3o';

        var box = document.createElement('div');
        box.className = 'urkut-confirm-box';

        var header = document.createElement('div');
        header.className = 'urkut-confirm-header';
        header.innerHTML = '<span class="urkut-confirm-header-text">' + escapeHtml(title) + '</span>';

        var body = document.createElement('div');
        body.className = 'urkut-confirm-body';
        if (options.inputHtml) {
            body.innerHTML = message + options.inputHtml;
        } else {
            body.innerHTML = message;
        }

        var footer = document.createElement('div');
        footer.className = 'urkut-confirm-footer';

        var btnNo = document.createElement('button');
        btnNo.className = 'urkut-confirm-btn urkut-confirm-btn-no';
        btnNo.textContent = noText;

        var btnYes = document.createElement('button');
        btnYes.className = 'urkut-confirm-btn ' + (isDanger ? 'urkut-confirm-btn-danger' : 'urkut-confirm-btn-yes');
        btnYes.textContent = yesText;

        footer.appendChild(btnNo);
        footer.appendChild(btnYes);
        box.appendChild(header);
        box.appendChild(body);
        box.appendChild(footer);
        overlay.appendChild(box);

        function close(result) {
            overlay.classList.remove('show');
            setTimeout(function() { if (overlay.parentNode) overlay.parentNode.removeChild(overlay); }, 300);
            if (result && typeof onYes === 'function') onYes();
        }

        btnNo.onclick = function() { close(false); };
        btnYes.onclick = function() { close(true); };
        overlay.onclick = function(e) { if (e.target === overlay) close(false); };

        // Esc para fechar
        function onKey(e) { if (e.key === 'Escape') { close(false); document.removeEventListener('keydown', onKey); } }
        document.addEventListener('keydown', onKey);

        var appendTarget = (options.container && document.querySelector(options.container)) || document.body;
        if (appendTarget !== document.body) {
            overlay.style.position = 'absolute';
        }
        appendTarget.appendChild(overlay);
        requestAnimationFrame(function() {
            requestAnimationFrame(function() { overlay.classList.add('show'); });
        });
        // Focar no input de senha se existir, senão no botão
        var senhaInput = box.querySelector('input[type="password"]');
        if (senhaInput) {
            senhaInput.focus();
            senhaInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { e.preventDefault(); btnYes.click(); }
            });
        } else {
            btnYes.focus();
        }
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ================================================================
    // Override window.alert
    // ================================================================
    window._originalAlert = window.alert;
    window.alert = function(msg) {
        if (!msg && msg !== 0) return;
        msg = String(msg);
        var type = 'info';
        var lower = msg.toLowerCase();
        if (lower.includes('erro') || lower.includes('error') || lower.includes('falha') || lower.includes('incorreto') || lower.includes('n\u00E3o pode') || lower.includes('n\u00E3o \u00E9 poss\u00EDvel') || lower.includes('expirada') || lower.includes('incompleto')) {
            type = 'error';
        } else if (lower.includes('sucesso') || lower.includes('salvo') || lower.includes('enviado') || lower.includes('enviada') || lower.includes('aceita') || lower.includes('aceito') || lower.includes('aprovado') || lower.includes('aprovada') || lower.includes('desbloqueado') || lower.includes('bloqueado com') || lower.includes('desfeita') || lower.includes('cancelada') || lower.includes('exclu\u00EDdo') || lower.includes('exclu\u00EDda') || lower.includes('cancelado')) {
            type = 'success';
        } else if (lower.includes('aten\u00E7\u00E3o') || lower.includes('aviso') || lower.includes('selecione') || lower.includes('preencha') || lower.includes('digite') || lower.includes('escreva') || lower.includes('precisa') || lower.includes('cole') || lower.includes('vazio') || lower.includes('grande') || lower.includes('m\u00E1x') || lower.includes('em breve') || lower.includes('constru\u00E7\u00E3o') || lower.includes('aguardando')) {
            type = 'warning';
        }
        showToast(msg, type);
    };

    // ================================================================
    // Expor globalmente
    // ================================================================
    window.showToast = showToast;
    window.showConfirm = showConfirm;

    // Flush toasts pendentes quando DOM estiver pronto
    if (document.body) {
        flushPending();
    } else {
        document.addEventListener('DOMContentLoaded', flushPending);
    }
})();
