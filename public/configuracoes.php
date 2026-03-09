<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Configurações</title>
<link rel="stylesheet" href="styles/main.css">
<link rel="stylesheet" href="styles/profile.css">
<style>
    .config-section { background: #fdfdfd; border: 1px solid var(--line); border-radius: 4px; padding: 20px; margin-bottom: 20px; }
    .config-title { font-size: 16px; color: var(--title); font-weight: bold; border-bottom: 2px solid var(--orkut-blue); padding-bottom: 5px; margin-bottom: 15px; margin-top: 0; }
    .config-warning { background: #fffdf5; border: 1px solid #ffe8a1; padding: 10px; font-size: 11px; color: #666; border-radius: 3px; margin-bottom: 15px; }
    
    .form-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .form-table td { padding: 10px 5px; border-bottom: 1px dotted #e4ebf5; vertical-align: top; }
    .form-table tr:last-child td { border-bottom: none; }
    .form-label { font-weight: bold; color: #444; width: 45%; }
    .form-select { padding: 5px; border: 1px solid #a5bce3; width: 100%; max-width: 300px; font-size: 11px; }
    .form-input { padding: 6px; border: 1px solid #a5bce3; width: 100%; max-width: 300px; font-size: 12px; box-sizing: border-box; }
    
    .msg-alert { padding: 10px; border-radius: 4px; font-weight: bold; font-size: 12px; margin-bottom: 15px; text-align: center; }
    .msg-success { background: #e4f2e9; border: 1px solid #8bc59e; color: #2a6b2a; }
    .msg-error { background: #ffe6e6; border: 1px solid #cc0000; color: #cc0000; }
    .msg-critical { background: #cc0000; color: #fff; padding: 15px; border-radius: 4px; text-align: center; margin-bottom: 20px; font-size: 13px; }

    .btn-link { background: #f4f7fc; border: 1px solid #c0d0e6; color: var(--link); padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 11px; display: inline-block; transition: 0.2s; cursor: pointer; }
    .btn-link:hover { background: #eef4ff; border-color: var(--orkut-blue); }

    /* Medidor de Força da Senha */
    .strength-meter { height: 5px; width: 100%; max-width: 300px; background: #e4ebf5; border-radius: 3px; margin-top: 5px; overflow: hidden; }
    .strength-fill { height: 100%; width: 0%; transition: width 0.3s, background 0.3s; }
    .strength-text { font-size: 9px; font-weight: bold; margin-top: 3px; }

    /* Minhas Denúncias */
    .den-list { border: 1px solid #e4ebf5; border-radius: 4px; overflow: hidden; }
    .den-item { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-bottom: 1px solid #e4ebf5; background: #fff; cursor: pointer; transition: background 0.15s; }
    .den-item:hover { background: #f0f5ff; }
    .den-item:last-child { border-bottom: none; }
    .den-item-foto { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #c0d0e6; }
    .den-item-info { flex: 1; min-width: 0; }
    .den-item-nome { font-weight: bold; font-size: 12px; color: var(--link); }
    .den-item-motivo { font-size: 11px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 350px; }
    .den-item-data { font-size: 10px; color: #999; }
        .den-item-resp { font-size: 10px; color: #2e7d32; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 350px; }
    .den-item-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
    .den-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; color: #fff; }
    .den-badge-pendente { background: #ff9800; }
    .den-badge-analisando { background: #2196F3; }
    .den-badge-resolvida { background: #4caf50; }
    .den-badge-respondido { background: #9b59b6; }
    .den-badge-rejeitada { background: #cc0000; }
    .den-unread { background: #e6399b; color: #fff; font-size: 9px; font-weight: bold; padding: 1px 6px; border-radius: 8px; animation: pulseBadge 1.5s infinite; }
    @keyframes pulseBadge { 0%,100%{opacity:1;} 50%{opacity:0.6;} }
    .den-empty { padding: 30px; text-align: center; color: #999; font-size: 12px; }

    /* Chat Modal */
    .den-chat-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
    .den-chat-overlay.open { display: flex; }
    .den-chat-modal { background: #fff; border-radius: 8px; width: 600px; max-width: 95vw; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
    .den-chat-header { background: linear-gradient(135deg, var(--orkut-blue), var(--title)); color: #fff; padding: 15px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
    .den-chat-header h3 { margin: 0; font-size: 14px; }
    .den-chat-close { background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; opacity: 0.8; }
    .den-chat-close:hover { opacity: 1; }
    .den-chat-info { background: #fffdf5; border-bottom: 1px solid #ffe8a1; padding: 10px 20px; font-size: 11px; color: #555; }
    .den-chat-info b { color: var(--title); }
    .den-chat-status-row { display: flex; gap: 10px; align-items: center; margin-top: 5px; }
    .den-chat-messages { flex: 1; overflow-y: auto; padding: 15px 20px; min-height: 200px; max-height: 400px; background: #f4f6fb; }
    .den-chat-msg { margin-bottom: 10px; display: flex; }
    .den-chat-msg.from-user { justify-content: flex-end; }
    .den-chat-msg.from-admin { justify-content: flex-start; }
    .den-chat-bubble { max-width: 75%; padding: 8px 12px; border-radius: 12px; font-size: 12px; line-height: 1.4; position: relative; }
    .den-chat-msg.from-user .den-chat-bubble { background: #d4edff; border-bottom-right-radius: 2px; }
    .den-chat-msg.from-admin .den-chat-bubble { background: #fff3cd; border-bottom-left-radius: 2px; border: 1px solid #ffe8a1; }
    .den-chat-bubble-sender { font-size: 10px; font-weight: bold; margin-bottom: 2px; }
    .den-chat-msg.from-user .den-chat-bubble-sender { color: var(--link); }
    .den-chat-msg.from-admin .den-chat-bubble-sender { color: #c77c00; }
    .den-chat-bubble-time { font-size: 9px; color: #999; text-align: right; margin-top: 3px; }
    .den-chat-empty { text-align: center; color: #999; padding: 40px; font-size: 12px; }
    .den-chat-footer { border-top: 1px solid #e4ebf5; padding: 12px 20px; display: flex; gap: 8px; background: #fdfdfd; border-radius: 0 0 8px 8px; }
    .den-chat-footer.disabled { opacity: 0.6; pointer-events: none; }
    .den-chat-input { flex: 1; padding: 8px 12px; border: 1px solid #a5bce3; border-radius: 20px; font-size: 12px; outline: none; resize: none; }
    .den-chat-input:focus { border-color: var(--orkut-blue); }
    .den-chat-send { background: var(--orkut-blue); color: #fff; border: none; border-radius: 20px; padding: 8px 18px; font-size: 12px; font-weight: bold; cursor: pointer; }
    .den-chat-send:hover { background: var(--title); }
    .den-chat-closed-msg { text-align: center; padding: 10px; font-size: 11px; color: #888; background: #f9f9f9; border-top: 1px solid #e4ebf5; border-radius: 0 0 8px 8px; }
    .den-resp-admin { background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 6px; padding: 8px 12px; margin-top: 5px; font-size: 11px; color: #2e7d32; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="profile.php">Início</a> > Configurações da Conta</div>

        <div class="card">
            <h1 class="orkut-name" style="font-size: 22px; margin-bottom: 20px;">Configurações</h1>

            <div id="msgArea"></div>
            
            <div class="config-warning">
                ⚠️ <b>Área Sensível:</b> Tome cuidado ao alterar estas opções. Mudanças drásticas podem resultar em você não ver outras pessoas online, pessoas não conseguirem te encontrar nas buscas, ou você não ser notificado das atividades no seu perfil.
            </div>

            <form id="formPrivacidade" onsubmit="return salvarPrivacidade(event);">
                
                <div class="config-section">
                    <h2 class="config-title">1 PARTE - Sobre as pessoas interagir com você</h2>
                    <table class="form-table">
                        <tr>
                            <td class="form-label">Você quer deixar "rastro" que visitou outro perfil?<br><span style="font-size:9px; font-weight:normal; color:#666;">Se 'Não', você também não saberá quem visitou você.</span></td>
                            <td>
                                <select name="visitas_rastro" class="form-select">
                                    <option value="sim">Sim (Deixar rastro e ver visitantes)</option>
                                    <option value="nao">Não (Modo fantasma / Ocultar visitantes)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="form-label">Quem pode escrever recado pra mim?</td>
                            <td>
                                <select name="escrever_recado" class="form-select">
                                    <option value="todos">Todos do Yorkut</option>
                                    <option value="amigos">Apenas Amigos</option>
                                    <option value="ninguem">Ninguém</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="form-label">Quem pode escrever depoimentos pra mim?</td>
                            <td>
                                <select name="escrever_depoimento" class="form-select">
                                    <option value="todos">Todos do Yorkut</option>
                                    <option value="amigos">Apenas Amigos</option>
                                    <option value="ninguem">Ninguém</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="form-label">Quem pode me mandar mensagem privada?<br><span style="font-size:9px; font-weight:normal; color:#666;">Se 'Ninguém', você também não pode enviar.</span></td>
                            <td>
                                <select name="enviar_mensagem" class="form-select">
                                    <option value="amigos">Todos meus amigos</option>
                                    <option value="ninguem">Ninguém</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="form-label">Quem pode mencionar você (comunidades, recados)?<br><span style="font-size:9px; font-weight:normal; color:#666;">Se 'Ninguém', você não poderá mencionar ninguém.</span></td>
                            <td>
                                <select name="mencionar" class="form-select">
                                    <option value="amigos">Todos meus amigos</option>
                                    <option value="ninguem">Ninguém</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="config-section">
                    <h2 class="config-title">2 PARTE - Sobre visualizar informações</h2>
                    <table class="form-table">
                        <tr><td class="form-label">Quem pode ver meus recados?</td><td>
                            <select name="ver_recado" class="form-select"><option value="todos">Todos do Yorkut</option><option value="amigos">Apenas Amigos</option><option value="ninguem">Ninguém</option></select>
                        </td></tr>
                        <tr><td class="form-label">Quem pode ver minhas fotos?</td><td>
                            <select name="ver_foto" class="form-select"><option value="todos">Todos</option><option value="amigos">Apenas Amigos</option><option value="ninguem">Ninguém</option></select>
                        </td></tr>
                        <tr><td class="form-label">Quem pode ver meus vídeos?</td><td>
                            <select name="ver_video" class="form-select"><option value="todos">Todos</option><option value="amigos">Apenas Amigos</option><option value="ninguem">Ninguém</option></select>
                        </td></tr>
                        <tr><td class="form-label">Quem pode ver meus depoimentos?</td><td>
                            <select name="ver_depoimento" class="form-select"><option value="todos">Todos</option><option value="amigos">Apenas Amigos</option><option value="ninguem">Ninguém</option></select>
                        </td></tr>
                        <tr><td class="form-label">Quem pode ver meus amigos?</td><td>
                            <select name="ver_amigos" class="form-select"><option value="todos">Todos</option><option value="amigos">Apenas Amigos</option><option value="ninguem">Ninguém</option></select>
                        </td></tr>
                        <tr><td class="form-label">Quem pode ver minhas comunidades?</td><td>
                            <select name="ver_comunidades" class="form-select"><option value="todos">Todos</option><option value="amigos">Apenas Amigos</option><option value="ninguem">Ninguém</option></select>
                        </td></tr>
                        
                        <tr><td class="form-label">Quem pode ver minhas informações SOCIAL?</td><td>
                            <select name="ver_social" class="form-select"><option value="todos">Todos</option><option value="amigos">Apenas Amigos</option><option value="ninguem">Ninguém</option></select>
                        </td></tr>
                        <tr><td class="form-label">Quem pode ver minhas informações PESSOAL?</td><td>
                            <select name="ver_pessoal" class="form-select"><option value="todos">Todos</option><option value="amigos">Apenas Amigos</option><option value="ninguem">Ninguém</option></select>
                        </td></tr>
                        <tr><td class="form-label">Quem pode ver minhas informações PROFISSIONAL?</td><td>
                            <select name="ver_profissional" class="form-select"><option value="todos">Todos</option><option value="amigos">Apenas Amigos</option><option value="ninguem">Ninguém</option></select>
                        </td></tr>

                        <tr><td class="form-label">Quem pode me ver online?</td><td>
                            <select name="ver_online" class="form-select">
                                <option value="todos">Todos (Você vê todos e todos te veem)</option>
                                <option value="amigos">Apenas Amigos (Você só vê amigos e vice-versa)</option>
                                <option value="ninguem">Ninguém (Você não vê ninguém e ninguém te vê)</option>
                            </select>
                        </td></tr>
                        
                        <tr><td class="form-label">Quem pode votar em mim? (Fã, Confiável...)</td><td>
                            <select name="votos" class="form-select">
                                <option value="todos">Todos (Você também interage com todos)</option>
                                <option value="amigos">Apenas Amigos (Interage apenas com amigos)</option>
                                <option value="ninguem">Ninguém (Você também não pode votar)</option>
                            </select>
                        </td></tr>
                        
                        <tr><td class="form-label">Quem pode me ver nas comunidades?</td><td>
                            <select name="ver_comunidades_presenca" class="form-select">
                                <option value="todos">Todos</option>
                                <option value="amigos">Apenas Amigos</option>
                                <option value="ninguem">Ninguém</option>
                            </select>
                        </td></tr>
                        
                        <tr><td class="form-label">Seu perfil pode ser buscado por outras pessoas?</td><td>
                            <select name="aparecer_pesquisa" class="form-select">
                                <option value="sim">Sim (Aparece nas buscas de pesquisas)</option>
                                <option value="nao">Não (Não aparece nas buscas)</option>
                            </select>
                        </td></tr>
                    </table>
                    
                    <div style="text-align:center; margin-top:20px;">
                        <button type="submit" class="btn-action" style="padding:10px 30px; font-size:14px;">Salvar Preferências</button>
                    </div>
                </div>
            </form>

            <div class="config-section">
                <h2 class="config-title">3 - CONFIGURAÇÕES DA CONTA</h2>
                
                <div style="display:flex; gap:15px; margin-bottom: 25px;">
                    <a href="javascript:void(0);" class="btn-link" onclick="toggleBloqueados();" id="btnBloqueados">🚫 Gerenciar Bloqueados <span id="bloqTotalBadge" style="display:none;"></span></a>
                    <a href="javascript:void(0);" class="btn-link" onclick="toggleMinhasDenuncias();" id="btnMinhasDen">⚠️ Ver minhas Denúncias <span id="denTotalBadge" style="display:none;"></span></a>
                </div>

                <!-- Seção Gerenciar Bloqueados -->
                <div id="secaoBloqueados" style="display:none; margin-bottom:25px;">
                    <div style="border: 1px solid #e4ebf5; padding: 15px; border-radius: 4px; background: #fdfdfd;">
                        <h3 style="margin-top:0; color:var(--title); font-size:13px; border-bottom: 1px solid #e4ebf5; padding-bottom: 8px; display:flex; justify-content:space-between; align-items:center;">
                            🚫 Usuários Bloqueados
                            <span id="bloqCountLabel" style="font-size:10px; color:#999; font-weight:normal;"></span>
                        </h3>
                        <div id="bloqListContainer">
                            <div style="text-align:center; padding:20px; color:#999; font-size:12px;">Carregando...</div>
                        </div>
                    </div>
                </div>

                <!-- Seção Minhas Denúncias -->
                <div id="minhasDenuncias" style="display:none; margin-bottom:25px;">
                    <div style="border: 1px solid #e4ebf5; padding: 15px; border-radius: 4px; background: #fdfdfd;">
                        <h3 style="margin-top:0; color:var(--title); font-size:13px; border-bottom: 1px solid #e4ebf5; padding-bottom: 8px; display:flex; justify-content:space-between; align-items:center;">
                            📋 Minhas Denúncias (Usuários)
                            <span id="denCountLabel" style="font-size:10px; color:#999; font-weight:normal;"></span>
                        </h3>
                        <div id="denListContainer">
                            <div style="text-align:center; padding:20px; color:#999; font-size:12px;">Carregando...</div>
                        </div>
                    </div>
                    <div style="border: 1px solid #e4ebf5; padding: 15px; border-radius: 4px; background: #fdfdfd; margin-top:15px;">
                        <h3 style="margin-top:0; color:var(--title); font-size:13px; border-bottom: 1px solid #e4ebf5; padding-bottom: 8px; display:flex; justify-content:space-between; align-items:center;">
                            🏠 Minhas Denúncias (Comunidades)
                            <span id="denCommCountLabel" style="font-size:10px; color:#999; font-weight:normal;"></span>
                        </h3>
                        <div id="denCommListContainer">
                            <div style="text-align:center; padding:20px; color:#999; font-size:12px;">Carregando...</div>
                        </div>
                    </div>
                </div>

                <!-- Chat Modal -->
                <div class="den-chat-overlay" id="denChatOverlay">
                    <div class="den-chat-modal">
                        <div class="den-chat-header">
                            <h3 id="denChatTitle">💬 Chat - Denúncia #0</h3>
                            <button class="den-chat-close" onclick="fecharChat();">✕</button>
                        </div>
                        <div class="den-chat-info" id="denChatInfo"></div>
                        <div class="den-chat-messages" id="denChatMessages">
                            <div class="den-chat-empty">Carregando mensagens...</div>
                        </div>
                        <div class="den-chat-footer" id="denChatFooter">
                            <input type="text" class="den-chat-input" id="denChatInput" placeholder="Digite sua mensagem..." maxlength="1000" onkeydown="if(event.key==='Enter')enviarMsgDenuncia();">
                            <button class="den-chat-send" onclick="enviarMsgDenuncia();">Enviar</button>
                        </div>
                        <div style="padding:10px 20px 15px; text-align:center; border-top:1px solid #eee;">
                            <button id="btnDesistirDenuncia" onclick="desistirDenuncia();" style="background:none; border:1px solid #c00; color:#c00; padding:6px 18px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; transition:all .2s;" onmouseover="this.style.background='#c00';this.style.color='#fff';" onmouseout="this.style.background='none';this.style.color='#c00';">Desistir da denúncia</button>
                        </div>
                    </div>
                </div>

                <!-- Chat Modal Comunidade -->
                <div class="den-chat-overlay" id="denCommChatOverlay">
                    <div class="den-chat-modal">
                        <div class="den-chat-header">
                            <h3 id="denCommChatTitle">💬 Chat - Denúncia Comunidade #0</h3>
                            <button class="den-chat-close" onclick="fecharChatComm();">✕</button>
                        </div>
                        <div class="den-chat-info" id="denCommChatInfo"></div>
                        <div class="den-chat-messages" id="denCommChatMessages">
                            <div class="den-chat-empty">Carregando mensagens...</div>
                        </div>
                        <div class="den-chat-footer" id="denCommChatFooter">
                            <input type="text" class="den-chat-input" id="denCommChatInput" placeholder="Digite sua mensagem..." maxlength="1000" onkeydown="if(event.key==='Enter')enviarMsgDenunciaComm();">
                            <button class="den-chat-send" onclick="enviarMsgDenunciaComm();">Enviar</button>
                        </div>
                        <div style="padding:10px 20px 15px; text-align:center; border-top:1px solid #eee;">
                            <button onclick="desistirDenunciaComm();" style="background:none; border:1px solid #c00; color:#c00; padding:6px 18px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; transition:all .2s;" onmouseover="this.style.background='#c00';this.style.color='#fff';" onmouseout="this.style.background='none';this.style.color='#c00';">Desistir da denúncia</button>
                        </div>
                    </div>
                </div>

                <div style="border: 1px solid #e4ebf5; padding: 15px; border-radius: 4px; background: #fdfdfd; margin-bottom: 25px;">
                    <h3 style="margin-top:0; color:var(--title); font-size:13px;">Desejo mudar a senha da minha conta</h3>
                    <form id="formSenha" onsubmit="return alterarSenha(event);">
                        <table class="form-table" style="width: auto;">
                            <tr>
                                <td style="vertical-align: middle;">Senha Original:</td>
                                <td><input type="password" name="senha_atual" id="senhaAtual" class="form-input" required></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Nova Senha:</td>
                                <td>
                                    <input type="password" name="nova_senha" id="novaSenha" class="form-input" required oninput="checkStrength()">
                                    <div class="strength-meter"><div id="strengthFill" class="strength-fill"></div></div>
                                    <div id="strengthText" class="strength-text" style="color:#999;">Digite a senha...</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Repita a Nova Senha:</td>
                                <td><input type="password" name="nova_senha_conf" id="novaSenhaConf" class="form-input" required></td>
                            </tr>
                        </table>
                        <button type="submit" class="btn-action" style="margin-top: 10px;">Alterar Senha</button>
                    </form>
                </div>

                <div id="secaoExcluir" style="border: 1px solid #ffcccc; padding: 15px; border-radius: 4px; background: #fffdfd;">
                    <!-- Preenchido dinamicamente por renderSecaoExcluir() -->
                </div>

            </div>

        </div>
    </div>
</div>

<script>
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ============================================
// CARREGAR CONFIGURAÇÕES ATUAIS
// ============================================
async function carregarConfiguracoes() {
    try {
        const resp = await fetch('/api/configuracoes');
        const data = await resp.json();
        if (!data.success) return;

        const config = data.config;
        const form = document.getElementById('formPrivacidade');

        // Preencher todos os selects com os valores salvos
        const campos = [
            'visitas_rastro', 'escrever_recado', 'escrever_depoimento',
            'enviar_mensagem', 'mencionar', 'ver_recado', 'ver_foto',
            'ver_video', 'ver_depoimento', 'ver_amigos', 'ver_comunidades',
            'ver_social', 'ver_pessoal', 'ver_profissional', 'ver_online',
            'votos', 'ver_comunidades_presenca', 'aparecer_pesquisa'
        ];

        campos.forEach(campo => {
            const el = form.querySelector('[name="' + campo + '"]');
            if (el && config[campo]) {
                el.value = config[campo];
            }
        });

        // Renderizar seção de exclusão com base no estado atual
        renderSecaoExcluir(config.conta_excluir_em);
    } catch(err) {
        console.error('Erro ao carregar configurações:', err);
    }
}

// ============================================
// SALVAR PRIVACIDADE
// ============================================
async function salvarPrivacidade(e) {
    e.preventDefault();
    const form = document.getElementById('formPrivacidade');
    const formData = new FormData(form);
    const dados = {};
    formData.forEach((val, key) => { dados[key] = val; });

    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Salvando...';

    try {
        const resp = await fetch('/api/configuracoes/salvar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        const data = await resp.json();
        showMsg(data.success ? 'success' : 'error', data.message);
    } catch(err) {
        showMsg('error', 'Erro de conexão.');
    }

    btn.disabled = false; btn.textContent = 'Salvar Preferências';
    return false;
}

// ============================================
// ALTERAR SENHA
// ============================================
async function alterarSenha(e) {
    e.preventDefault();
    const senhaAtual = document.getElementById('senhaAtual').value;
    const novaSenha = document.getElementById('novaSenha').value;
    const novaSenhaConf = document.getElementById('novaSenhaConf').value;

    if (novaSenha !== novaSenhaConf) {
        showMsg('error', 'As senhas não coincidem!');
        return false;
    }

    if (novaSenha.length < 4) {
        showMsg('error', 'A nova senha deve ter no mínimo 4 caracteres.');
        return false;
    }

    const btn = document.querySelector('#formSenha button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Alterando...';

    try {
        const resp = await fetch('/api/configuracoes/alterar-senha', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ senha_atual: senhaAtual, nova_senha: novaSenha })
        });
        const data = await resp.json();
        showMsg(data.success ? 'success' : 'error', data.message);
        if (data.success) {
            document.getElementById('formSenha').reset();
            document.getElementById('strengthFill').style.width = '0%';
            document.getElementById('strengthText').innerText = 'Digite a senha...';
            document.getElementById('strengthText').style.color = '#999';
        }
    } catch(err) {
        showMsg('error', 'Erro de conexão.');
    }

    btn.disabled = false; btn.textContent = 'Alterar Senha';
    return false;
}

// ============================================
// RENDERIZAR SEÇÃO DE EXCLUSÃO DE CONTA
// ============================================
function renderSecaoExcluir(contaExcluirEm) {
    var el = document.getElementById('secaoExcluir');
    var html = '';
    if (contaExcluirEm) {
        // Exclusão agendada - mostrar countdown
        var excluirDate = new Date(contaExcluirEm.replace(' ', 'T'));
        var agora = new Date();
        var diffMs = excluirDate.getTime() - agora.getTime();
        var diffH = Math.max(0, Math.floor(diffMs / (1000 * 60 * 60)));
        var diffM = Math.max(0, Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60)));

        html += '<h3 style="margin-top:0; color:#cc0000; font-size:13px;">⏳ Exclusão Agendada</h3>';
        html += '<div style="background:#fff3f3; border:1px solid #ffcccc; border-radius:4px; padding:12px; margin-bottom:12px;">';
        html += '  <p style="font-size:12px; color:#cc0000; margin:0 0 8px 0; font-weight:bold;">Sua conta está programada para ser excluída permanentemente!</p>';
        if (diffMs > 0) {
            html += '  <p style="font-size:11px; color:#666; margin:0;">Tempo restante: <b style="color:#cc0000;">' + diffH + 'h ' + diffM + 'min</b></p>';
            html += '  <p style="font-size:10px; color:#999; margin:5px 0 0 0;">Data prevista: ' + formatDateExcluir(contaExcluirEm) + '</p>';
        } else {
            html += '  <p style="font-size:11px; color:#cc0000; margin:0; font-weight:bold;">O prazo já expirou — sua conta será removida em breve.</p>';
        }
        html += '</div>';
        if (diffMs > 0) {
            html += '<button type="button" onclick="cancelarExclusaoConta()" style="background:#fff; border:1px solid #090; color:#090; padding:6px 16px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; font-weight:bold; transition:all .2s;" onmouseover="this.style.background=\'#090\';this.style.color=\'#fff\';" onmouseout="this.style.background=\'#fff\';this.style.color=\'#090\';">Cancelar Exclusão</button>';
        }
    } else {
        // Nenhuma exclusão agendada - mostrar formulário
        html += '<h3 style="margin-top:0; color:#cc0000; font-size:13px;">Desejo excluir minha conta</h3>';
        html += '<p style="font-size:11px; color:#444; line-height:1.4; margin-bottom:15px;">';
        html += '  <b>ATENÇÃO O PROCESSO DE EXCLUSÃO É PERMANENTE E NÃO TERÁ REATIVAÇÃO!</b><br>';
        html += '  Seu perfil junto com todas as informações, fotos, recados e comunidades serão deletados para sempre.<br>';
        html += '  Ao confirmar, começará uma <b>contagem regressiva de 24 horas</b>. Após isso, todos os dados serão apagados permanentemente.';
        html += '</p>';
        html += '<input type="password" id="senhaExclusao" class="form-input" placeholder="Digite sua senha para confirmar" style="max-width:250px; margin-right:10px;">';
        html += '<button type="button" onclick="excluirConta()" class="btn-action" style="background:#cc0000; border-color:#990000; color:#fff;">Iniciar Exclusão (24h)</button>';
    }
    el.innerHTML = html;
}

function formatDateExcluir(dateStr) {
    if (!dateStr) return '';
    var meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    var parts = dateStr.split(/[\-\s:]/);
    if (parts.length >= 5) {
        var day = parseInt(parts[2], 10);
        var month = parseInt(parts[1], 10) - 1;
        return String(day).padStart(2,'0') + ' de ' + meses[month] + ' de ' + parts[0] + ' as ' + parts[3] + ':' + parts[4];
    }
    return dateStr;
}

// ============================================
// EXCLUIR CONTA
// ============================================
function excluirConta() {
    var senha = document.getElementById('senhaExclusao').value;
    if (!senha) { showMsg('error', 'Digite sua senha para confirmar.'); return; }

    showConfirm('ATENÇÃO! Tem certeza absoluta que deseja agendar a exclusão permanente da sua conta?', async function() {
        try {
            const resp = await fetch('/api/configuracoes/excluir-conta', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ senha: senha })
            });
            const data = await resp.json();
            if (data.success) {
                showMsg('success', data.message);
                renderSecaoExcluir(data.conta_excluir_em);
            } else {
                showMsg('error', data.message);
            }
        } catch(err) {
            showMsg('error', 'Erro de conexão.');
        }
    });
}

// ============================================
// CANCELAR EXCLUSÃO DE CONTA
// ============================================
async function cancelarExclusaoConta() {
    try {
        const resp = await fetch('/api/configuracoes/cancelar-exclusao', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await resp.json();
        if (data.success) {
            showMsg('success', data.message);
            renderSecaoExcluir(null);
        } else {
            showMsg('error', data.message);
        }
    } catch(err) {
        showMsg('error', 'Erro de conexão.');
    }
}

// ============================================
// MEDIDOR DE FORÇA DA SENHA
// ============================================
function checkStrength() {
    let pwd = document.getElementById('novaSenha').value;
    let fill = document.getElementById('strengthFill');
    let txt = document.getElementById('strengthText');
    
    let strength = 0;
    if (pwd.length >= 6) strength += 25;
    if (pwd.length >= 10) strength += 25;
    if (/[A-Z]/.test(pwd)) strength += 25;
    if (/[0-9]/.test(pwd) || /[^A-Za-z0-9]/.test(pwd)) strength += 25;
    
    fill.style.width = strength + '%';
    
    if (pwd.length === 0) {
        fill.style.width = '0%';
        txt.innerText = 'Digite a senha...';
        txt.style.color = '#999';
    } else if (strength <= 25) {
        fill.style.background = '#cc0000';
        txt.innerText = 'Fraca';
        txt.style.color = '#cc0000';
    } else if (strength <= 50) {
        fill.style.background = '#ff9800';
        txt.innerText = 'Razoável';
        txt.style.color = '#ff9800';
    } else if (strength <= 75) {
        fill.style.background = '#4caf50';
        txt.innerText = 'Boa';
        txt.style.color = '#4caf50';
    } else {
        fill.style.background = '#2196F3';
        txt.innerText = 'Forte';
        txt.style.color = '#2196F3';
    }
}

// ============================================
// HELPER: MOSTRAR MENSAGEM
// ============================================
function showMsg(type, text) {
    const area = document.getElementById('msgArea');
    if (type === 'critical') {
        area.innerHTML = '<div class="msg-critical">' + text + '</div>';
    } else {
        area.innerHTML = '<div class="msg-alert msg-' + type + '">' + text + '</div>';
    }
    area.scrollIntoView({ behavior: 'smooth' });
    if (type !== 'critical') {
        setTimeout(() => { area.innerHTML = ''; }, 5000);
    }
}

// Carregar ao iniciar
document.addEventListener('DOMContentLoaded', function() {
    carregarConfiguracoes();
    // Se veio do bloqueio, abre a seção de bloqueados automaticamente
    if (new URLSearchParams(window.location.search).get('bloqueados') === '1') {
        _bloqueadosAberto = true;
        document.getElementById('secaoBloqueados').style.display = 'block';
        carregarBloqueados();
        showToast('Usuário bloqueado com sucesso.', 'success');
    }
    // Se veio da criação de denúncia, abre a seção de denúncias automaticamente
    if (new URLSearchParams(window.location.search).get('denuncias') === '1') {
        _denunciasAberto = true;
        document.getElementById('minhasDenuncias').style.display = 'block';
        carregarMinhasDenuncias();
        const hasCommDenId = new URLSearchParams(window.location.search).get('dcid');
        if (hasCommDenId) {
            showToast('Denúncia de comunidade enviada! Nossa equipe irá analisar.', 'success');
        } else {
            showToast('Denúncia enviada com sucesso! Nossa equipe irá analisar.', 'success');
        }
    }
});

// ============================================
// GERENCIAR BLOQUEADOS
// ============================================
let _bloqueadosAberto = false;
let _bloqueadosData = [];

function toggleBloqueados() {
    const sec = document.getElementById('secaoBloqueados');
    _bloqueadosAberto = !_bloqueadosAberto;
    if (_bloqueadosAberto) {
        sec.style.display = 'block';
        carregarBloqueados();
    } else {
        sec.style.display = 'none';
    }
}

async function carregarBloqueados() {
    try {
        const resp = await fetch('/api/bloqueados');
        const data = await resp.json();
        if (!data.success) return;
        _bloqueadosData = data.bloqueados || [];
        renderBloqueadosList();
    } catch(err) {
        console.error('Erro carregar bloqueados:', err);
    }
}

function renderBloqueadosList() {
    const container = document.getElementById('bloqListContainer');
    const countLabel = document.getElementById('bloqCountLabel');
    const totalBadge = document.getElementById('bloqTotalBadge');

    if (_bloqueadosData.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:20px; color:#999; font-size:12px;">Você não bloqueou nenhum usuário.</div>';
        countLabel.textContent = '';
        if (totalBadge) totalBadge.style.display = 'none';
        return;
    }

    countLabel.textContent = _bloqueadosData.length + ' usuário(s)';
    if (totalBadge) {
        totalBadge.style.display = 'inline';
        totalBadge.style.background = '#c00';
        totalBadge.style.color = '#fff';
        totalBadge.style.fontSize = '9px';
        totalBadge.style.padding = '2px 6px';
        totalBadge.style.borderRadius = '10px';
        totalBadge.style.marginLeft = '5px';
        totalBadge.textContent = _bloqueadosData.length;
    }

    let html = '';
    const destaqueUid = new URLSearchParams(window.location.search).get('uid');
    _bloqueadosData.forEach(b => {
        const foto = b.foto_perfil || (b.sexo === 'Feminino' ? '/img/avatar_female.png' : '/img/avatar_male.png');
        const dataFormatada = b.criado_em ? formatarDataBloq(b.criado_em) : '';
        const isDestaque = destaqueUid && String(b.user_id) === String(destaqueUid);
        html += `
            <div data-bloq-uid="${b.user_id}" style="display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #f0f0f0;${isDestaque ? ' background:#fff8e1; border-left:3px solid #f5a623; padding-left:10px; border-radius:4px;' : ''}" class="${isDestaque ? 'bloq-destaque' : ''}">
                <img src="${escHtmlBloq(foto)}" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #ddd;">
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:bold; font-size:12px; color:var(--title);">${escHtmlBloq(b.nome)}</div>
                    <div style="font-size:10px; color:#999;">Bloqueado em ${dataFormatada}</div>
                </div>
                <button onclick="desbloquearConfig('${b.user_id}')" style="background:#fff; border:1px solid #c00; color:#c00; padding:5px 12px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; transition:all .2s;" onmouseover="this.style.background='#c00';this.style.color='#fff';" onmouseout="this.style.background='#fff';this.style.color='#c00';">
                    Desbloquear
                </button>
            </div>`;
    });
    container.innerHTML = html;

    // Scroll e animação de destaque no usuário recém-bloqueado
    if (destaqueUid) {
        const el = container.querySelector('.bloq-destaque');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.style.transition = 'background 0.5s ease';
            setTimeout(() => {
                el.style.background = '#fffde7';
                setTimeout(() => { el.style.background = '#fff8e1'; }, 600);
                setTimeout(() => { el.style.background = '#fffde7'; }, 1200);
                setTimeout(() => { el.style.background = ''; el.style.borderLeft = ''; el.style.paddingLeft = ''; }, 3000);
            }, 300);
        }
    }
}

async function desbloquearConfig(bloqueadoId) {
    showConfirm('Deseja desbloquear este usuário?', async function() {
        try {
            const resp = await fetch('/api/desbloquear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bloqueado_id: bloqueadoId })
            });
            const data = await resp.json();
            if (data.success) {
                carregarBloqueados();
            } else {
                alert(data.message || 'Erro ao desbloquear.');
            }
        } catch(err) {
            alert('Erro de conexão.');
        }
    });
}

function formatarDataBloq(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const pad = n => String(n).padStart(2, '0');
    return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

function escHtmlBloq(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ============================================
// MINHAS DENÚNCIAS
// ============================================
let _denunciasData = [];
let _denunciasAberto = false;
let _chatDenunciaId = null;
let _chatPollInterval = null;

function toggleMinhasDenuncias() {
    const sec = document.getElementById('minhasDenuncias');
    _denunciasAberto = !_denunciasAberto;
    if (_denunciasAberto) {
        sec.style.display = 'block';
        carregarMinhasDenuncias();
    } else {
        sec.style.display = 'none';
    }
}

async function carregarMinhasDenuncias() {
    try {
        const resp = await fetch('/api/minhas-denuncias');
        const data = await resp.json();
        if (!data.success) return;
        _denunciasData = data.denuncias;
        renderDenunciasList();
    } catch(err) {
        console.error('Erro carregar denúncias:', err);
    }
    // Carregar denúncias de comunidades também
    try {
        const resp2 = await fetch('/api/minhas-denuncias-comunidades');
        const data2 = await resp2.json();
        if (data2.success) {
            _denunciasCommData = data2.denuncias;
            renderDenunciasCommList();
        }
    } catch(err) {
        console.error('Erro carregar denúncias comunidades:', err);
    }
}

function renderDenunciasList() {
    const container = document.getElementById('denListContainer');
    const countLabel = document.getElementById('denCountLabel');
    const totalBadge = document.getElementById('denTotalBadge');

    if (_denunciasData.length === 0) {
        container.innerHTML = '<div class="den-empty">Você ainda não fez nenhuma denúncia.</div>';
        countLabel.textContent = '';
        totalBadge.style.display = 'none';
        return;
    }

    const totalNaoLidas = _denunciasData.reduce((s, d) => s + (d.mensagens_nao_lidas || 0), 0);
    countLabel.textContent = _denunciasData.length + ' denúncia(s)';
    if (totalNaoLidas > 0) {
        totalBadge.style.display = 'inline';
        totalBadge.className = 'den-unread';
        totalBadge.textContent = totalNaoLidas + ' nova(s)';
    } else {
        totalBadge.style.display = 'none';
    }

    let html = '<div class="den-list">';
    _denunciasData.forEach(d => {
        const statusClass = 'den-badge-' + d.status;
        const statusLabel = d.status.charAt(0).toUpperCase() + d.status.slice(1);
        const dataFormatada = formatarDataDen(d.criado_em);
        const motivoShort = d.motivo.length > 60 ? d.motivo.substring(0, 60) + '...' : d.motivo;
        const foto = d.denunciado_foto || '/img/default-avatar.png';

        const destaqueDid = new URLSearchParams(window.location.search).get('did');
        const isDestaque = destaqueDid && String(d.id) === String(destaqueDid);
        html += '<div class="den-item' + (isDestaque ? ' den-item-destaque' : '') + '" data-den-id="' + d.id + '" onclick="abrirChatDenuncia(' + d.id + ');"';
        if (isDestaque) html += ' style="background:#fff8e1; border-left:3px solid #f5a623; border-radius:4px;"';
        html += '>';
        html += '  <img src="' + foto + '" class="den-item-foto" onerror="this.src=\'/img/default-avatar.png\'">';
        html += '  <div class="den-item-info">';
        html += '    <div class="den-item-nome">Contra: ' + escHtml(d.denunciado_nome) + '</div>';
        html += '    <div class="den-item-motivo">' + escHtml(motivoShort) + '</div>';
        html += '    <div class="den-item-data">📅 ' + dataFormatada + '</div>';
        if (d.ultima_resposta_equipe) {
            const respostaShort = d.ultima_resposta_equipe.length > 60 ? d.ultima_resposta_equipe.substring(0, 60) + '...' : d.ultima_resposta_equipe;
            html += '    <div class="den-item-resp">🛡️ <b>Resposta da equipe:</b> ' + escHtml(respostaShort) + '</div>';
        }
        html += '  </div>';
        html += '  <div class="den-item-right">';
        html += '    <span class="den-badge ' + statusClass + '">' + statusLabel + '</span>';
        if (d.mensagens_nao_lidas > 0) {
            html += '    <span class="den-unread">💬 ' + d.mensagens_nao_lidas + '</span>';
        } else if (d.total_mensagens > 0) {
            html += '    <span style="font-size:10px;color:#999;">💬 ' + d.total_mensagens + '</span>';
        }
        html += '  </div>';
        html += '</div>';
    });
    html += '</div>';
    container.innerHTML = html;

    // Scroll e animação de destaque na denúncia recém-criada
    const destaqueDid = new URLSearchParams(window.location.search).get('did');
    if (destaqueDid) {
        const el = container.querySelector('.den-item-destaque');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.style.transition = 'background 0.5s ease';
            setTimeout(() => {
                el.style.background = '#fffde7';
                setTimeout(() => { el.style.background = '#fff8e1'; }, 600);
                setTimeout(() => { el.style.background = '#fffde7'; }, 1200);
                setTimeout(() => { el.style.background = ''; el.style.borderLeft = ''; }, 3000);
            }, 300);
        }
    }
}

async function abrirChatDenuncia(denId) {
    _chatDenunciaId = denId;
    const overlay = document.getElementById('denChatOverlay');
    overlay.classList.add('open');
    document.getElementById('denChatTitle').textContent = '💬 Chat - Denúncia #' + denId;
    document.getElementById('denChatMessages').innerHTML = '<div class="den-chat-empty">Carregando mensagens...</div>';
    document.getElementById('denChatInfo').innerHTML = 'Carregando...';
    document.getElementById('denChatFooter').classList.remove('disabled');
    document.getElementById('denChatInput').value = '';

    await carregarChatMensagens();

    // Polling a cada 5 segundos
    if (_chatPollInterval) clearInterval(_chatPollInterval);
    _chatPollInterval = setInterval(carregarChatMensagens, 5000);
}

async function carregarChatMensagens() {
    if (!_chatDenunciaId) return;
    try {
        const resp = await fetch('/api/denuncia-chat/' + _chatDenunciaId);
        const data = await resp.json();
        if (!data.success) return;

        const den = data.denuncia;
        const statusLabel = den.status.charAt(0).toUpperCase() + den.status.slice(1);
        const statusClass = 'den-badge-' + den.status;

        let infoHtml = '<b>Denunciado:</b> ' + escHtml(den.denunciado_id ? '' : '') + ' | ';
        // fetch denunciado name from stored data
        const denData = _denunciasData.find(d => d.id === _chatDenunciaId);
        if (denData) {
            infoHtml = '<b>Denunciado:</b> ' + escHtml(denData.denunciado_nome) + ' | ';
        }
        infoHtml += '<b>Motivo:</b> ' + escHtml(den.motivo.length > 80 ? den.motivo.substring(0, 80) + '...' : den.motivo);
        infoHtml += '<div class="den-chat-status-row"><b>Status:</b> <span class="den-badge ' + statusClass + '">' + statusLabel + '</span>';
        infoHtml += ' <span style="font-size:10px;color:#999;">Aberta em ' + formatarDataDen(den.criado_em) + '</span></div>';

        // Mostrar última resposta da equipe (última mensagem admin do chat)
        const lastAdminMsg = data.mensagens.filter(m => m.is_admin).pop();
        if (lastAdminMsg) {
            infoHtml += '<div class="den-resp-admin">🛡️ <b>Resposta da equipe:</b> ' + escHtml(lastAdminMsg.mensagem) + '</div>';
        }

        document.getElementById('denChatInfo').innerHTML = infoHtml;

        // Render messages
        const msgs = data.mensagens;
        const container = document.getElementById('denChatMessages');

        if (msgs.length === 0) {
            container.innerHTML = '<div class="den-chat-empty">Nenhuma mensagem ainda.<br><span style="font-size:10px;">Envie uma mensagem para a equipe do Yorkut sobre sua denúncia.</span></div>';
        } else {
            let html = '';
            msgs.forEach(m => {
                const cls = m.is_admin ? 'from-admin' : 'from-user';
                const senderName = m.is_admin ? '🛡️ Equipe Yorkut' : 'Você';
                html += '<div class="den-chat-msg ' + cls + '">';
                html += '  <div class="den-chat-bubble">';
                html += '    <div class="den-chat-bubble-sender">' + senderName + '</div>';
                html += '    ' + escHtml(m.mensagem);
                html += '    <div class="den-chat-bubble-time">' + formatarDataDen(m.criado_em) + '</div>';
                html += '  </div>';
                html += '</div>';
            });
            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        }

        // Disable input if closed
        const footer = document.getElementById('denChatFooter');
        if (den.status === 'resolvida' || den.status === 'rejeitada') {
            footer.classList.add('disabled');
            const closedMsg = document.querySelector('.den-chat-closed-msg');
            if (!closedMsg) {
                const msg = document.createElement('div');
                msg.className = 'den-chat-closed-msg';
                msg.textContent = 'Esta denúncia foi ' + (den.status === 'resolvida' ? 'resolvida' : 'rejeitada') + '. Não é possível enviar novas mensagens.';
                footer.parentNode.insertBefore(msg, footer.nextSibling);
            }
            footer.style.display = 'none';
        } else {
            footer.classList.remove('disabled');
            footer.style.display = 'flex';
            const closedMsg = document.querySelector('.den-chat-closed-msg');
            if (closedMsg) closedMsg.remove();
        }

        // Update unread in main list
        if (_denunciasAberto) {
            const denItem = _denunciasData.find(d => d.id === _chatDenunciaId);
            if (denItem) {
                denItem.mensagens_nao_lidas = 0;
                renderDenunciasList();
            }
        }

    } catch(err) {
        console.error('Erro carregar chat:', err);
    }
}

async function enviarMsgDenuncia() {
    const input = document.getElementById('denChatInput');
    const msg = input.value.trim();
    if (!msg || !_chatDenunciaId) return;

    input.disabled = true;
    try {
        const resp = await fetch('/api/denuncia-chat/' + _chatDenunciaId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensagem: msg })
        });
        const data = await resp.json();
        if (data.success) {
            input.value = '';
            await carregarChatMensagens();
        } else {
            alert(data.message || 'Erro ao enviar.');
        }
    } catch(err) {
        alert('Erro de conexão.');
    }
    input.disabled = false;
    input.focus();
}

function fecharChat() {
    _chatDenunciaId = null;
    if (_chatPollInterval) { clearInterval(_chatPollInterval); _chatPollInterval = null; }
    document.getElementById('denChatOverlay').classList.remove('open');
    // Refresh list to update unread counts
    if (_denunciasAberto) carregarMinhasDenuncias();
}

async function desistirDenuncia() {
    if (!_chatDenunciaId) return;
    showConfirm('Tem certeza que deseja desistir desta denúncia? Ela será excluída permanentemente e não poderá ser recuperada.', async function() {
        try {
            const resp = await fetch('/api/denuncia-desistir/' + _chatDenunciaId, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await resp.json();
            if (data.success) {
                fecharChat();
                showToast('Denúncia removida com sucesso.', 'success');
            } else {
                showToast(data.message || 'Erro ao desistir da denúncia.', 'error');
            }
        } catch(err) {
            showToast('Erro de conexão.', 'error');
        }
    }, { danger: true });
}

// ============================================
// DENÚNCIAS DE COMUNIDADES
// ============================================
let _denunciasCommData = [];
let _chatCommDenunciaId = null;
let _chatCommPollInterval = null;

function renderDenunciasCommList() {
    const container = document.getElementById('denCommListContainer');
    const countLabel = document.getElementById('denCommCountLabel');

    if (_denunciasCommData.length === 0) {
        container.innerHTML = '<div class="den-empty">Você ainda não denunciou nenhuma comunidade.</div>';
        countLabel.textContent = '';
        return;
    }

    countLabel.textContent = _denunciasCommData.length + ' denúncia(s)';

    let html = '<div class="den-list">';
    _denunciasCommData.forEach(d => {
        const statusClass = 'den-badge-' + d.status;
        const statusLabel = d.status.charAt(0).toUpperCase() + d.status.slice(1);
        const dataFormatada = formatarDataDen(d.criado_em);
        const motivoShort = d.motivo.length > 60 ? d.motivo.substring(0, 60) + '...' : d.motivo;
        const foto = d.comunidade_foto || '/img/default-community.png';

        const destaqueDcid = new URLSearchParams(window.location.search).get('dcid');
        const isDestaque = destaqueDcid && String(d.id) === String(destaqueDcid);
        html += '<div class="den-item' + (isDestaque ? ' den-item-destaque' : '') + '" onclick="abrirChatDenunciaComm(' + d.id + ');"';
        if (isDestaque) html += ' style="background:#fff8e1; border-left:3px solid #f5a623; border-radius:4px;"';
        html += '>';
        html += '  <img src="' + foto + '" class="den-item-foto" onerror="this.src=\'/img/default-community.png\'" style="border-radius:4px;">';
        html += '  <div class="den-item-info">';
        html += '    <div class="den-item-nome">🏠 Comunidade: ' + escHtml(d.comunidade_nome) + '</div>';
        html += '    <div class="den-item-motivo">' + escHtml(motivoShort) + '</div>';
        html += '    <div class="den-item-data">📅 ' + dataFormatada + '</div>';
        if (d.ultima_resposta_equipe) {
            const respostaShort = d.ultima_resposta_equipe.length > 60 ? d.ultima_resposta_equipe.substring(0, 60) + '...' : d.ultima_resposta_equipe;
            html += '    <div class="den-item-resp">🛡️ <b>Resposta da equipe:</b> ' + escHtml(respostaShort) + '</div>';
        }
        html += '  </div>';
        html += '  <div class="den-item-right">';
        html += '    <span class="den-badge ' + statusClass + '">' + statusLabel + '</span>';
        if (d.mensagens_nao_lidas > 0) {
            html += '    <span class="den-unread">💬 ' + d.mensagens_nao_lidas + '</span>';
        } else if (d.total_mensagens > 0) {
            html += '    <span style="font-size:10px;color:#999;">💬 ' + d.total_mensagens + '</span>';
        }
        html += '  </div>';
        html += '</div>';
    });
    html += '</div>';
    container.innerHTML = html;

    // Scroll e animação de destaque
    const destaqueDcid = new URLSearchParams(window.location.search).get('dcid');
    if (destaqueDcid) {
        const el = container.querySelector('.den-item-destaque');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.style.transition = 'background 0.5s ease';
            setTimeout(() => {
                el.style.background = '#fffde7';
                setTimeout(() => { el.style.background = '#fff8e1'; }, 600);
                setTimeout(() => { el.style.background = '#fffde7'; }, 1200);
                setTimeout(() => { el.style.background = ''; el.style.borderLeft = ''; }, 3000);
            }, 300);
        }
    }
}

async function abrirChatDenunciaComm(denId) {
    _chatCommDenunciaId = denId;
    const overlay = document.getElementById('denCommChatOverlay');
    overlay.classList.add('open');
    document.getElementById('denCommChatTitle').textContent = '💬 Chat - Denúncia Comunidade #' + denId;
    document.getElementById('denCommChatMessages').innerHTML = '<div class="den-chat-empty">Carregando mensagens...</div>';
    document.getElementById('denCommChatInfo').innerHTML = 'Carregando...';
    document.getElementById('denCommChatFooter').classList.remove('disabled');
    document.getElementById('denCommChatInput').value = '';

    await carregarChatMensagensComm();

    if (_chatCommPollInterval) clearInterval(_chatCommPollInterval);
    _chatCommPollInterval = setInterval(carregarChatMensagensComm, 5000);
}

async function carregarChatMensagensComm() {
    if (!_chatCommDenunciaId) return;
    try {
        const resp = await fetch('/api/denuncia-comunidade-chat/' + _chatCommDenunciaId);
        const data = await resp.json();
        if (!data.success) return;

        const den = data.denuncia;
        const statusLabel = den.status.charAt(0).toUpperCase() + den.status.slice(1);
        const statusClass = 'den-badge-' + den.status;

        const denData = _denunciasCommData.find(d => d.id === _chatCommDenunciaId);
        let infoHtml = '<b>Comunidade:</b> ' + (denData ? escHtml(denData.comunidade_nome) : '#' + den.comunidade_id) + ' | ';
        infoHtml += '<b>Motivo:</b> ' + escHtml(den.motivo.length > 80 ? den.motivo.substring(0, 80) + '...' : den.motivo);
        infoHtml += '<div class="den-chat-status-row"><b>Status:</b> <span class="den-badge ' + statusClass + '">' + statusLabel + '</span>';
        infoHtml += ' <span style="font-size:10px;color:#999;">Aberta em ' + formatarDataDen(den.criado_em) + '</span></div>';

        const lastAdminMsg = data.mensagens.filter(m => m.is_admin).pop();
        if (lastAdminMsg) {
            infoHtml += '<div class="den-resp-admin">🛡️ <b>Resposta da equipe:</b> ' + escHtml(lastAdminMsg.mensagem) + '</div>';
        }

        document.getElementById('denCommChatInfo').innerHTML = infoHtml;

        const msgs = data.mensagens;
        const container = document.getElementById('denCommChatMessages');

        if (msgs.length === 0) {
            container.innerHTML = '<div class="den-chat-empty">Nenhuma mensagem ainda.<br><span style="font-size:10px;">Envie uma mensagem para a equipe sobre sua denúncia de comunidade.</span></div>';
        } else {
            let html = '';
            msgs.forEach(m => {
                const cls = m.is_admin ? 'from-admin' : 'from-user';
                const senderName = m.is_admin ? '🛡️ Equipe Yorkut' : 'Você';
                html += '<div class="den-chat-msg ' + cls + '">';
                html += '  <div class="den-chat-bubble">';
                html += '    <div class="den-chat-bubble-sender">' + senderName + '</div>';
                html += '    ' + escHtml(m.mensagem);
                html += '    <div class="den-chat-bubble-time">' + formatarDataDen(m.criado_em) + '</div>';
                html += '  </div>';
                html += '</div>';
            });
            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        }

        const footer = document.getElementById('denCommChatFooter');
        if (den.status === 'resolvida' || den.status === 'rejeitada') {
            footer.classList.add('disabled');
            footer.style.display = 'none';
            if (!document.querySelector('#denCommChatOverlay .den-chat-closed-msg')) {
                const msg = document.createElement('div');
                msg.className = 'den-chat-closed-msg';
                msg.textContent = 'Esta denúncia foi ' + (den.status === 'resolvida' ? 'resolvida' : 'rejeitada') + '. Não é possível enviar novas mensagens.';
                footer.parentNode.insertBefore(msg, footer.nextSibling);
            }
        } else {
            footer.classList.remove('disabled');
            footer.style.display = 'flex';
            const closedMsg = document.querySelector('#denCommChatOverlay .den-chat-closed-msg');
            if (closedMsg) closedMsg.remove();
        }

        if (_denunciasCommData.length > 0) {
            const denItem = _denunciasCommData.find(d => d.id === _chatCommDenunciaId);
            if (denItem) { denItem.mensagens_nao_lidas = 0; renderDenunciasCommList(); }
        }
    } catch(err) {
        console.error('Erro carregar chat comunidade:', err);
    }
}

async function enviarMsgDenunciaComm() {
    const input = document.getElementById('denCommChatInput');
    const msg = input.value.trim();
    if (!msg || !_chatCommDenunciaId) return;
    input.disabled = true;
    try {
        const resp = await fetch('/api/denuncia-comunidade-chat/' + _chatCommDenunciaId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensagem: msg })
        });
        const data = await resp.json();
        if (data.success) { input.value = ''; await carregarChatMensagensComm(); }
        else { alert(data.message || 'Erro ao enviar.'); }
    } catch(err) { alert('Erro de conexão.'); }
    input.disabled = false;
    input.focus();
}

function fecharChatComm() {
    _chatCommDenunciaId = null;
    if (_chatCommPollInterval) { clearInterval(_chatCommPollInterval); _chatCommPollInterval = null; }
    document.getElementById('denCommChatOverlay').classList.remove('open');
    if (_denunciasAberto) carregarMinhasDenuncias();
}

async function desistirDenunciaComm() {
    if (!_chatCommDenunciaId) return;
    showConfirm('Tem certeza que deseja desistir desta denúncia de comunidade? Ela será excluída permanentemente.', async function() {
        try {
            const resp = await fetch('/api/denuncia-comunidade-desistir/' + _chatCommDenunciaId, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await resp.json();
            if (data.success) { fecharChatComm(); showToast('Denúncia removida com sucesso.', 'success'); }
            else { showToast(data.message || 'Erro ao desistir.', 'error'); }
        } catch(err) { showToast('Erro de conexão.', 'error'); }
    }, { danger: true });
}

function formatarDataDen(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const pad = n => String(n).padStart(2, '0');
    return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>document.addEventListener('DOMContentLoaded', () => { loadLayout({ activePage: 'configuracoes' }); });</script>
</body>
</html>
