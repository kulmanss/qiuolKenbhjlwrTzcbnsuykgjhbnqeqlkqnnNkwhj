<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Enviar Sugestão</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .form-box { background: #fdfdfd; border: 1px solid #c0d0e6; padding: 20px; border-radius: 6px; margin-top: 15px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; color: var(--orkut-blue); margin-bottom: 5px; font-size: 12px; }
    .form-group input[type="text"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid #a5bce3; border-radius: 4px; font-size: 13px; font-family: inherit; box-sizing: border-box; }
    .form-group input:focus, .form-group textarea:focus { border-color: var(--orkut-pink); outline: none; }
    .file-upload-box { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px; }
    .file-item { flex: 1; min-width: 150px; background: #f4f7fc; border: 1px dashed #a5bce3; padding: 10px; border-radius: 4px; text-align: center; font-size: 11px; color:#555; position: relative; }
    .file-item input[type="file"] { margin-top: 5px; max-width: 100%; }
    .file-item .preview-img { max-width: 100%; max-height: 80px; margin-top: 6px; border-radius: 4px; display: none; }
    .file-item .remove-img { position: absolute; top: 4px; right: 6px; background: #e74c3c; color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; cursor: pointer; display: none; line-height: 18px; }
    .btn-submit { background: #4caf50; color: #fff; border: none; padding: 12px 25px; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 14px; transition: 0.2s; }
    .btn-submit:hover { background: #388e3c; }
    .btn-submit:disabled { background: #aaa; cursor: not-allowed; }
    .success-msg { background: #e8f5e9; border: 1px solid #a5d6a7; padding: 20px; border-radius: 6px; text-align: center; margin-top: 15px; display: none; }
    .success-msg h3 { color: #2e7d32; margin: 0 0 8px 0; }
    .success-msg p { color: #555; font-size: 13px; margin: 0; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col">
        <div class="breadcrumb"><a href="/profile.php">Início</a> > Enviar Sugestão</div>
        <div class="card">
            <h1 class="orkut-name" style="color: var(--orkut-blue);">💡 Caixa de Sugestões</h1>
            <p style="color: #666; font-size: 13px; line-height: 1.6;">
                Teve uma ideia genial para um novo joguinho? Quer sugerir uma cor, um tema ou uma funcionalidade nova? Descreva sua sugestão abaixo. Se você tiver alguma imagem de referência ou rascunho, sinta-se livre para anexar.
            </p>
            <div class="form-box" id="form-container">
                <form id="sugestao-form">
                    <div class="form-group"><label>Resumo da Ideia</label><input type="text" id="sug-titulo" required placeholder="Ex: Criar um sistema de Sorteio com KutCoins" maxlength="200"></div>
                    <div class="form-group"><label>Como isso funcionaria?</label><textarea id="sug-descricao" rows="5" required placeholder="Escreva os detalhes de como você imagina essa sugestão funcionando na prática..." maxlength="5000"></textarea></div>
                    <div class="form-group">
                        <label>Anexar Imagens / Referências (Opcional - Máx 3 Imagens, 3MB cada)</label>
                        <div class="file-upload-box">
                            <div class="file-item"><b>Anexo 1</b><br><input type="file" class="sug-file" accept="image/*"><img class="preview-img"><button type="button" class="remove-img">&times;</button></div>
                            <div class="file-item"><b>Anexo 2</b><br><input type="file" class="sug-file" accept="image/*"><img class="preview-img"><button type="button" class="remove-img">&times;</button></div>
                            <div class="file-item"><b>Anexo 3</b><br><input type="file" class="sug-file" accept="image/*"><img class="preview-img"><button type="button" class="remove-img">&times;</button></div>
                        </div>
                    </div>
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" id="btn-enviar" class="btn-submit">Enviar Minha Ideia!</button>
                    </div>
                </form>
            </div>
            <div class="success-msg" id="success-msg">
                <h3>✅ Sugestão enviada com sucesso!</h3>
                <p>Obrigado pela sua ideia! Nossa equipe vai analisar e você será notificado sobre o andamento.</p>
                <br>
                <button class="btn-submit" onclick="location.reload()">Enviar outra sugestão</button>
            </div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadLayout({ activePage: 'sugestoes' });

    // Preview de imagens
    document.querySelectorAll('.sug-file').forEach(input => {
        const item = input.closest('.file-item');
        const preview = item.querySelector('.preview-img');
        const removeBtn = item.querySelector('.remove-img');

        input.addEventListener('change', () => {
            const file = input.files[0];
            if (file) {
                if (file.size > 3 * 1024 * 1024) {
                    showToast('Imagem muito grande! Máximo 3MB.', 'error');
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    removeBtn.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        removeBtn.addEventListener('click', () => {
            input.value = '';
            preview.src = '';
            preview.style.display = 'none';
            removeBtn.style.display = 'none';
        });
    });

    // Envio do formulário
    document.getElementById('sugestao-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const titulo = document.getElementById('sug-titulo').value.trim();
        const descricao = document.getElementById('sug-descricao').value.trim();
        if (!titulo || !descricao) return showToast('Preencha todos os campos obrigatórios.', 'error');

        const btn = document.getElementById('btn-enviar');
        btn.disabled = true;
        btn.textContent = 'Enviando...';

        try {
            // Converter imagens para base64
            const imagens = [];
            const fileInputs = document.querySelectorAll('.sug-file');
            for (const input of fileInputs) {
                if (input.files[0]) {
                    const base64 = await fileToBase64(input.files[0]);
                    imagens.push(base64);
                }
            }

            const resp = await fetch('/api/sugestao', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ titulo, descricao, imagens })
            });
            const data = await resp.json();
            if (data.success) {
                document.getElementById('form-container').style.display = 'none';
                document.getElementById('success-msg').style.display = 'block';
                showToast('Sugestão enviada com sucesso!', 'success');
            } else {
                showToast(data.message || 'Erro ao enviar sugestão.', 'error');
                btn.disabled = false;
                btn.textContent = 'Enviar Minha Ideia!';
            }
        } catch(err) {
            console.error(err);
            showToast('Erro de conexão. Tente novamente.', 'error');
            btn.disabled = false;
            btn.textContent = 'Enviar Minha Ideia!';
        }
    });
});

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}
</script>
</body>
</html>
