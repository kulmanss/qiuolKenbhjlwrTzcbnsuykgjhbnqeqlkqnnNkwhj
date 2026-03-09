/**
 * Sistema de @menção para textareas
 * Uso: initMention(textareaElement) ou initMention('#seletor')
 * 
 * Formato armazenado: @[Nome](uid)
 * Formato exibido:    @Nome (com link clicável)
 */

(function() {
    const MENTION_REGEX = /@(\S*)$/;
    let activeTextarea = null;
    let dropdown = null;
    let mentionStart = -1;
    let selectedIndex = 0;
    let results = [];
    let debounceTimer = null;

    // Criar dropdown global (singleton)
    function getDropdown() {
        if (dropdown) return dropdown;
        dropdown = document.createElement('div');
        dropdown.id = 'mention-dropdown';
        dropdown.className = 'mention-dropdown';
        dropdown.style.display = 'none';
        document.body.appendChild(dropdown);

        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            if (dropdown && !dropdown.contains(e.target) && e.target !== activeTextarea) {
                hideDropdown();
            }
        });

        return dropdown;
    }

    function hideDropdown() {
        const dd = getDropdown();
        dd.style.display = 'none';
        dd.innerHTML = '';
        results = [];
        selectedIndex = 0;
        mentionStart = -1;
    }

    function showDropdown(textarea, items) {
        const dd = getDropdown();
        results = items;
        selectedIndex = 0;

        if (items.length === 0) {
            hideDropdown();
            return;
        }

        dd.innerHTML = '';
        items.forEach((item, i) => {
            const row = document.createElement('div');
            row.className = 'mention-item' + (i === 0 ? ' mention-item-active' : '');
            
            const foto = item.foto_perfil || getDefaultAvatar(item.sexo);
            row.innerHTML = `
                <img src="${foto}" class="mention-avatar">
                <span class="mention-name">${escapeHtmlMention(item.nome)}</span>
            `;
            row.addEventListener('mousedown', function(e) {
                e.preventDefault();
                selectMention(textarea, item);
            });
            row.addEventListener('mouseenter', function() {
                dd.querySelectorAll('.mention-item').forEach(el => el.classList.remove('mention-item-active'));
                row.classList.add('mention-item-active');
                selectedIndex = i;
            });
            dd.appendChild(row);
        });

        // Posicionar dropdown abaixo do textarea
        positionDropdown(textarea);
        dd.style.display = 'block';
    }

    function positionDropdown(textarea) {
        const dd = getDropdown();
        const rect = textarea.getBoundingClientRect();
        
        // Posicionar junto ao caret (simplificado: abaixo do textarea)
        dd.style.position = 'absolute';
        dd.style.left = rect.left + window.scrollX + 'px';
        dd.style.top = (rect.bottom + window.scrollY + 2) + 'px';
        dd.style.width = Math.min(rect.width, 320) + 'px';
        dd.style.zIndex = '99999';
    }

    function selectMention(textarea, item) {
        const val = textarea.value;
        const cursorPos = textarea.selectionStart;
        
        // Encontrar o @ que iniciou esta menção
        const beforeCursor = val.substring(0, cursorPos);
        const atIndex = beforeCursor.lastIndexOf('@');
        
        if (atIndex === -1) { hideDropdown(); return; }
        
        // Substituir @query por @[Nome](uid) 
        const mentionTag = '@[' + item.nome + '](' + item.id + ') ';
        const newVal = val.substring(0, atIndex) + mentionTag + val.substring(cursorPos);
        textarea.value = newVal;
        
        // Posicionar cursor após a menção
        const newPos = atIndex + mentionTag.length;
        textarea.selectionStart = textarea.selectionEnd = newPos;
        textarea.focus();
        
        hideDropdown();
    }

    async function fetchMentions(query) {
        try {
            const resp = await fetch('/api/amigos/buscar?q=' + encodeURIComponent(query));
            const data = await resp.json();
            if (data.success) return data.amigos;
        } catch(e) { console.error('Erro buscando menções:', e); }
        return [];
    }

    function handleInput(textarea) {
        const val = textarea.value;
        const cursorPos = textarea.selectionStart;
        const beforeCursor = val.substring(0, cursorPos);
        
        // Verificar se há @ antes do cursor
        const match = beforeCursor.match(MENTION_REGEX);
        
        if (!match) {
            hideDropdown();
            return;
        }

        const query = match[1]; // texto após @
        
        if (query.length < 1) {
            // Mostrar amigos mais recentes ao digitar apenas @
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                const items = await fetchMentions('');
                // Ainda vazio, buscar primeiros amigos
                if (textarea === activeTextarea) {
                    // Buscar com query vazia retorna vazio pela API, então pular
                    hideDropdown();
                }
            }, 200);
            return;
        }

        // Debounce a busca
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            const items = await fetchMentions(query);
            if (textarea === activeTextarea) {
                showDropdown(textarea, items);
            }
        }, 200);
    }

    function handleKeydown(textarea, e) {
        const dd = getDropdown();
        if (dd.style.display === 'none' || results.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % results.length;
            updateSelection(dd);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + results.length) % results.length;
            updateSelection(dd);
        } else if (e.key === 'Enter' || e.key === 'Tab') {
            if (results.length > 0) {
                e.preventDefault();
                selectMention(textarea, results[selectedIndex]);
            }
        } else if (e.key === 'Escape') {
            hideDropdown();
        }
    }

    function updateSelection(dd) {
        const items = dd.querySelectorAll('.mention-item');
        items.forEach((el, i) => {
            el.classList.toggle('mention-item-active', i === selectedIndex);
        });
        // Scroll into view
        if (items[selectedIndex]) {
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    // Converter formato @[Nome](uid) para links clicáveis
    window.renderMentions = function(text) {
        if (!text) return text;
        return text.replace(/@\[([^\]]+)\]\(([^)]+)\)/g, function(match, nome, uid) {
            return '<a href="/profile.php?uid=' + uid + '" class="mention-link" title="' + escapeHtmlMention(nome) + '">@' + escapeHtmlMention(nome) + '</a>';
        });
    };

    function escapeHtmlMention(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // API pública
    window.initMention = function(textareaOrSelector) {
        let textarea;
        if (typeof textareaOrSelector === 'string') {
            textarea = document.querySelector(textareaOrSelector);
        } else {
            textarea = textareaOrSelector;
        }
        if (!textarea) return;

        textarea.addEventListener('input', function() {
            activeTextarea = textarea;
            handleInput(textarea);
        });

        textarea.addEventListener('keydown', function(e) {
            handleKeydown(textarea, e);
        });

        textarea.addEventListener('focus', function() {
            activeTextarea = textarea;
        });

        textarea.addEventListener('blur', function() {
            // Delay para permitir click no dropdown
            setTimeout(() => {
                if (activeTextarea === textarea) {
                    hideDropdown();
                }
            }, 200);
        });
    };

    // Injetar CSS
    const style = document.createElement('style');
    style.textContent = `
        .mention-dropdown {
            background: #fff;
            border: 1px solid #c0d0e6;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-height: 240px;
            overflow-y: auto;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .mention-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            cursor: pointer;
            gap: 10px;
            transition: background 0.1s;
        }
        .mention-item:hover,
        .mention-item-active {
            background: #e8f0fe;
        }
        .mention-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #ddd;
            flex-shrink: 0;
        }
        .mention-name {
            color: #315b9e;
            font-weight: bold;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .mention-link {
            color: #315b9e;
            font-weight: bold;
            text-decoration: none;
        }
        .mention-link:hover {
            text-decoration: underline;
        }
    `;
    document.head.appendChild(style);
})();
