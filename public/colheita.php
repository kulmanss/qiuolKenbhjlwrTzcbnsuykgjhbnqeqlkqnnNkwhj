<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="description" content="Colheita Feliz - Jogo de fazenda do Yorkut. Plante, regue, colha e visite amigos!">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Yorkut - Colheita Feliz</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
/* Colheita: game area ocupa center+right */
body { overflow: hidden; }
.colheita-center {
    flex: 1;
    min-width: 0;
}
.breadcrumb { color: #666; font-size: 11px; margin-bottom: 6px; }
.breadcrumb a { color: #315c99; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }

/* === GAME CONTAINER (fiel ao original) === */
#game-container {
    width: 100%;
    height: calc(100vh - 135px);
    min-height: 400px;
    overflow: hidden;
    position: relative;
    cursor: url('imagens_colheita/cursor.png'), auto;
    background: #6db64d;
    border-radius: 6px;
    border: 1px solid #4a8c33;
    user-select: none;
}
#game-container.tool-active { cursor: none !important; }
#game-container:active:not(.tool-active) { cursor: grabbing; }
#world { transform-origin: 0 0; position: absolute; top: 0; left: 0; }
#farm-bg { display: block; pointer-events: none; }

/* Grid 3D isométrico (idêntico ao original) */
#grid-container {
    position: absolute;
    top: 50%; left: 38.4%;
    width: 1300px; height: 1950px;
    transform: rotateZ(-84deg) rotateX(36deg) rotateY(-26deg) scaleX(1.25) scaleY(0.85) skewX(11deg) skewY(-61deg);
    display: grid; grid-template-columns: repeat(6, 1fr); grid-template-rows: repeat(4, 1fr);
    gap: 0px;
}
.tile { box-sizing: border-box; border: none; background-color: transparent; transition: opacity 0.2s; position: relative; overflow: hidden; }
.tile::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 85%;
    height: 80%;
    background-color: transparent;
    border-radius: 4px;
    z-index: 0;
}
.sprite.sprite-hovered { filter: brightness(1.4) drop-shadow(0 0 10px rgba(255,255,255,0.7)); }
#sprite-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
.sprite-element {
    position: absolute;
    pointer-events: none; z-index: 10;
}
.sprite { width: 600px; transition: filter 0.3s; transform: translate(calc(-80% + -6px), calc(-50% + 1px)); }

.plant-indicator {
    position: absolute;
    z-index: 11; display: none;
    background-repeat: no-repeat; background-position: center center;
    background-size: contain; filter: drop-shadow(0 6px 8px rgba(0,0,0,0.4));
    transition: width 0.3s, height 0.3s, background-image 0.3s, transform 0.3s;
}

.tile-progress-container { width: 140px; height: 24px; background: rgba(0,0,0,0.85); border-radius: 12px; z-index: 12; display: none; margin-top: 0; overflow: hidden; border: 1px solid rgba(255,255,255,0.6); box-shadow: 0 4px 8px rgba(0,0,0,0.6); transform: translate(-300%, 20%); position: absolute; }
.tile-progress-bar { height: 100%; background: #4caf50; width: 0%; position: absolute; top: 0; left: 0; transition: width 0.3s, background-color 0.3s; }
.tile-progress-text { position: absolute; top: 0; left: 0; width: 100%; height: 100%; text-align: center; font-size: 12px; line-height: 24px; color: #fff; font-weight: bold; z-index: 2; text-shadow: 1px 1px 2px #000; }

.harvest-indicator { width: 60px; height: 60px; border-radius: 50%; z-index: 13; display: none; transform: translate(-50%, -50%); border: 4px solid #FFD700; box-shadow: 0 0 20px #FFD700; animation: pulse 1s infinite alternate; background: rgba(0,0,0,0.08); }
@keyframes pulse { from { transform: translate(-50%, -50%) scale(1); } to { transform: translate(-50%, -50%) scale(1.15); } }

/* HUD */
#game-hud { position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.7); border-radius: 40px; padding: 5px 25px 5px 5px; display: flex; align-items: center; gap: 20px; color: white; z-index: 1000; border: 2px solid rgba(255,255,255,0.3); font-size: 12px; }
.hud-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.hud-profile img { width: 45px; height: 45px; border-radius: 50%; border: 2px solid #fff; object-fit: cover; background:#fff;}
.hud-info { display: flex; flex-direction: column; gap: 4px; width: 120px; }
.hud-name { font-weight: bold; font-size: 13px; text-shadow: 1px 1px 2px #000; }
.hud-xp-bar { width: 100%; height: 12px; background: #333; border-radius: 6px; overflow: hidden; border: 1px solid #555; position: relative;}
.hud-xp-fill { height: 100%; background: linear-gradient(to right, #4CAF50, #2E7D32); width: 0%; transition: width 0.3s; }
.hud-xp-text { position: absolute; width:100%; text-align:center; font-size:9px; line-height:12px; top:0; left:0; font-weight:bold; color:#fff; text-shadow: 0 0 2px #000, 0 0 2px #000; }
.hud-stats { display: flex; gap: 15px; font-weight: bold; font-size: 14px; text-shadow: 1px 1px 2px #000; }
.stat-icon { font-size: 16px; margin-right: 3px; }
.farm-title { position: absolute; bottom: 20px; left: 20px; background: rgba(0,0,0,0.65); color: #ddd; padding: 8px 14px; border-radius: 20px; font-size: 12px; z-index: 1000; cursor: pointer; border: 2px solid rgba(255,255,255,0.4); transition: transform 0.2s, background 0.2s; font-weight: bold; text-shadow: 1px 1px 2px #000; }
.farm-title:hover { background: rgba(0,0,0,0.85); border-color: #fff; transform: scale(1.05); }

/* Top panel buttons */
.top-panel { position: absolute; top: 15px; right: 15px; display: flex; gap: 10px; z-index: 1000; }
.top-icon { background: rgba(0,0,0,0.65); border: 2px solid rgba(255,255,255,0.4); color: #fff; padding: 8px 16px; border-radius: 20px; font-weight: bold; cursor: pointer; font-size: 12px; display: flex; align-items: center; gap: 6px; transition: transform 0.2s, background 0.2s; text-shadow: 1px 1px 2px #000; }
.top-icon:hover { background: rgba(0,0,0,0.85); border-color: #fff; transform: scale(1.05); }

/* Floating modals (amigos + avisos) */
.floating-modal { display: none; position: absolute; top: 60px; right: 15px; width: 340px; max-height: 450px; overflow-y: auto; background: rgba(0,0,0,0.85); border: 2px solid #555; border-radius: 10px; z-index: 2000; padding: 15px; color: #fff; }
.modal-close-btn { background: transparent; border: none; color: #fff; cursor: pointer; font-weight: bold; font-size: 14px; }

.log-item { display: flex; gap: 10px; margin-bottom: 10px; border-bottom: 1px solid #444; padding-bottom: 10px; font-size: 11px; }
.log-pic { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
.log-date { color: #999; font-size: 10px; margin-top: 3px; }

.friend-farm-item { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.05); padding: 8px; border-radius: 6px; margin-bottom: 8px; transition: background 0.2s; }
.friend-farm-item:hover { background: rgba(255,255,255,0.2); }
.friend-farm-pic { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc; cursor: pointer; }
.friend-info { flex: 1; cursor: pointer; }
.friend-stats { font-size: 10px; color: #ddd; margin-top: 3px; display: flex; gap: 8px; }
.btn-ver-perfil { background: #3b5998; color: #fff; border: 1px solid #7a9bce; border-radius: 4px; font-size: 9px; padding: 4px 6px; cursor: pointer; text-decoration: none; font-weight: bold; }

/* Toolbar */
#toolbar-wrapper { position: absolute; bottom: 0px; left: 50%; transform: translateX(-50%); z-index: 1000; display: flex; flex-direction: column; align-items: center; gap: 0px; transition: transform 0.3s; }
#btn-minimize { background: rgba(0,0,0,0.5); color: transparent; border: none; border-radius: 0 0 8px 8px; width: 50px; height: 14px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0; padding: 0; margin-top: -1px; position: relative; }
#btn-minimize::after { content: ''; display: block; width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-top: 5px solid rgba(255,255,255,0.6); transition: transform 0.3s; }
#btn-minimize.collapsed::after { transform: rotate(180deg); }
#ui-tools { display: flex; gap: 15px; background: rgba(0,0,0,0.6); padding: 15px 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 4px 15px rgba(0,0,0,0.3); }

.tool { width: 65px; height: 65px; cursor: pointer; position: relative; background-size: 70%; background-position: center; background-repeat: no-repeat; background-color: transparent; border-radius: 12px; transition: transform 0.1s, filter 0.2s; display:flex; align-items:center; justify-content:center; font-size: 32px; border: none; filter: drop-shadow(0 0 0 transparent); }
.tool:hover { filter: drop-shadow(0 0 6px rgba(255,255,255,0.7)); }
.tool.active { filter: drop-shadow(0 0 8px rgba(255,255,255,0.9)) drop-shadow(0 0 3px rgba(255,255,255,0.6)); }
.tool.active:hover { filter: drop-shadow(0 0 12px rgba(255,255,255,1)) drop-shadow(0 0 5px rgba(255,255,255,0.8)); }
.tool:active { transform: scale(0.9); }

#tool-seta { background-image: url('imagens_colheita/seta.png'); font-size:0; }
#tool-enxada { background-image: url('imagens_colheita/enxada.png'); }
#tool-bolsa { background-image: url('imagens_colheita/bolsa.png'); }
#tool-regador { background-image: url('imagens_colheita/regador.png'); }
#tool-catalizador { background-image: url('imagens_colheita/catalizador.png'); font-size:0; }
#tool-inseticida { background-image: url('imagens_colheita/inseticida.png'); font-size:0; }
#tool-mao { background-image: url('imagens_colheita/mao.png'); font-size:0; }

/* Tool cursor */
.active-tool-cursor { position: fixed; pointer-events: none; z-index: 9999; transform: translate(-50%, -50%); box-shadow: none; filter: drop-shadow(2px 6px 8px rgba(0,0,0,0.6)); }
@keyframes workMotion { 0% { transform: translate(-50%, -50%) rotate(0deg) translateY(0); } 50% { transform: translate(-50%, -50%) rotate(-30deg) translateY(-15px); } 100% { transform: translate(-50%, -50%) rotate(0deg) translateY(0); } }
.active-tool-cursor.working { animation: workMotion 0.4s infinite; }
.active-tool-cursor.pouring { transform-origin: top left; animation: pourMotion 0.7s ease-in-out forwards; }
@keyframes pourMotion { 0% { transform: translate(-50%, -50%) rotate(0deg); } 40% { transform: translate(-50%, -50%) rotate(-75deg); } 70% { transform: translate(-50%, -50%) rotate(-75deg); } 100% { transform: translate(-50%, -50%) rotate(0deg); } }

/* Tool Effect Animation */
.tool-effect {
    position: fixed;
    pointer-events: none;
    z-index: 9998;
    width: 60px;
    height: 60px;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    transform: translate(-50%, -20%);
    opacity: 0.9;
}

/* Puff Transition Effect */
.puff-overlay {
    position: absolute;
    pointer-events: none;
    z-index: 15;
    width: 600px;
    height: 600px;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    transform: translate(calc(-80% + -6px), calc(-50% + 1px));
}
@keyframes puffSpin {
    0% { transform: translate(calc(-80% + -6px), calc(-50% + 1px)) rotate(0deg) scale(0.5); opacity: 0; }
    15% { transform: translate(calc(-80% + -6px), calc(-50% + 1px)) rotate(54deg) scale(1); opacity: 1; }
    50% { transform: translate(calc(-80% + -6px), calc(-50% + 1px)) rotate(180deg) scale(1); opacity: 1; }
    100% { transform: translate(calc(-80% + -6px), calc(-50% + 1px)) rotate(360deg) scale(0.3); opacity: 0; }
}

/* Game Toast */
.game-toast { position: absolute; top: 20px; left: 50%; transform: translateX(-50%) translateY(-20px); opacity: 0; color: #fff; padding: 10px 20px; border-radius: 30px; font-size: 13px; font-weight: bold; z-index: 99999; transition: all 0.3s ease; text-align: center; border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.3); pointer-events: none; }
.game-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.game-toast.error { background: rgba(204, 0, 0, 0.9); border-color: #ffcccc; }
.game-toast.success { background: rgba(42, 107, 42, 0.9); border-color: #8bc59e; }

/* Tooltip */
#tooltip { position: fixed; background: rgba(0, 0, 0, 0.85); color: #fff; padding: 10px 15px; border-radius: 8px; font-size: 13px; font-weight: bold; pointer-events: none; display: none; z-index: 9999; white-space: nowrap; transform: translate(-50%, -150%); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 4px 10px rgba(0,0,0,0.5); }
.progress-container { width: 100%; height: 14px; background: rgba(255,255,255,0.2); border-radius: 7px; margin-top: 8px; overflow: hidden; display: none; }
.progress-bar { height: 100%; width: 0%; transition: width 0.1s; border-radius: 7px; }
#tooltip-season { font-size: 11px; color: #ccc; text-align: center; margin-top: 6px; display: none; }
#tooltip-season .season-bar-container { width: 100%; height: 10px; background: rgba(255,255,255,0.2); border-radius: 5px; margin-top: 3px; overflow: hidden; }
#tooltip-season .season-bar { height: 100%; width: 0%; background: linear-gradient(to right, #2196F3, #64B5F6); border-radius: 5px; transition: width 0.2s; }

/* Level-up overlay */
#levelup-overlay { display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:9998; justify-content:center; align-items:center; }
#levelup-overlay.show { display:flex; }
.levelup-wrapper { position:relative; display:inline-block; max-width:90%; max-height:85%; animation: levelupPop 0.5s ease-out, levelupPulse 1.5s ease-in-out 0.5s infinite; }
.levelup-wrapper .levelup-img { width:100%; height:100%; max-height:80vh; object-fit:contain; display:block; }
.levelup-wrapper .levelup-close { position:absolute; top:93px; right:17px; cursor:pointer; width:42px; height:42px; z-index:10; }
.levelup-wrapper .levelup-confirm { position:absolute; bottom:15%; left:48%; transform:translateX(-50%); cursor:pointer; height:40px; z-index:10; }
@keyframes levelupPop { 0% { transform:scale(0.3); opacity:0; } 60% { transform:scale(1.1); opacity:1; } 100% { transform:scale(1); opacity:1; } }
@keyframes levelupPulse { 0%,100% { transform:scale(1); } 50% { transform:scale(1.03); } }

/* Harvest float effect */
.harvest-float { position:absolute; z-index:500; pointer-events:none; display:flex; flex-direction:row; align-items:center; gap:10px; transform:translateX(-50%); animation: harvestFloatUp 0.8s ease-out forwards; }
.harvest-float img { width:400px; height:400px; object-fit:contain; filter: drop-shadow(0 3px 10px rgba(0,0,0,0.6)); }
.harvest-float span { font-size:240px; font-weight:900; color:#bb0808; text-shadow: 3px 3px 6px rgba(0,0,0,0.6), 0 0 14px rgba(187,8,8,0.4); white-space:nowrap; }
@keyframes harvestFloatUp { 0% { opacity:1; transform:translateX(-50%) translateY(0); } 60% { opacity:1; transform:translateX(-50%) translateY(-500px); } 100% { opacity:0; transform:translateX(-50%) translateY(-700px); } }

/* Game modal (loja/armazem/sementes) */
#game-modal { display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index: 3000; align-items:center; justify-content:center; }
.modal-box { background:#fff; width:90%; max-width:650px; height:80%; max-height:600px; border-radius:8px; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.6); }
.modal-header { background:#6d84b4; color:#fff; padding:15px; display:flex; justify-content:space-between; font-weight:bold; font-size:16px; align-items:center; }
.modal-header button { background:#cc0000; border:none; color:#fff; font-size:14px; cursor:pointer; padding:5px 10px; border-radius:4px; font-weight:bold; }
#modal-iframe { flex:1; width:100%; border:none; background:#f4f7fc; }

/* Remover right-col nesta página */
.container .right-col { display: none !important; }

/* Painel flutuante de pertences (bolsa) */
#inventory-panel {
    display: none;
    position: absolute;
    bottom: 115px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2500;
    background: rgba(255,255,255,0.97);
    border: 2px solid rgba(180,170,150,0.5);
    border-radius: 12px;
    padding: 0;
    width: 510px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15), inset 0 1px 0 rgba(255,255,255,0.8);
    animation: invPanelIn 0.15s ease-out;
}
@keyframes invPanelIn { from { opacity:0; transform:translateX(-50%) translateY(10px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
#inventory-panel .inv-header {
    display: flex; justify-content: center; align-items: center; position: relative;
    padding: 10px 16px; border-bottom: 1px solid rgba(0,0,0,0.1);
}
#inventory-panel .inv-header span { color: #333; font-weight: bold; font-size: 14px; font-family: Tahoma, Arial, sans-serif; }
#inventory-panel .inv-close { background: transparent; border: none; color: #666; font-size: 26px; cursor: pointer; padding: 2px 6px; line-height: 1; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); }
#inventory-panel .inv-close:hover { color: #cc0000; }
#inventory-panel .inv-items {
    display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; padding: 12px 10px 12px 14px; max-height: 140px; overflow-y: scroll !important;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: #c0bdb8 transparent;
}
#inventory-panel .inv-items::-webkit-scrollbar { width: 6px !important; }
#inventory-panel .inv-items::-webkit-scrollbar-track { background: transparent !important; margin: 6px 0; }
#inventory-panel .inv-items::-webkit-scrollbar-thumb {
    background: #c0bdb8 !important;
    border-radius: 3px !important;
}
#inventory-panel .inv-items::-webkit-scrollbar-thumb:hover { background: #a8a5a0 !important; }
#inventory-panel .inv-items::-webkit-scrollbar-button { display: none !important; }
#inventory-panel .inv-item {
    position: relative; width: 58px; height: 58px; background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.1);
    border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.15s;
}
#inventory-panel .inv-item:hover { background: rgba(0,0,0,0.1); border-color: rgba(0,0,0,0.25); transform: scale(1.08); }
#inventory-panel .inv-item img { width: 40px; height: 40px; object-fit: contain; filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2)); pointer-events: none; }
#inventory-panel .inv-item .inv-qty {
    position: absolute; bottom: 2px; right: 4px; color: #333; font-size: 11px; font-weight: bold;
    text-shadow: 0 0 3px rgba(255,255,255,0.9); font-family: Tahoma, Arial, sans-serif;
}
#inventory-panel .inv-empty { color: #888; font-size: 13px; padding: 20px; text-align: center; width: 100%; font-family: Tahoma, Arial, sans-serif; }

/* Tooltip do item no painel */
#inv-tooltip {
    display: none; position: fixed; z-index: 9999;
    background: rgba(255,255,255,0.97); color: #333; padding: 10px 14px;
    border-radius: 8px; border: 1px solid rgba(0,0,0,0.12);
    font-family: Tahoma, Arial, sans-serif; font-size: 12px;
    pointer-events: none; max-width: 220px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}
#inv-tooltip .inv-tt-name { font-weight: bold; font-size: 13px; color: #3b5998; margin-bottom: 4px; }
#inv-tooltip .inv-tt-desc { color: #666; font-size: 11px; margin-bottom: 4px; }
#inv-tooltip .inv-tt-info { color: #555; font-size: 11px; line-height: 1.5; }

/* Tile unlock popup */
#tile-unlock-popup {
    display: none;
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 4000;
    justify-content: center;
    align-items: center;
}
.tile-unlock-content {
    background: url('imagens_colheita/liberarbg.png') center/cover no-repeat;
    border: none;
    border-radius: 12px;
    padding: 24px;
    color: white;
    text-align: center;
    width: 446px;
    height: 338px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    position: relative;
}
.tile-unlock-title {
    font-size: 18px;
    font-weight: bold;
    position: absolute;
    top: 8px;
    left: 0;
    right: 0;
}
.tile-unlock-reqs {
    margin-bottom: 12px;
    font-size: 12px;
    text-align: left;
    width: 55%;
    margin-left: auto;
    margin-right: 10%;
}
.tile-unlock-reqs .req-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    margin-bottom: 4px;
    background: rgba(255,255,255,0.85);
    border-radius: 4px;
    border-left: 3px solid;
}
.tile-unlock-reqs .req-item.ok { border-left-color: #4caf50; }
.tile-unlock-reqs .req-item.fail { border-left-color: #f44336; }
.tile-unlock-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    margin: 4px;
    padding: 0;
    transition: transform 0.15s;
    position: relative;
    display: inline-block;
}
.tile-unlock-btn:hover { transform: scale(1.1); }
.tile-unlock-btn img { height: 40px; display: block; }
.tile-unlock-btn span {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    color: #fff;
    font-size: 13px;
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
    pointer-events: none;
    white-space: nowrap;
}
.tile-unlock-close {
    position: absolute;
    top: 4px;
    right: 4px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    transition: transform 0.15s;
}
.tile-unlock-close:hover { transform: scale(1.15); }
.tile-unlock-close img { height: 30px; }

/* Animação de explosão ao desbloquear tile */
.tile-unlock-explosion {
    position: absolute;
    width: 300px;
    height: 300px;
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 100;
    background-image: url('imagens_colheita/desbloquear.png');
    background-size: contain;
    background-position: center;
    background-repeat: no-repeat;
    animation: explosionAppear 0.6s ease-out forwards;
}

@keyframes explosionAppear {
    0% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.3) rotate(-20deg);
    }
    50% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.2) rotate(5deg);
    }
    100% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(1.5) rotate(10deg);
    }
}

/* Animação do tile sendo destruído */
.tile-being-unlocked .sprite {
    animation: tileDestroy 0.8s ease-out forwards;
}

@keyframes tileDestroy {
    0% {
        opacity: 1;
        transform: translate(calc(-80% + -6px), calc(-50% + 1px)) scale(1) rotate(0deg);
    }
    30% {
        opacity: 1;
        transform: translate(calc(-80% + -6px), calc(-50% + 1px)) scale(1.1) rotate(0deg);
    }
    60% {
        opacity: 0.5;
        transform: translate(calc(-80% + -6px), calc(-50% + 1px)) scale(0.8) rotate(5deg);
    }
    100% {
        opacity: 0;
        transform: translate(calc(-80% + -6px), calc(-50% + 1px)) scale(0.3) rotate(15deg);
    }
}
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div class="left-col" id="app-left-col"></div>
    <div class="colheita-center">
        <div class="breadcrumb" id="breadcrumb">Início &gt; Colheita Feliz</div>

        <div id="game-container">
            <!-- HUD -->
            <div id="game-hud">
                <div class="hud-profile" onclick="window.location.href='profile.php'">
                    <img id="hud-pic" src="img/perfilsemfoto.jpg" title="Voltar ao meu Perfil">
                    <div class="hud-info">
                        <div class="hud-name">Nível <span id="hud-lvl">1</span></div>
                        <div class="hud-xp-bar"><div class="hud-xp-fill" id="hud-xp-fill"></div><div class="hud-xp-text" id="hud-xp-text">0/5000</div></div>
                    </div>
                </div>
                <div class="hud-stats">
                    <div class="stat" title="XP"><span class="stat-icon">⭐</span> <span id="hud-xp">0</span></div>
                    <div class="stat" title="Ouro (Ganha vendendo no armazém)"><span class="stat-icon">🪙</span> <span id="hud-gold">0</span></div>
                    <div class="stat" title="KutCoins" style="cursor:pointer; transition:0.2s;" onclick="window.location.href='kutcoin.php'" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <span class="stat-icon" style="color:#4CAF50;">K$</span> <span id="hud-kutcoin">0</span>
                    </div>
                </div>
            </div>

            <!-- Top panel buttons -->
            <div class="top-panel" id="top-panel-container">
                <!-- preenchido pelo JS baseado em isOwner -->
            </div>

            <div class="farm-title" id="farm-title" onclick="toggleFullscreen()">⛶ Jogar em tela cheia</div>

            <!-- Logs modal -->
            <div id="logs-modal" class="floating-modal">
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #555; padding-bottom:10px; margin-bottom:10px;">
                    <h3 style="margin:0; font-size:14px;">Visitantes Recentes</h3>
                    <button class="modal-close-btn" onclick="toggleFloatingModal('logs-modal')">X</button>
                </div>
                <div id="logs-list"></div>
            </div>

            <!-- Friends modal -->
            <div id="friends-modal" class="floating-modal">
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #555; padding-bottom:10px; margin-bottom:10px;">
                    <h3 style="margin:0; font-size:14px;">Meus Vizinhos</h3>
                    <button class="modal-close-btn" onclick="toggleFloatingModal('friends-modal')">X</button>
                </div>
                <div id="friends-list" style="display:flex; flex-direction:column;"></div>
            </div>

            <!-- Game world -->
            <div id="world">
                <img id="farm-bg" src="imagens_colheita/background.jpg" alt="Fazenda">
                <div id="grid-container"></div>
                <div id="sprite-layer"></div>
            </div>

            <!-- Toolbar -->
            <div id="toolbar-wrapper">
                <div id="ui-tools"></div>
                <button id="btn-minimize" onclick="toggleToolbar()"></button>
            </div>
            <!-- Painel de pertences (bolsa) -->
            <div id="inventory-panel">
                <div class="inv-header">
                    <span>Seus pertences</span>
                    <button class="inv-close" onclick="closeInventoryPanel()">&times;</button>
                </div>
                <div class="inv-items" id="inv-items"></div>
            </div>
            <div id="inv-tooltip"></div>
            <!-- Game modal (loja/armazem/sementes) -->
            <div id="game-modal">
                <div class="modal-box">
                    <div class="modal-header">
                        <span id="modal-title"></span>
                        <button onclick="closeModal()">FECHAR</button>
                    </div>
                    <iframe id="modal-iframe" src=""></iframe>
                </div>
            </div>
            <!-- Tooltip -->
            <div id="tooltip">
                <div id="tooltip-text">Aguardando...</div>
                <div class="progress-container" id="tooltip-progress-container"><div id="tooltip-progress" class="progress-bar"></div></div>
                <div id="tooltip-season"><span id="tooltip-season-text"></span><div class="season-bar-container"><div class="season-bar" id="tooltip-season-bar"></div></div></div>
            </div>
            <!-- Level-up overlay -->
            <div id="levelup-overlay">
                <div class="levelup-wrapper">
                    <img class="levelup-close" src="imagens_colheita/levelup/fechar.png" onclick="closeLevelUp()" alt="Fechar">
                    <img class="levelup-img" id="levelup-img" src="" alt="Level Up!">
                    <img class="levelup-confirm" src="imagens_colheita/levelup/confirmar.png" onclick="closeLevelUp()" alt="Confirmar">
                </div>
            </div>
        </div><!-- /game-container -->
    </div>
</div>
<div id="app-footer"></div>

<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
// ========== VARIÁVEIS GLOBAIS ==========
let isOwner = false;
let profileId = '';
let loggedUserId = '';
let rawFarmData = null;
let rawInventory = {};
let lastUpdatedTs = 0;
let serverNowTs = 0;
let myLevel = 1;
let myXP = 0;
let myGold = 0;
let myKutCoin = 0;
let myDailySteals = 0;
let gridSize = 24;
let tileData = {};
let inventory = {};
let SEEDS_CONFIG = {};
let FERTILIZERS_CONFIG = {};
let unlockedTiles = [0];
let tileReqs = [];
const maxPlantSize = 360;

// EXP_TABLE e MAX_LEVEL carregados do servidor (banco de dados)
let EXP_TABLE = {};
let MAX_LEVEL = 84;

function getXPNeeded(level) {
                if (level >= MAX_LEVEL) return Infinity;
                return EXP_TABLE[level + 1] || 999999999;
            }

function addXP(amount) {
    myXP += amount;
    let leveled = false;
    while (myLevel < MAX_LEVEL) {
        let needed = getXPNeeded(myLevel);
        if (myXP >= needed) {
            myXP -= needed;
            myLevel++;
            leveled = true;
        } else break;
    }
    if (leveled) showLevelUp(myLevel);
    updateHUD();
}

function showLevelUp(level) {
    const overlay = document.getElementById('levelup-overlay');
    const img = document.getElementById('levelup-img');
    img.src = `imagens_colheita/levelup/lv${level}.png`;
    overlay.classList.add('show');
}
function closeLevelUp() {
    document.getElementById('levelup-overlay').classList.remove('show');
}

window.getInventory = function() { return inventory; };
window.getMyGold = function() { return myGold; };
window.getMyKutCoin = function() { return myKutCoin; };

// Retorna o ID do próximo tile que pode ser desbloqueado, ou -1 se todos estão liberados
function getNextUnlockableTile() {
    for (let i = 0; i < gridSize; i++) {
        if (!unlockedTiles.includes(i)) return i;
    }
    return -1;
}

// ========== UTILITY ==========
function showToast(msg, isError = false) {
    const toast = document.createElement('div');
    toast.className = 'game-toast ' + (isError ? 'error' : 'success');
    toast.innerText = msg;
    document.getElementById('game-container').appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3000);
}

const PUFF_FRAMES = ['imagens_colheita/puff2.png','imagens_colheita/puff3.png','imagens_colheita/puff4.png','imagens_colheita/puff5.png'];

function playPuffOnTile(tileId, callback) {
    const sprite = document.getElementById('sprite-' + tileId);
    if (!sprite) { if (callback) callback(); return; }
    const spriteLayer = document.getElementById('sprite-layer');
    
    const puff = document.createElement('div');
    puff.className = 'puff-overlay';
    puff.style.left = sprite.style.left;
    puff.style.top = sprite.style.top;
    puff.style.backgroundImage = `url('${PUFF_FRAMES[0]}')`;
    puff.style.animation = 'puffSpin 0.5s ease-in-out forwards';
    spriteLayer.appendChild(puff);
    
    // puff1 gira (handled by CSS animation), puff2-5 trocam rápido
    let frameIdx = 0;
    const frameInterval = setInterval(() => {
        frameIdx++;
        if (frameIdx >= PUFF_FRAMES.length) frameIdx = 1; // loop puff2-5
        puff.style.backgroundImage = `url('${PUFF_FRAMES[frameIdx]}')`;
    }, 120); // troca rápida de frames
    
    // Após 0.5 segundo: executa callback (troca de grama) e remove puff
    setTimeout(() => {
        clearInterval(frameInterval);
        if (callback) callback();
        setTimeout(() => puff.remove(), 100);
    }, 500);
}

function playWaterEffect() {
    if (!toolCursor) return;
    if (!toolEffectEl) {
        toolEffectEl = document.createElement('div');
        toolEffectEl.className = 'tool-effect';
        document.getElementById('game-container').appendChild(toolEffectEl);
    }
    toolEffectEl.style.display = 'block';
    toolEffectEl.style.width = '28px';
    toolEffectEl.style.height = '28px';
    toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
    toolEffectEl.style.top = (parseFloat(toolCursor.style.top) - 5) + 'px';
    toolEffectEl.style.backgroundImage = `url('${REGADOR_FRAMES[0]}')`;
    let frameIdx = 0;
    const frameInterval = setInterval(() => {
        frameIdx++;
        if (frameIdx >= REGADOR_FRAMES.length) frameIdx = 0;
        if (toolCursor) {
            toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
            toolEffectEl.style.top = (parseFloat(toolCursor.style.top) - 5) + 'px';
        }
        toolEffectEl.style.backgroundImage = `url('${REGADOR_FRAMES[frameIdx]}')`;
    }, 100);
    setTimeout(() => {
        clearInterval(frameInterval);
        hideToolEffect();
    }, 700);
}

function playDigEffect() {
    if (!toolCursor) return;
    if (!toolEffectEl) {
        toolEffectEl = document.createElement('div');
        toolEffectEl.className = 'tool-effect';
        document.getElementById('game-container').appendChild(toolEffectEl);
    }
    toolEffectEl.style.display = 'block';
    toolEffectEl.style.width = '60px';
    toolEffectEl.style.height = '60px';
    toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
    toolEffectEl.style.top = toolCursor.style.top;
    toolEffectEl.style.backgroundImage = `url('${ENXADA_FRAMES[0]}')`;
    let frameIdx = 0;
    const frameInterval = setInterval(() => {
        frameIdx++;
        if (frameIdx >= ENXADA_FRAMES.length) frameIdx = 0;
        if (toolCursor) {
            toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
            toolEffectEl.style.top = toolCursor.style.top;
        }
        toolEffectEl.style.backgroundImage = `url('${ENXADA_FRAMES[frameIdx]}')`;
    }, 150);
    setTimeout(() => {
        clearInterval(frameInterval);
        hideToolEffect();
    }, 700);
}

function playFertEffect() {
    if (!toolCursor) return;
    if (!toolEffectEl) {
        toolEffectEl = document.createElement('div');
        toolEffectEl.className = 'tool-effect';
        document.getElementById('game-container').appendChild(toolEffectEl);
    }
    toolEffectEl.style.display = 'block';
    toolEffectEl.style.width = '28px';
    toolEffectEl.style.height = '28px';
    toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
    toolEffectEl.style.top = (parseFloat(toolCursor.style.top) - 5) + 'px';
    toolEffectEl.style.backgroundImage = `url('${FERT_FRAMES[0]}')`;
    let frameIdx = 0;
    const totalFrames = FERT_FRAMES.length * 2; // exatamente 2 ciclos
    let frameCount = 0;
    const frameInterval = setInterval(() => {
        frameCount++;
        if (frameCount >= totalFrames) {
            clearInterval(frameInterval);
            hideToolEffect();
            return;
        }
        frameIdx = frameCount % FERT_FRAMES.length;
        if (toolCursor) {
            toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
            toolEffectEl.style.top = (parseFloat(toolCursor.style.top) - 5) + 'px';
        }
        toolEffectEl.style.backgroundImage = `url('${FERT_FRAMES[frameIdx]}')`;
    }, 100);
}

function showHarvestFloat(tileId, seedId, amount) {
    const sprite = document.getElementById('sprite-' + tileId);
    if (!sprite) return;
    const spriteLayer = document.getElementById('sprite-layer');
    const seed = SEEDS_CONFIG[String(seedId)];
    if (!seed) return;
    const cx = parseFloat(sprite.style.left);
    const cy = parseFloat(sprite.style.top);
    const el = document.createElement('div');
    el.className = 'harvest-float';
    el.style.left = (cx - 200) + 'px';
    el.style.top = (cy - 700) + 'px';
    el.innerHTML = `<img src="${seed.f4_img}"><span>x${amount}</span>`;
    spriteLayer.appendChild(el);
    setTimeout(() => el.remove(), 900);
}

function toggleFloatingModal(id) {
    let el = document.getElementById(id);
    if(el.style.display === 'block') el.style.display = 'none';
    else { document.getElementById('friends-modal').style.display = 'none'; document.getElementById('logs-modal').style.display = 'none'; el.style.display = 'block'; }
}

window.buyItem = async function(item, price, currency) {
    try {
        const resp = await fetch('/api/colheita/buy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: item, quantity: 1 })
        });
        const data = await resp.json();
        if (data.success) {
            myGold = data.ouro;
            inventory = data.inventory;
            updateHUD();
            return true;
        } else {
            showToast(data.message || 'Erro ao comprar.', true);
            return false;
        }
    } catch(e) { showToast('Erro de conexão.', true); return false; }
};

window.sellItem = async function(item, amount, priceEach) {
    try {
        const resp = await fetch('/api/colheita/sell', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: item, quantity: amount })
        });
        const data = await resp.json();
        if (data.success) {
            myGold = data.ouro;
            inventory = data.inventory;
            updateHUD();
            return true;
        } else {
            showToast(data.message || 'Erro ao vender.', true);
            return false;
        }
    } catch(e) { showToast('Erro de conexão.', true); return false; }
};

// ===== SEMENTES (da bolsa) =====
window.selectSeed = function(seedKey) {
    cancelActiveTool();
    let seedId = seedKey.replace('seed_', '');
    let seed = SEEDS_CONFIG[seedId];
    let img = seed ? seed.f1_img : 'imagens_colheita/fase1_semente.png';
    activeTool = seedKey; // ex: 'seed_1001'
    toolCursor = document.createElement('div'); toolCursor.className = 'active-tool-cursor';
    toolCursor.style.cssText = `width:45px;height:45px;background-image:url('${img}');background-repeat:no-repeat;background-position:center;background-size:contain;`;
    document.getElementById('game-container').appendChild(toolCursor);
    closeModal();
    showToast('Clique em uma terra molhada para plantar a semente.');
};

// ===== FERTILIZANTES =====
let activeFertKey = null; // ex: 'fert_1'

window.selectFertilizer = function(fertKey) {
    cancelActiveTool();
    activeFertKey = fertKey;
    let fertId = fertKey.replace('fert_', '');
    let fert = FERTILIZERS_CONFIG[fertId];
    let img = fert ? 'imagens_colheita/fertilizantes/' + fert.icone : '';
    activeTool = fertKey; // marca como ferramenta ativa
    toolCursor = document.createElement('div'); toolCursor.className = 'active-tool-cursor';
    toolCursor.style.cssText = `width:45px;height:45px;background-image:url('${img}');background-repeat:no-repeat;background-position:center;background-size:contain;`;
    document.getElementById('game-container').appendChild(toolCursor);
    closeModal();
    showToast('Clique em uma planta crescendo para usar o fertilizante.');
};

function formatTimeDetailed(ms) {
    if (ms <= 0) return '0s';
    let totalSec = Math.ceil(ms / 1000);
    let h = Math.floor(totalSec / 3600);
    let m = Math.floor((totalSec % 3600) / 60);
    let s = totalSec % 60;
    let parts = [];
    if (h > 0) parts.push(h + 'h');
    if (m > 0) parts.push(m + 'min');
    if (s > 0 && h === 0) parts.push(s + 's');
    return parts.join(' ') || '0s';
}

function applyFertilizerToTile(tileId, fertKey) {
    let fertId = fertKey.replace('fert_', '');
    let fert = FERTILIZERS_CONFIG[fertId];
    if (!fert) return;
    if (!inventory[fertKey] || inventory[fertKey] <= 0) {
        showToast('Sem estoque deste fertilizante!', true);
        cancelActiveTool();
        return;
    }
    let data = tileData[tileId];
    if (!data || data.state !== 10 || !data.planted) {
        showToast('Fertilizante só funciona em plantas crescendo!', true);
        return;
    }
    let seed = SEEDS_CONFIG[data.planted];
    if (!seed) return;

    // Efeito visual de despejar (antes da resposta do servidor)
    if (toolCursor) {
        instantAnimating = true;
        toolCursor.classList.remove('pouring');
        void toolCursor.offsetWidth; // force reflow
        toolCursor.classList.add('pouring');
        setTimeout(() => { if (toolCursor) toolCursor.classList.remove('pouring'); instantAnimating = false; }, 750);
    }
    playFertEffect();

    // Chamar servidor para aplicar fertilizante (anti-cheat)
    fetch('/api/colheita/use-fertilizer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tile_id: tileId, fert_key: fertKey })
    }).then(r => r.json()).then(res => {
        if (res.success) {
            // Atualizar dados do servidor
            inventory = res.inventory;
            if (res.farm_data) {
                // Atualizar apenas o tile afetado e seus timestamps
                for (let tid in res.farm_data) {
                    if (tileData[tid]) {
                        tileData[tid].fert_bonus = res.farm_data[tid].fert_bonus || 0;
                        tileData[tid].fertPhase = res.farm_data[tid].fertPhase;
                        tileData[tid].planted_at = res.farm_data[tid].planted_at;
                        tileData[tid].harvestable_at = res.farm_data[tid].harvestable_at;
                        tileData[tid].state = res.farm_data[tid].state;
                        tileData[tid].timer = res.farm_data[tid].timer || 0;
                    }
                }
            }
            if (!inventory[fertKey] || inventory[fertKey] <= 0) {
                showToast(`${fert.nome} aplicado em ${seed.nome}! (acabou o estoque)`);
                cancelActiveTool();
            } else {
                showToast(`${fert.nome} aplicado em ${seed.nome}! (restam ${inventory[fertKey]})`);
            }
            forceVisualUpdate();
            updateHUD();
        } else {
            showToast(res.message || 'Erro ao usar fertilizante.', true);
        }
    }).catch(() => showToast('Erro de conexão.', true));
}

window.closeFertPopup = function() {};

function openModal(title, url) {
    if(!isOwner) return;
    cancelActiveTool();
    document.getElementById('modal-title').innerText = title;
    document.getElementById('modal-iframe').src = url + '?t=' + Date.now();
    document.getElementById('game-modal').style.display = 'flex';
}
function closeModal() { document.getElementById('game-modal').style.display = 'none'; document.getElementById('modal-iframe').src = ''; }

// Normalizar estados desconhecidos (ex: 1, 5) para state 3
function normalizeTileStates() {
    const validStates = [2, 3, 4, 10, 11, 12];
    for (let id in tileData) {
        let d = tileData[id];
        if (!validStates.includes(d.state)) {
            d.state = 3;
            d.timer = 0;
            d.planted = null;
            d.harvested = 0;
            d.stolenBy = [];
            // Limpar timestamps órfãos
            delete d.planted_at;
            delete d.harvestable_at;
            delete d.watered_at;
            delete d.fert_bonus;
            delete d.fertPhase;
            delete d.currentSeason;
        }
        // Limpar dados de planta em estados sem planta (3, 4)
        if ((d.state === 3 || d.state === 4) && d.planted) {
            d.planted = null;
            d.harvested = 0;
            d.stolenBy = [];
            // Limpar timestamps órfãos
            delete d.planted_at;
            delete d.harvestable_at;
            delete d.fert_bonus;
            delete d.fertPhase;
            delete d.currentSeason;
        }
    }
}

// ========== TILE / VISUALS ==========
function getTileImage(state, tileId) {
    if (tileId !== undefined && !unlockedTiles.includes(parseInt(tileId))) {
        return 'imagens_colheita/Grama_1.png'; // tile trancado
    }
    
    // Nova lógica:
    // state 3 = Grama normal (Grama_3.png) - não pode plantar
    // state 4 = Grama seca (Grama_4.png) - não pode plantar, precisa regar
    // state 2 = Grama molhada (Grama_2.png) - pode plantar
    // state 10 = Plantado/crescendo
    // state 11 = Pronto para colher
    // state 12 = Planta morta
    
    switch(state) {
        case 1: return 'imagens_colheita/Grama_3.png'; // liberado inicial
        case 2: return 'imagens_colheita/Grama_2.png'; // molhada (pode plantar)
        case 3: return 'imagens_colheita/Grama_3.png'; // normal
        case 4: return 'imagens_colheita/Grama_4.png'; // seca (precisa regar)
        case 10: {
            // Grama fertilizada se fertPhase ativo na fase atual
            if (tileId !== undefined) {
                let d = tileData[tileId];
                if (d && d.fertPhase && d.planted && SEEDS_CONFIG[d.planted]) {
                    let s = SEEDS_CONFIG[d.planted];
                    let _t1 = (s.tempo_fase2 || 60) * 1000;
                    let _t2 = (s.tempo_fase3 || 60) * 1000;
                    let curPhase;
                    if (d.timer < _t1) curPhase = 1;
                    else if (d.timer < _t1 + _t2) curPhase = 2;
                    else curPhase = 3;
                    if (d.fertPhase === curPhase) return 'imagens_colheita/Grama_5.png';
                }
            }
            return 'imagens_colheita/Grama_2.png'; // plantado
        }
        case 11: return 'imagens_colheita/Grama_2.png'; // colheita
        case 12: return 'imagens_colheita/Grama_2.png'; // morta
        default: return 'imagens_colheita/Grama_3.png';
    }
}

function updateHUD() {
    let needed = getXPNeeded(myLevel);
    let pct = needed > 0 ? Math.min(100, (myXP / needed) * 100) : 100;
    document.getElementById('hud-lvl').innerText = myLevel;
    document.getElementById('hud-xp').innerText = myXP;
    document.getElementById('hud-gold').innerText = myGold;
    document.getElementById('hud-kutcoin').innerText = myKutCoin;
    document.getElementById('hud-xp-fill').style.width = pct + '%';
    document.getElementById('hud-xp-text').innerText = `${myXP}/${needed}`;
}

function saveFarmToDB() {
    if (!isOwner) return;
    fetch('/api/colheita/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ farm_data: tileData })
    }).then(r => r.json()).then(data => {
        if (data.success && data.inventory) {
            inventory = data.inventory;
        }
    }).catch(() => {});
    updateHUD();
}

function visitorSaveOwnerFarm() {
    if (isOwner) return;
    fetch('/api/colheita/save-visitor', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ farm_owner_id: profileId, farm_data: tileData })
    });
}

// Simulação offline simplificada — apenas solo (estados sem planta)
// Estados de plantas (10, 11, 12) são recalculados pelo servidor com timestamps
function simulateOfflineProgress(elapsedMs) {
    for (let id in tileData) {
        let d = tileData[id];
        if (!d.stolenBy) d.stolenBy = [];
        if (!d.harvested) d.harvested = 0;

        let rem = elapsedMs;
        while (rem > 0) {
            // State 3: Grama seca -> após 5min vira State 4 (super seca)
            if (d.state === 3 && !d.planted) {
                let needed = 300000 - d.timer;
                if (rem >= needed) { 
                    rem -= needed; 
                    d.state = 4;
                    d.timer = 0; 
                } else { 
                    d.timer += rem; 
                    rem = 0; 
                }
            }
            // State 2: Grama molhada sem planta -> após 5min vira State 4 (super seca)
            else if (d.state === 2 && !d.planted) {
                let needed = 300000 - d.timer;
                if (rem >= needed) { 
                    rem -= needed; 
                    d.state = 4;
                    d.timer = 0; 
                } else { 
                    d.timer += rem; 
                    rem = 0; 
                }
            }
            else { 
                break; // Estados com planta (10, 11, 12) são controlados pelo servidor
            }
        }
    }
}

function forceVisualUpdate() {
    for (let id in tileData) {
        let data = tileData[id];
        let t = document.querySelector(`.tile[data-id="${id}"]`);
        if(t) t.dataset.state = data.state;

        let sprite = document.getElementById('sprite-' + id);
        if (sprite) { sprite.src = getTileImage(data.state, id); }

        let plantInd = document.getElementById('plant-' + id);
        if (!plantInd) continue;

        // Próximo tile a desbloquear: mostra desbloquear.png
        const nextTile = getNextUnlockableTile();
        if (!unlockedTiles.includes(parseInt(id)) && nextTile === parseInt(id)) {
            plantInd.style.display = 'block';
            plantInd.style.backgroundImage = "url('imagens_colheita/desbloquear.png')";
            plantInd.style.width = '300px';
            plantInd.style.height = '300px';
            plantInd.style.transform = 'translate(-115%, -85%)';
            continue;
        } else if (!unlockedTiles.includes(parseInt(id))) {
            plantInd.style.display = 'none';
            continue;
        }

        // Mostrar planta apenas nos estados 2 (crescendo), 10 (crescendo), 11 (colheita), 12 (morta)
        if (data.planted && SEEDS_CONFIG[data.planted] && (data.state === 2 || data.state === 10 || data.state === 11 || data.state === 12)) {
            plantInd.style.display = 'block';
            let seed = SEEDS_CONFIG[data.planted];

            // Configuração de tamanho e posição por fase
            // [largura, altura, translateX, translateY]
            const PHASE_CONFIG = {
                1: [250, 250, '-130%', '-60%'],   // Fase 1: semente/broto (pequena)
                2: [320, 320, '-105%', '-65%'],   // Fase 2: crescendo
                3: [450, 450, '-90%', '-85%'],   // Fase 3: quase pronta
                4: [900, 900, '-69%', '-75%'],    // Fase 4: pronta para colher
                5: [300, 300, '-115%', '-75%']    // Fase 5: morta
            };

            let phaseKey, phaseImg;
            
            if (data.state === 12) {
                // Planta morta
                phaseKey = 5;
                phaseImg = seed.f5_img || 'imagens_colheita/fase5_morta.png';
            } else if (data.state === 11) {
                // Pronta para colher
                phaseKey = 4;
                phaseImg = seed.f4_img;
            } else if (data.state === 10) {
                // Crescendo (fases 1, 2, 3)
                let t1 = (seed.tempo_fase2 || 60)*1000;
                let t2 = t1 + (seed.tempo_fase3 || 60)*1000;
                if (data.timer < t1) { 
                    phaseKey = 1; 
                    phaseImg = seed.f1_img; 
                } else if (data.timer < t2) { 
                    phaseKey = 2; 
                    phaseImg = seed.f2_img; 
                } else { 
                    phaseKey = 3; 
                    phaseImg = seed.f3_img; 
                }
            } else if (data.state === 2 && data.planted) {
                // Recém plantado (fase 1)
                phaseKey = 1;
                phaseImg = seed.f1_img;
            }

            if (phaseKey) {
                let cfg = PHASE_CONFIG[phaseKey];
                plantInd.style.backgroundImage = `url('${phaseImg}')`;
                plantInd.style.width = cfg[0] + 'px';
                plantInd.style.height = cfg[1] + 'px';
                plantInd.style.transform = `translate(${cfg[2]}, ${cfg[3]})`;
            }
        } else { 
            plantInd.style.display = 'none'; 
        }
    }
}

// ========== RENDER UI ==========
function renderTopPanel() {
    const panel = document.getElementById('top-panel-container');
    let html = '';
    if (isOwner) {
        html += `<div class="top-icon" onclick="openModal('Armazém', 'colheita_armazem.php')">📦 Armazém</div>`;
        html += `<div class="top-icon" onclick="openModal('Loja da Fazenda', 'colheita_loja.php')">🛒 Loja</div>`;
    }
    html += `<div class="top-icon" onclick="toggleFloatingModal('friends-modal')">👥 Amigos</div>`;
    html += `<div class="top-icon" onclick="toggleFloatingModal('logs-modal')">📋 Avisos</div>`;
    panel.innerHTML = html;
}

function renderToolbar() {
    const tools = document.getElementById('ui-tools');
    let html = '<div id="tool-seta" class="tool action-tool" data-type="seta" title="Seta (Informações)"></div>';
    if (isOwner) {
        html += '<div id="tool-enxada" class="tool action-tool" data-type="enxada" title="Enxada (Arar)"></div>';
        html += '<div id="tool-bolsa" class="tool action-tool" data-type="bolsa" title="Bolsa (Pertences)" onclick="toggleInventoryPanel()"></div>';
        html += '<div id="tool-regador" class="tool action-tool" data-type="regador" title="Regador (Regar)"></div>';
        html += '<div id="tool-catalizador" class="tool action-tool" data-type="catalizador" title="Catalizador"></div>';
        html += '<div id="tool-inseticida" class="tool action-tool" data-type="inseticida" title="Inseticida"></div>';
        html += '<div id="tool-mao" class="tool action-tool" data-type="mao" title="Mão (Colher)"></div>';
    } else {
        html += '<div id="tool-regador" class="tool action-tool" data-type="regador" title="Regador (Ajudar)"></div>';
        html += '<div id="tool-mao" class="tool action-tool" data-type="mao" title="Mão (Roubar)"></div>';
    }
    tools.innerHTML = html;
}

function renderFriendsList(friends) {
    const list = document.getElementById('friends-list');
    let html = '';
    // "Minha fazenda" link at top
    html += `<div class="friend-farm-item">
        <img src="${myFoto}" class="friend-farm-pic" onclick="window.location.href='colheita.php?uid=${loggedUserId}'">
        <div class="friend-info" onclick="window.location.href='colheita.php?uid=${loggedUserId}'">
            <div style="font-weight:bold; font-size:12px;">Minha Fazenda</div>
            <div class="friend-stats">
                <span style="color:#4caf50; font-weight:bold;">Nv ${myLevel}</span>
                <span>🪙 ${myGold}</span>
                <span style="color:#81c784;">K$ ${myKutCoin}</span>
            </div>
        </div>
    </div>`;
    friends.forEach(f => {
        html += `<div class="friend-farm-item">
            <img src="${f.foto_perfil}" class="friend-farm-pic" onclick="window.location.href='colheita.php?uid=${f.id}'">
            <div class="friend-info" onclick="window.location.href='colheita.php?uid=${f.id}'">
                <div style="font-weight:bold; font-size:12px;">${f.nome || ''}</div>
                <div class="friend-stats">
                    <span style="color:#4caf50; font-weight:bold;">Nv ${f.level}</span>
                    <span>🪙 ${f.ouro}</span>
                    <span style="color:#81c784;">K$ ${f.kutcoin || 0}</span>
                </div>
            </div>
            <a href="profile.php?uid=${f.id}" target="_blank" class="btn-ver-perfil">Ver Perfil</a>
        </div>`;
    });
    list.innerHTML = html;
}

function renderLogsList(logs) {
    const list = document.getElementById('logs-list');
    if (!logs || logs.length === 0) {
        list.innerHTML = '<div style="color:#999; text-align:center; padding:20px;">Nenhuma atividade recente.</div>';
        return;
    }
    let html = '';
    logs.forEach(l => {
        let desc = '';
        if (l.action === 'visit') desc = 'Visitou a sua fazenda.';
        else if (l.action === 'steal') desc = l.details || 'Roubou frutos.';
        else if (l.action === 'help') desc = 'Regou a terra.';
        else desc = l.details || l.action;

        let dateStr = '';
        if (l.created_at) {
            const d = new Date(l.created_at);
            dateStr = d.toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
        }
        html += `<div class="log-item">
            <img src="${l.foto_perfil}" class="log-pic">
            <div>
                <a href="profile.php?uid=" style="color:#fff; text-decoration:none;"><b>${l.nome}</b></a>
                <div>${desc}</div>
                <div class="log-date">${dateStr}</div>
            </div>
        </div>`;
    });
    list.innerHTML = html;
}

function buildGrid() {
    const gc = document.getElementById('grid-container');
    gc.innerHTML = '';
    const cols = 6;
    const rows = Math.ceil(gridSize / cols);
    // Mapear tile IDs para posições no grid CSS de modo que a ordem visual isométrica
    // (coluna direita→esquerda, dentro de cada coluna de baixo→cima) fique sequencial
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const i = (cols - 1 - col) * rows + (rows - 1 - row);
            if (i >= gridSize) continue;
            const tile = document.createElement('div');
            tile.className = 'tile';
            tile.dataset.id = i;
            tile.dataset.state = '1';
            gc.appendChild(tile);
        }
    }
}

// ========== TOOLBAR TOGGLE ==========
function toggleToolbar() {
    const tools = document.getElementById('ui-tools');
    const btn = document.getElementById('btn-minimize');
    if (tools.style.display === 'none') {
        tools.style.display = 'flex';
        btn.textContent = '🔽';
    } else {
        tools.style.display = 'none';
        btn.textContent = '🔼';
    }
}

// ========== PAINEL DE PERTENCES (BOLSA) ==========
function toggleInventoryPanel() {
    cancelActiveTool();
    const panel = document.getElementById('inventory-panel');
    if (panel.style.display === 'flex' || panel.style.display === 'block') {
        closeInventoryPanel();
    } else {
        renderInventoryPanel();
        panel.style.display = 'block';
    }
}

function closeInventoryPanel() {
    document.getElementById('inventory-panel').style.display = 'none';
    document.getElementById('inv-tooltip').style.display = 'none';
}

function renderInventoryPanel() {
    const container = document.getElementById('inv-items');
    let html = '';
    let hasItems = false;

    // Sementes
    for (let key in inventory) {
        if (key.startsWith('seed_') && inventory[key] > 0) {
            let realKey = key.replace('seed_', '');
            let seed = SEEDS_CONFIG[realKey];
            if (!seed) continue;
            hasItems = true;
            let imgUrl = seed.f1_img || 'imagens_colheita/fase1_semente.png';
            html += `<div class="inv-item" data-key="${key}" data-type="seed"
                onmouseenter="showInvTooltip(event,'${key}','seed')"
                onmouseleave="hideInvTooltip()"
                onmousemove="moveInvTooltip(event)"
                onclick="selectSeedFromPanel('${key}')">
                <img src="${imgUrl}" alt="${seed.nome}">
                <span class="inv-qty">${inventory[key]}</span>
            </div>`;
        }
    }

    // Fertilizantes
    for (let key in inventory) {
        if (key.startsWith('fert_') && inventory[key] > 0) {
            let realKey = key.replace('fert_', '');
            let fert = FERTILIZERS_CONFIG[realKey];
            if (!fert) continue;
            hasItems = true;
            let imgUrl = 'imagens_colheita/fertilizantes/' + fert.icone;
            html += `<div class="inv-item" data-key="${key}" data-type="fert"
                onmouseenter="showInvTooltip(event,'${key}','fert')"
                onmouseleave="hideInvTooltip()"
                onmousemove="moveInvTooltip(event)"
                onclick="selectFertFromPanel('${key}')">
                <img src="${imgUrl}" alt="${fert.nome}">
                <span class="inv-qty">${inventory[key]}</span>
            </div>`;
        }
    }

    if (!hasItems) {
        html = '<div class="inv-empty">Bolsa vazia — compre itens na Loja!</div>';
    }
    container.innerHTML = html;
}

function selectSeedFromPanel(seedKey) {
    closeInventoryPanel();
    selectSeed(seedKey);
}

function selectFertFromPanel(fertKey) {
    closeInventoryPanel();
    selectFertilizer(fertKey);
}

function showInvTooltip(e, key, type) {
    const tt = document.getElementById('inv-tooltip');
    let html = '';

    if (type === 'seed') {
        let realKey = key.replace('seed_', '');
        let seed = SEEDS_CONFIG[realKey];
        if (seed) {
            let moedaLabel = seed.moeda === 'kutcoin' ? 'K$' : '🪙';
            html = `<div class="inv-tt-name">${seed.nome}</div>`;
            if (seed.descricao) html += `<div class="inv-tt-desc">${seed.descricao}</div>`;
            html += `<div class="inv-tt-info">`;
            html += `Qtd: ${inventory[key]}<br>`;
            html += `Preço: ${moedaLabel} ${seed.preco_compra}<br>`;
            html += `Venda: 🪙 ${seed.preco_venda}<br>`;
            html += `Rendimento: ${seed.rendimento} frutos<br>`;
            html += `Temporadas: ${seed.temporadas || 1}`;
            html += `</div>`;
        }
    } else if (type === 'fert') {
        let realKey = key.replace('fert_', '');
        let fert = FERTILIZERS_CONFIG[realKey];
        if (fert) {
            let timeStr = fert.tempo_reducao >= 3600 ? Math.round(fert.tempo_reducao / 3600) + 'h' : (fert.tempo_reducao >= 60 ? Math.round(fert.tempo_reducao / 60) + ' min' : fert.tempo_reducao + 's');
            html = `<div class="inv-tt-name">${fert.nome}</div>`;
            if (fert.descricao) html += `<div class="inv-tt-desc">${fert.descricao}</div>`;
            html += `<div class="inv-tt-info">`;
            html += `Qtd: ${inventory[key]}<br>`;
            html += `Reduz: ${timeStr}`;
            html += `</div>`;
        }
    }

    tt.innerHTML = html;
    tt.style.display = 'block';
    moveInvTooltip(e);
}

function moveInvTooltip(e) {
    const tt = document.getElementById('inv-tooltip');
    tt.style.left = (e.clientX + 14) + 'px';
    tt.style.top = (e.clientY - 10) + 'px';
}

function hideInvTooltip() {
    document.getElementById('inv-tooltip').style.display = 'none';
}

// ========== FULLSCREEN ==========
function toggleFullscreen() {
    const gameContainer = document.getElementById('game-container');
    if (!document.fullscreenElement) {
        gameContainer.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen().catch(() => {});
    }
}
document.addEventListener('fullscreenchange', () => {
    const btn = document.getElementById('farm-title');
    const gc = document.getElementById('game-container');
    if (document.fullscreenElement) {
        btn.innerHTML = '⛶ Sair da tela cheia';
        gc.style.width = screen.width + 'px';
        gc.style.height = screen.height + 'px';
        gc.style.minHeight = screen.height + 'px';
        gc.style.maxHeight = screen.height + 'px';
        gc.style.borderRadius = '0';
        gc.style.border = 'none';
    } else {
        btn.innerHTML = '⛶ Jogar em tela cheia';
        gc.style.width = '';
        gc.style.height = '';
        gc.style.minHeight = '';
        gc.style.maxHeight = '';
        gc.style.borderRadius = '';
        gc.style.border = '';
    }
    setTimeout(() => { if (window._initWorld) window._initWorld(); }, 100);
    setTimeout(() => { if (window._initWorld) window._initWorld(); }, 300);
});

// ========== CAMERA + GAME ENGINE ==========
let cam = { scale: 1, x: 0, y: 0, isDragging: false, startX: 0, startY: 0 };
let minScale = 1; const maxScale = 5;
let activeTool = null; let toolCursor = null;
let toolEffectEl = null;
const ENXADA_FRAMES = ['imagens_colheita/395.png', 'imagens_colheita/396.png', 'imagens_colheita/397.png'];
const REGADOR_FRAMES = ['imagens_colheita/366.png','imagens_colheita/367.png','imagens_colheita/368.png','imagens_colheita/369.png','imagens_colheita/370.png','imagens_colheita/371.png','imagens_colheita/372.png','imagens_colheita/373.png','imagens_colheita/374.png'];
const FERT_FRAMES = ['imagens_colheita/71.png','imagens_colheita/72.png','imagens_colheita/73.png','imagens_colheita/74.png'];
let effectFrameTimer = 0;

function hideToolEffect() {
    if (toolEffectEl) { toolEffectEl.style.display = 'none'; }
    effectFrameTimer = 0;
}

let hoveredTileId = null; let actionTimer = 0;
let toolActionActive = false; let toolActionTileId = null;
let instantAnimating = false;
let lastTime = performance.now();
let myFoto = 'img/perfilsemfoto.jpg';

function initGameEngine() {
    const container = document.getElementById('game-container');
    const world = document.getElementById('world');
    const bg = document.getElementById('farm-bg');
    const spriteLayer = document.getElementById('sprite-layer');
    const tooltip = document.getElementById('tooltip');

    // Create sprites for each tile
    for (let i = 0; i < gridSize; i++) {
        const img = document.createElement('img'); img.className = 'sprite-element sprite'; img.id = 'sprite-' + i; img.src = getTileImage(1, i); spriteLayer.appendChild(img);
        const plant = document.createElement('div'); plant.className = 'sprite-element plant-indicator'; plant.id = 'plant-' + i; spriteLayer.appendChild(plant);

        const lockDiv = document.createElement('div'); lockDiv.className = 'sprite-element lock-indicator'; lockDiv.id = 'lock-' + i;
        lockDiv.innerHTML = '🔒'; lockDiv.style.display = unlockedTiles.includes(i) ? 'none' : 'block';
        spriteLayer.appendChild(lockDiv);

        const progCont = document.createElement('div'); progCont.className = 'sprite-element tile-progress-container'; progCont.id = 'tile-progress-container-' + i;
        const progBar = document.createElement('div'); progBar.className = 'tile-progress-bar'; progBar.id = 'tile-progress-bar-' + i;
        const progText = document.createElement('div'); progText.className = 'tile-progress-text'; progText.id = 'tile-progress-text-' + i;
        progCont.appendChild(progBar); progCont.appendChild(progText); spriteLayer.appendChild(progCont);

        const harvestInd = document.createElement('div'); harvestInd.className = 'sprite-element harvest-indicator'; harvestInd.id = 'harvest-' + i; spriteLayer.appendChild(harvestInd);
    }
    forceVisualUpdate();

    function initWorld() {
        if (!bg.naturalWidth || !bg.naturalHeight) return;
        minScale = Math.max(container.clientWidth / bg.naturalWidth, container.clientHeight / bg.naturalHeight);
        cam.scale = minScale * 1.0;
        cam.x = (container.clientWidth - (bg.naturalWidth * cam.scale)) / 2;
        cam.y = (container.clientHeight - (bg.naturalHeight * cam.scale)) / 2;
        clampPosition(); updateTransform(); requestAnimationFrame(alignSprites);
    }
    window._initWorld = initWorld;

    function clampPosition() {
        if (!bg.naturalWidth) return;
        const minX = container.clientWidth - (bg.naturalWidth * cam.scale);
        const minY = container.clientHeight - (bg.naturalHeight * cam.scale);
        cam.x = Math.min(Math.max(cam.x, minX), 0);
        cam.y = Math.min(Math.max(cam.y, minY), 0);
    }

    function updateTransform() { world.style.transform = `translate(${cam.x}px, ${cam.y}px) scale(${cam.scale})`; }

    const tiles = document.querySelectorAll('.tile');
    function alignSprites() {
        const worldRect = world.getBoundingClientRect();
        tiles.forEach(tile => {
            const id = tile.dataset.id; const rect = tile.getBoundingClientRect();
            const centerX = (rect.left + rect.width / 2 - worldRect.left) / cam.scale;
            const centerY = (rect.top + rect.height / 2 - worldRect.top) / cam.scale;
            ['sprite-', 'plant-', 'tile-progress-container-', 'harvest-'].forEach(prefix => {
                const el = document.getElementById(prefix + id);
                if (el) { el.style.left = centerX + 'px'; el.style.top = centerY + 'px'; }
            });
        });
    }

    window.addEventListener('resize', initWorld);
    if (bg.complete && bg.naturalWidth) initWorld(); else bg.onload = initWorld;

    // Zoom
    container.addEventListener('wheel', (e) => {
        if (e.target.closest('#inventory-panel')) return;
        e.preventDefault(); tooltip.style.display = 'none';
        const newScale = Math.max(minScale, Math.min(cam.scale * Math.exp(-e.deltaY * 0.002), maxScale));
        cam.x -= (e.clientX - container.getBoundingClientRect().left - cam.x) * (newScale / cam.scale - 1);
        cam.y -= (e.clientY - container.getBoundingClientRect().top - cam.y) * (newScale / cam.scale - 1);
        cam.scale = newScale; clampPosition(); updateTransform();
    }, { passive: false });

    // Pan
    container.addEventListener('mousedown', (e) => {
        if (e.target.closest('#ui-tools') || e.target.closest('#game-hud') || e.target.closest('#game-modal') || e.target.closest('.floating-modal') || e.target.closest('.top-panel') || e.target.closest('#inventory-panel')) return;
        cam.isDragging = true; cam.startX = e.clientX - cam.x; cam.startY = e.clientY - cam.y; tooltip.style.display = 'none';
    });

    window.addEventListener('mouseup', () => cam.isDragging = false);

    window.addEventListener('mousemove', (e) => {
        if (activeTool && toolCursor) {
            const gcRect = container.getBoundingClientRect();
            const cx = Math.max(gcRect.left, Math.min(e.clientX, gcRect.right));
            const cy = Math.max(gcRect.top, Math.min(e.clientY, gcRect.bottom));
            toolCursor.style.left = cx + 'px';
            toolCursor.style.top = cy + 'px';
            const inside = e.clientX >= gcRect.left && e.clientX <= gcRect.right && e.clientY >= gcRect.top && e.clientY <= gcRect.bottom;
            toolCursor.style.display = inside ? '' : 'none';
        }
        if (cam.isDragging) {
            cam.x = e.clientX - cam.startX; cam.y = e.clientY - cam.startY;
            clampPosition(); updateTransform();
            if (hoveredTileId !== null) { const spr = document.getElementById('sprite-' + hoveredTileId); if (spr) spr.classList.remove('sprite-hovered'); }
            hoveredTileId = null; return;
        }
        const target = document.elementFromPoint(e.clientX, e.clientY);
        const tile = target ? target.closest('.tile') : null;
        const newHoverId = tile ? tile.dataset.id : null;
        if (newHoverId !== hoveredTileId) {
            // Remover brilho do sprite anterior
            if (hoveredTileId !== null) { const prevSpr = document.getElementById('sprite-' + hoveredTileId); if (prevSpr) prevSpr.classList.remove('sprite-hovered'); }
            hoveredTileId = newHoverId; actionTimer = 0;
            // Adicionar brilho no novo sprite
            if (hoveredTileId !== null) { const newSpr = document.getElementById('sprite-' + hoveredTileId); if (newSpr) newSpr.classList.add('sprite-hovered'); }
        }
        if (hoveredTileId !== null && !cam.isDragging && !document.getElementById('game-modal').style.display.includes('flex')) {
            tooltip.style.left = `${e.clientX}px`; tooltip.style.top = `${e.clientY}px`;
        } else { tooltip.style.display = 'none'; }
    });

    // Tool selection
    document.getElementById('ui-tools').addEventListener('click', (e) => {
        const tool = e.target.closest('.action-tool');
        if (!tool) return;
        e.stopPropagation();
        // bolsa opens panel, doesn't set tool
        if (tool.dataset.type === 'bolsa') return;
        closeInventoryPanel();
        if (activeTool === tool.dataset.type) { cancelActiveTool(); return; }
        cancelActiveTool(); activeTool = tool.dataset.type;
        document.querySelectorAll('#ui-tools .tool').forEach(t => t.classList.remove('active'));
        if (activeTool === 'seta') { activeTool = null; return; } // seta = no tool
        tool.classList.add('active');
        container.classList.add('tool-active');
        toolCursor = document.createElement('div'); toolCursor.className = 'active-tool-cursor';
        if (activeTool === 'mao') { toolCursor.innerText = '✋'; toolCursor.style.fontSize = '35px'; }
        else { toolCursor.style.cssText = `width:45px;height:45px;background-image:url('imagens_colheita/${activeTool}.png');background-size:contain;background-repeat:no-repeat;background-position:center; filter: drop-shadow(2px 6px 8px rgba(0,0,0,0.6));`; }
        document.getElementById('game-container').appendChild(toolCursor); toolCursor.style.left = e.clientX + 'px'; toolCursor.style.top = e.clientY + 'px';
    });

    window.addEventListener('contextmenu', (e) => { if (activeTool) { e.preventDefault(); cancelActiveTool(); } });
    window.addEventListener('keydown', (e) => { if (e.key === 'Escape' && activeTool) cancelActiveTool(); });

    container.addEventListener('click', (e) => {
        if (!activeTool || cam.isDragging) return;
        const t = e.target;
        if (t.closest('#inventory-panel')) return;
        // Regador: efeito de água em qualquer clique, sem cancelar
        if (activeTool === 'regador' && !t.closest('.tile') && !t.closest('#ui-tools') && !t.closest('#game-modal')) {
            if (toolCursor) { instantAnimating = true; toolCursor.classList.add('working'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('working'); instantAnimating = false; }, 700); }
            playWaterEffect();
            return;
        }
        // Enxada: efeito de cavar em qualquer clique, sem cancelar
        if (activeTool === 'enxada' && !t.closest('.tile') && !t.closest('#ui-tools') && !t.closest('#game-modal')) {
            if (toolCursor) { instantAnimating = true; toolCursor.classList.add('working'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('working'); instantAnimating = false; }, 700); }
            playDigEffect();
            return;
        }
        // Fertilizante: efeito de despejar em qualquer clique, sem cancelar
        if (activeTool && activeTool.startsWith('fert_') && !t.closest('.tile') && !t.closest('#ui-tools') && !t.closest('#game-modal')) {
            if (toolCursor) { instantAnimating = true; toolCursor.classList.remove('pouring'); void toolCursor.offsetWidth; toolCursor.classList.add('pouring'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('pouring'); instantAnimating = false; }, 750); }
            playFertEffect();
            return;
        }
        // Mão: não cancela ao clicar fora dos tiles
        if (activeTool === 'mao' && !t.closest('.tile') && !t.closest('#ui-tools') && !t.closest('#game-modal')) {
            return;
        }
        // Sementes: não cancela ao clicar fora dos tiles
        if (activeTool && activeTool.startsWith('seed_') && !t.closest('.tile') && !t.closest('#ui-tools') && !t.closest('#game-modal')) {
            return;
        }
        if (!t.closest('.tile') && !t.closest('#ui-tools') && !t.closest('#game-modal')) cancelActiveTool();
    });

    function cancelActiveTool() { activeTool = null; activeFertKey = null; instantAnimating = false; if (toolCursor) { toolCursor.remove(); toolCursor = null; } hideToolEffect(); container.classList.remove('tool-active'); actionTimer = 0; toolActionActive = false; toolActionTileId = null; document.querySelectorAll('#ui-tools .tool.active').forEach(t => t.classList.remove('active')); }
    window.cancelActiveTool = cancelActiveTool;

    function changeTileState(id, newState) {
        tileData[id].state = newState; tileData[id].timer = 0;
        document.querySelector(`.tile[data-id="${id}"]`).dataset.state = newState;
        const sprite = document.getElementById('sprite-' + id);
        if (sprite) sprite.src = getTileImage(newState, id);
    }

    function canUseToolOnTile(data, tool, isOwnerObj, tileId) {
        // Bloquear tiles trancados
        if (tileId !== undefined && !unlockedTiles.includes(parseInt(tileId))) return false;

        if (isOwnerObj) {
            // Regador: pode usar em Grama_3 (normal) ou Grama_4 (seca) para transformar em Grama_2 (molhada)
            if (tool === 'regador' && (data.state === 3 || data.state === 4)) return true;
            
            // Sementes: só pode plantar em Grama_2 (molhada) sem planta
            if (tool.startsWith('seed_') && data.state === 2 && !data.planted) {
                if(inventory[tool] > 0) return true;
            }
            
            // Fertilizante: pode usar em planta crescendo (state 10)
            if (tool.startsWith('fert_') && data.state === 10 && data.planted) return true;
            
            // Enxada: pode usar em qualquer planta (estados 2, 10, 11, 12) para remover
            if (tool === 'enxada' && data.planted && (data.state === 2 || data.state === 10 || data.state === 11 || data.state === 12)) return true;
            
            // Luva (mão): colher frutos prontos (state 11)
            if (tool === 'mao' && data.state === 11 && data.planted) return true;
        } else {
            // Visitante: pode regar Grama_3 ou Grama_4
            if (tool === 'regador' && (data.state === 3 || data.state === 4)) return true;
        }
        
        return false;
    }

    // Tile click handler
    tiles.forEach(tile => {
        tile.addEventListener('click', (e) => {
            if (cam.isDragging) return;
            const id = tile.dataset.id; const data = tileData[id];

            // Clicar em tile trancado: só o próximo tile a desbloquear abre popup
            if (!unlockedTiles.includes(parseInt(id))) {
                // Efeito visual mesmo em tiles trancados
                if (activeTool === 'regador') {
                    if (toolCursor) { instantAnimating = true; toolCursor.classList.add('working'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('working'); instantAnimating = false; }, 700); }
                    playWaterEffect();
                }
                if (activeTool === 'enxada') {
                    if (toolCursor) { instantAnimating = true; toolCursor.classList.add('working'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('working'); instantAnimating = false; }, 700); }
                    playDigEffect();
                }
                if (activeTool && activeTool.startsWith('fert_')) {
                    if (toolCursor) { instantAnimating = true; toolCursor.classList.remove('pouring'); void toolCursor.offsetWidth; toolCursor.classList.add('pouring'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('pouring'); instantAnimating = false; }, 750); }
                    playFertEffect();
                }
                // Mão e sementes: não cancela em tiles trancados
                if (activeTool === 'mao' || (activeTool && activeTool.startsWith('seed_'))) {
                    // Apenas não cancela, sem efeito visual
                }
                const nextTile = getNextUnlockableTile();
                if (isOwner && parseInt(id) === nextTile) showTileUnlockPopup(parseInt(id));
                return;
            }

            if (activeTool && activeTool !== 'seta') {
                // Fertilizante: ação instantânea
                if (activeTool.startsWith('fert_') && data.state === 10 && data.planted) {
                    applyFertilizerToTile(id, activeTool);
                    return;
                }
                if (activeTool.startsWith('fert_')) {
                    if (toolCursor) { instantAnimating = true; toolCursor.classList.remove('pouring'); void toolCursor.offsetWidth; toolCursor.classList.add('pouring'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('pouring'); instantAnimating = false; }, 750); }
                    playFertEffect();
                    showToast('Clique em uma planta que esteja crescendo!', true);
                    return;
                }
                // Regador: ação instantânea com efeito
                if (activeTool === 'regador' && (data.state === 3 || data.state === 4)) {
                    if (toolCursor) { instantAnimating = true; toolCursor.classList.add('working'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('working'); instantAnimating = false; }, 700); }
                    playWaterEffect();
                    playPuffOnTile(id, () => {
                        data.state = 2;
                        data.planted = null;
                        data.harvested = 0;
                        data.stolenBy = [];
                        data.timer = 0;
                        data.watered_at = Date.now();
                        if (isOwner) { forceVisualUpdate(); saveFarmToDB(); }
                        else {
                            fetch('/api/colheita/visitor-action', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ farm_owner_id: profileId, type: 'help' })
                            }).then(r=>r.json()).then(res => {
                                if(res.status === 'ok') { addXP(1); visitorSaveOwnerFarm(); showToast('+1 XP por ajudar!', false); }
                            });
                            forceVisualUpdate();
                        }
                    });
                    return;
                }
                if (activeTool === 'regador') {
                    if (toolCursor) { instantAnimating = true; toolCursor.classList.add('working'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('working'); instantAnimating = false; }, 700); }
                    playWaterEffect();
                    return;
                }
                // Enxada: ação instantânea com efeito
                if (activeTool === 'enxada' && data.planted && (data.state === 2 || data.state === 10 || data.state === 11 || data.state === 12)) {
                    if (toolCursor) { instantAnimating = true; toolCursor.classList.add('working'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('working'); instantAnimating = false; }, 700); }
                    playDigEffect();
                    const seed = SEEDS_CONFIG[data.planted];
                    const plantName = seed ? seed.nome : 'planta';
                    // Planta morta (state 12): remover direto sem confirmação
                    if (data.state === 12) {
                        playPuffOnTile(id, () => {
                            data.planted = null;
                            data.state = 3;
                            data.harvested = 0;
                            data.timer = 0;
                            data.stolenBy = [];
                            delete data.currentSeason;
                            delete data.planted_at;
                            delete data.harvestable_at;
                            delete data.fert_bonus;
                            forceVisualUpdate();
                            saveFarmToDB();
                            showToast('Planta murcha removida!', false);
                        });
                        return;
                    }
                    // Confirmação antes de remover planta viva
                    window.showConfirm(`Tem certeza que deseja remover esta ${plantName}? Você perderá todo o progresso!`, function() {
                        playPuffOnTile(id, () => {
                            data.planted = null;
                            data.state = 3;
                            data.harvested = 0;
                            data.timer = 0;
                            data.stolenBy = [];
                            delete data.currentSeason;
                            delete data.planted_at;
                            delete data.harvestable_at;
                            delete data.fert_bonus;
                            forceVisualUpdate();
                            saveFarmToDB();
                            showToast('Planta removida com sucesso!', false);
                        });
                    }, { title: 'Remover Planta', danger: true, container: '#game-container' });
                    return;
                }
                if (activeTool === 'enxada') {
                    if (toolCursor) { instantAnimating = true; toolCursor.classList.add('working'); setTimeout(() => { if (toolCursor) toolCursor.classList.remove('working'); instantAnimating = false; }, 700); }
                    playDigEffect();
                    return;
                }
                // Luva (mão): colheita instantânea
                if (activeTool === 'mao' && data.state === 11 && data.planted && isOwner) {
                    let seed = SEEDS_CONFIG[data.planted];
                    let maxYield = seed ? seed.rendimento : 50;
                    let available = maxYield - (data.harvested || 0);
                    let maxTemporadas = seed ? (seed.temporadas || 1) : 1;
                    let currentSeason = data.currentSeason || 1;
                    if (available > 0) {
                        // Colher via servidor (valida XP e ouro no backend)
                        fetch('/api/colheita/harvest', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ tile_id: id, amount: available })
                        }).then(r => r.json()).then(hData => {
                            if (hData.success) {
                                myLevel = hData.level;
                                myXP = hData.xp;
                                myGold = hData.ouro;
                                inventory = hData.inventory;
                                tileData = hData.farm_data;
                                showHarvestFloat(id, data.planted, hData.harvested);
                                if (currentSeason < maxTemporadas) {
                                    showToast(`Colheu ${hData.harvested} frutos! Temporada ${currentSeason+1}/${maxTemporadas} — crescendo de novo! +${hData.xp_gain}⭐`, false);
                                } else {
                                    showToast(`Colheu ${hData.harvested} frutos e ganhou ${hData.xp_gain} estrelas!`, false);
                                }
                                forceVisualUpdate();
                                updateHUD();
                            } else {
                                showToast(hData.message || 'Erro ao colher.', true);
                            }
                        }).catch(() => showToast('Erro de conexão.', true));
                    } else {
                        if (currentSeason < maxTemporadas) {
                            let _t1b = (seed.tempo_fase2 || 60)*1000;
                            let _t2b = (seed.tempo_fase3 || 60)*1000;
                            playPuffOnTile(id, () => {
                                data.state = 10;
                                data.planted_at = Date.now() - _t1b - _t2b; // Reinício na fase 3
                                data.timer = _t1b + _t2b;
                                data.harvested = 0;
                                data.stolenBy = [];
                                data.currentSeason = currentSeason + 1;
                                delete data.fertPhase;
                                delete data.fert_bonus;
                                delete data.harvestable_at;
                                forceVisualUpdate();
                                saveFarmToDB();
                            });
                            showToast(`Frutos roubados! Temporada ${currentSeason+1}/${maxTemporadas} — crescendo de novo!`, true);
                        } else {
                            playPuffOnTile(id, () => {
                                data.planted = null;
                                data.state = 3;
                                data.timer = 0;
                                data.harvested = 0;
                                data.stolenBy = [];
                                delete data.currentSeason;
                                delete data.planted_at;
                                delete data.harvestable_at;
                                delete data.fert_bonus;
                                delete data.fertPhase;
                                forceVisualUpdate();
                                saveFarmToDB();
                            });
                            showToast('Frutos roubados! Planta morreu.', true);
                        }
                    }
                    return;
                }
                if (activeTool === 'mao') {
                    return;
                }
                // Sementes: plantio instantâneo
                if (activeTool.startsWith('seed_') && data.state === 2 && !data.planted && isOwner) {
                    if (inventory[activeTool] > 0) {
                        inventory[activeTool]--;
                        let pType = activeTool.split('_')[1];
                        data.planted = pType;
                        data.harvested = 0;
                        data.stolenBy = [];
                        data.currentSeason = 1;
                        data.state = 10;
                        data.planted_at = Date.now();
                        data.timer = 0;
                        forceVisualUpdate();
                        saveFarmToDB();
                        let seedName = SEEDS_CONFIG[pType] ? SEEDS_CONFIG[pType].nome : 'Semente';
                        showToast(`${seedName} plantada! Restam ${inventory[activeTool]}`, false);
                        // Cancelar se sem sementes OU se não há mais tiles plantáveis
                        let hasPlantableTile = unlockedTiles.some(tid => {
                            let td = tileData[tid];
                            return td && td.state === 2 && !td.planted && String(tid) !== String(id);
                        });
                        if (inventory[activeTool] <= 0 || !hasPlantableTile) {
                            cancelActiveTool();
                        }
                    } else {
                        showToast('Sem sementes! Compre na loja.', true);
                        cancelActiveTool();
                    }
                    return;
                }
                if (activeTool.startsWith('seed_')) {
                    showToast('Clique em uma terra molhada para plantar a semente.');
                    return;
                }
                if (canUseToolOnTile(data, activeTool, isOwner, id)) {
                    // Iniciar ação com timer (não executa instantaneamente)
                    if (!toolActionActive || toolActionTileId !== id) {
                        toolActionActive = true;
                        toolActionTileId = id;
                        hoveredTileId = id;
                        actionTimer = 0;
                        effectFrameTimer = 0;
                        // Já começa a animação de trabalho ao clicar
                        if (toolCursor) toolCursor.classList.add('working');
                        // Já mostra o efeito visual (água/terra) ao clicar
                        if (activeTool === 'enxada' || activeTool === 'regador') {
                            if (!toolEffectEl) {
                                toolEffectEl = document.createElement('div');
                                toolEffectEl.className = 'tool-effect';
                                document.getElementById('game-container').appendChild(toolEffectEl);
                            }
                            toolEffectEl.style.display = 'block';
                            if (activeTool === 'enxada') {
                                toolEffectEl.style.width = '60px';
                                toolEffectEl.style.height = '60px';
                                if (toolCursor) {
                                    toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
                                    toolEffectEl.style.top = toolCursor.style.top;
                                }
                                toolEffectEl.style.backgroundImage = `url('${ENXADA_FRAMES[0]}')`;
                            } else {
                                toolEffectEl.style.width = '28px';
                                toolEffectEl.style.height = '28px';
                                if (toolCursor) {
                                    toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
                                    toolEffectEl.style.top = (parseFloat(toolCursor.style.top) - 5) + 'px';
                                }
                                toolEffectEl.style.backgroundImage = `url('${REGADOR_FRAMES[0]}')`;
                            }
                        }
                    }
                } else {
                    showToast("Não é possível usar esta ferramenta aqui.", true);
                }
                return;
            }

            if (isOwner) {
                // State 10: planta crescendo — informar sobre fertilizante
                if (data.state === 10 && data.planted) {
                    let _seed = SEEDS_CONFIG[data.planted];
                    let _maxTemp = _seed ? (_seed.temporadas || 1) : 1;
                    let _curTemp = data.currentSeason || 1;
                    let tempMsg = _maxTemp > 1 ? ` (Temporada ${_curTemp}/${_maxTemp})` : '';
                    showToast('Use um fertilizante da Bolsa para acelerar!' + tempMsg, false);
                    return;
                }
                // Colher: state 11 (pronta para colher) — precisa usar a Luva
                if (data.state === 11) {
                    let _seed = SEEDS_CONFIG[data.planted];
                    let _maxTemp = _seed ? (_seed.temporadas || 1) : 1;
                    let _curTemp = data.currentSeason || 1;
                    let tempMsg = _maxTemp > 1 ? ` (Temporada ${_curTemp}/${_maxTemp})` : '';
                    showToast("Use a Luva para colher os frutos!" + tempMsg, true);
                    return;
                }
                if (data.state === 12) showToast("A planta morreu. Use a enxada para limpar.", true);
            } else {
                // Visitante roubando: state 11 (colheita)
                if (activeTool === 'mao' && data.state === 11) {
                    if (data.stolenBy && data.stolenBy.includes(loggedUserId)) { showToast('Você já roubou esta planta!', true); return; }
                    fetch('/api/colheita/visitor-action', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ farm_owner_id: profileId, type: 'steal', tile_id: id })
                    }).then(r=>r.json()).then(res => {
                        if (res.status === 'ok') {
                            addXP(5); myDailySteals += res.amount;
                            if (!inventory[res.plant]) inventory[res.plant] = 0; inventory[res.plant] += res.amount;
                            data.harvested = (data.harvested || 0) + res.amount;
                            if(!data.stolenBy) data.stolenBy = []; data.stolenBy.push(loggedUserId);
                            updateHUD(); forceVisualUpdate();
                            showToast(`Roubou ${res.amount} frutos para seu armazém. +5 XP!`, false);
                        } else { showToast(res.msg || 'Você atingiu o limite ou sem frutos agora.', true); }
                    });
                }
            }
        });
    });

    let enxadaConfirmShown = false; // Flag para evitar múltiplas confirmações
    
    function performToolAction(id, type) {
        let data = tileData[id];
        
        if (type === 'enxada') {
            // Enxada: remove qualquer planta
            if(data.planted && !enxadaConfirmShown) {
                const seed = SEEDS_CONFIG[data.planted];
                const plantName = seed ? seed.nome : 'planta';
                
                // Planta morta (state 12): remover direto sem confirmação
                if (data.state === 12) {
                    playPuffOnTile(id, () => {
                        data.planted = null; 
                        data.state = 3; 
                        data.harvested = 0; 
                        data.timer = 0; 
                        data.stolenBy = [];
                        delete data.currentSeason;
                        delete data.planted_at;
                        delete data.harvestable_at;
                        delete data.fert_bonus;
                        forceVisualUpdate();
                        saveFarmToDB();
                        showToast('Planta murcha removida!', false);
                    });
                    cancelActiveTool();
                    return;
                }
                
                // Planta viva: pedir confirmação
                enxadaConfirmShown = true;
                window.showConfirm(`Tem certeza que deseja remover esta ${plantName}? Você perderá todo o progresso!`, function() {
                    playPuffOnTile(id, () => {
                        data.planted = null; 
                        data.state = 3; 
                        data.harvested = 0; 
                        data.timer = 0; 
                        data.stolenBy = [];
                        delete data.currentSeason;
                        delete data.planted_at;
                        delete data.harvestable_at;
                        delete data.fert_bonus;
                        forceVisualUpdate();
                        saveFarmToDB();
                        showToast('Planta removida com sucesso!', false);
                    });
                    cancelActiveTool();
                    enxadaConfirmShown = false;
                }, { title: 'Remover Planta', danger: true, container: '#game-container' });
                
                // Callback para quando cancelar
                setTimeout(() => {
                    if (enxadaConfirmShown) {
                        enxadaConfirmShown = false;
                        cancelActiveTool();
                    }
                }, 100);
                return;
            }
            return;
        }
        else if (type === 'mao') {
            // Luva: colher frutos (state 11) — via servidor
            if (data.state === 11 && data.planted) {
                let seed = SEEDS_CONFIG[data.planted];
                let maxYield = seed ? seed.rendimento : 50;
                let available = maxYield - (data.harvested || 0);
                let maxTemporadas = seed ? (seed.temporadas || 1) : 1;
                let currentSeason = data.currentSeason || 1;
                if (available > 0) {
                    fetch('/api/colheita/harvest', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ tile_id: id, amount: available })
                    }).then(r => r.json()).then(hData => {
                        if (hData.success) {
                            myLevel = hData.level;
                            myXP = hData.xp;
                            myGold = hData.ouro;
                            inventory = hData.inventory;
                            tileData = hData.farm_data;
                            showHarvestFloat(id, data.planted, hData.harvested);
                            if (currentSeason < maxTemporadas) {
                                showToast(`Colheu ${hData.harvested} frutos! Temporada ${currentSeason+1}/${maxTemporadas} — crescendo de novo! +${hData.xp_gain}⭐`, false);
                            } else {
                                showToast(`Colheu ${hData.harvested} frutos e ganhou ${hData.xp_gain} estrelas!`, false);
                            }
                            forceVisualUpdate();
                            updateHUD();
                        } else {
                            showToast(hData.message || 'Erro ao colher.', true);
                        }
                    }).catch(() => showToast('Erro de conexão.', true));
                } else {
                    if (currentSeason < maxTemporadas) {
                        let _t1b = (seed.tempo_fase2 || 60)*1000;
                        let _t2b = (seed.tempo_fase3 || 60)*1000;
                        playPuffOnTile(id, () => {
                            data.state = 10;
                            data.planted_at = Date.now() - _t1b - _t2b; // Reinício na fase 3
                            data.timer = _t1b + _t2b;
                            data.harvested = 0;
                            data.stolenBy = [];
                            data.currentSeason = currentSeason + 1;
                            delete data.fertPhase;
                            delete data.fert_bonus;
                            delete data.harvestable_at;
                            forceVisualUpdate();
                            saveFarmToDB();
                        });
                        showToast(`Frutos roubados! Temporada ${currentSeason+1}/${maxTemporadas} — crescendo de novo!`, true);
                    } else {
                        playPuffOnTile(id, () => {
                            data.planted = null;
                            data.state = 3;
                            data.timer = 0;
                            data.harvested = 0;
                            data.stolenBy = [];
                            delete data.currentSeason;
                            delete data.planted_at;
                            delete data.harvestable_at;
                            delete data.fert_bonus;
                            forceVisualUpdate();
                            saveFarmToDB();
                        });
                        showToast("Todos os frutos já foram colhidos ou roubados!", true);
                    }
                }
            }
            actionTimer = 0;
            if (toolCursor) toolCursor.classList.remove('working');
            hideToolEffect();
            return;
        }
        else if (type === 'regador') { 
            // Regador: transforma grama seca (state 3/4) em molhada (state 2)
            playPuffOnTile(id, () => {
                data.state = 2; 
                data.planted = null;
                data.harvested = 0;
                data.stolenBy = [];
                data.timer = 0;
                data.watered_at = Date.now();
                if (isOwner) { forceVisualUpdate(); saveFarmToDB(); }
                else {
                    fetch('/api/colheita/visitor-action', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ farm_owner_id: profileId, type: 'help' })
                    }).then(r=>r.json()).then(res => {
                        if(res.status === 'ok') { addXP(1); visitorSaveOwnerFarm(); showToast('+1 XP por ajudar!', false); }
                    });
                    forceVisualUpdate();
                }
            });
            actionTimer = 0;
            if (toolCursor) toolCursor.classList.remove('working');
            hideToolEffect();
            return;
        }
        else if (type.startsWith('seed_')) {
            // Plantar: só funciona em grama molhada (state 2)
            inventory[type]--; 
            let pType = type.split('_')[1];
            data.planted = pType; 
            data.harvested = 0; 
            data.stolenBy = []; 
            data.currentSeason = 1;
            data.state = 10; // muda para estado "crescendo"
            data.planted_at = Date.now();
            if (inventory[type] <= 0) {
                data.timer = 0; actionTimer = 0;
                if (toolCursor) toolCursor.classList.remove('working');
                hideToolEffect();
                if (isOwner) { forceVisualUpdate(); saveFarmToDB(); }
                cancelActiveTool();
                return;
            }
        }

        data.timer = 0; 
        actionTimer = 0; 
        if (toolCursor) toolCursor.classList.remove('working');
        hideToolEffect();

        if (isOwner) { 
            forceVisualUpdate(); 
            saveFarmToDB(); 
        }
        else {
            fetch('/api/colheita/visitor-action', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ farm_owner_id: profileId, type: 'help' })
            }).then(r=>r.json()).then(res => {
                if(res.status === 'ok') { addXP(1); visitorSaveOwnerFarm(); showToast('+1 XP por ajudar!', false); }
            });
            forceVisualUpdate();
        }
    }

    // GAME LOOP
    function gameLoop(time) {
        const dt = time - lastTime; lastTime = time;
        const nowTs = Date.now();

        for (let id in tileData) {
            let d = tileData[id];
            if (!d.stolenBy) d.stolenBy = [];
            if (!d.harvested) d.harvested = 0;

            let tileProgCont = document.getElementById('tile-progress-container-' + id);
            let tileProgBar = document.getElementById('tile-progress-bar-' + id);
            let tileProgText = document.getElementById('tile-progress-text-' + id);
            let harvestInd = document.getElementById('harvest-' + id);

            if (isOwner) {
                // State 3: Grama seca -> após 5min vira State 4 (super seca)
                if (d.state === 3 && !d.planted) {
                    d.timer += dt;
                    if (d.timer >= 300000) { 
                        changeTileState(id, 4); // vira super seca
                        d.timer = 0;
                    }
                }
                // State 2: Grama molhada sem planta -> após 5min vira State 4 (super seca)
                else if (d.state === 2 && !d.planted) {
                    d.timer += dt;
                    if (d.timer >= 300000) { 
                        changeTileState(id, 4); // vira super seca
                        d.timer = 0;
                    }
                }
                // State 10: Plantado crescendo -> vira State 11 (colheita) — TIMER DO SERVIDOR
                else if (d.state === 10 && d.planted) {
                    let prevTimer = d.timer || 0;
                    // Usar timestamp do servidor para calcular timer (anti-cheat)
                    if (d.planted_at) {
                        d.timer = nowTs - d.planted_at + (d.fert_bonus || 0);
                    } else {
                        d.timer += dt; // fallback para dados antigos sem planted_at
                    }
                    let seed = SEEDS_CONFIG[d.planted];
                    let t1 = (seed.tempo_fase2 || 60)*1000;
                    let t2 = (seed.tempo_fase3 || 60)*1000;
                    let t3 = (seed.tempo_fase4 || 60)*1000;
                    let totalGrow = t1 + t2 + t3;

                    // Detectar mudança de fase para limpar fertPhase
                    let prevPhase = prevTimer < t1 ? 1 : (prevTimer < t1+t2 ? 2 : 3);
                    let curPhase = d.timer < t1 ? 1 : (d.timer < t1+t2 ? 2 : 3);
                    if (curPhase !== prevPhase && d.fertPhase) {
                        delete d.fertPhase; // Limpar fertilização ao mudar de fase
                        forceVisualUpdate(); // Volta para Grama_2.png
                    }

                    if (d.timer >= totalGrow) {
                        delete d.fertPhase;
                        let harvestableAt = d.planted_at ? (d.planted_at + totalGrow - (d.fert_bonus || 0)) : nowTs;
                        changeTileState(id, 11); // pronto para colher
                        d.harvestable_at = harvestableAt;
                        d.timer = nowTs - harvestableAt;
                        forceVisualUpdate();
                    } else {
                        // Atualizar visual das fases
                        if (curPhase !== prevPhase) {
                            forceVisualUpdate();
                        }
                    }
                }
                // State 11: Colheita -> após tempo vira State 12 (morta) — TIMER DO SERVIDOR
                else if (d.state === 11 && d.planted) {
                    // Usar timestamp do servidor para calcular timer (anti-cheat)
                    if (d.harvestable_at) {
                        d.timer = nowTs - d.harvestable_at;
                    } else {
                        d.timer += dt; // fallback
                    }
                    let seed = SEEDS_CONFIG[d.planted];
                    let f4_time = (seed ? (seed.tempo_fase5 || 300) : 300) * 1000;

                    if (d.timer >= f4_time) {
                        changeTileState(id, 12); // planta morta
                        d.timer = 0;
                        delete d.harvestable_at;
                        forceVisualUpdate();
                    }
                }
            }

            // Esconder barra de progresso (agora só no tooltip)
            if (tileProgCont) {
                tileProgCont.style.display = 'none';
            }

            // Indicador de colheita: mostrar em State 11
            if (d.state === 11 && d.planted && SEEDS_CONFIG[d.planted]) {
                let seed = SEEDS_CONFIG[d.planted];
                let maxYield = seed.rendimento || 50;
                let available = maxYield - (d.harvested || 0);
                if (available > 0) {
                    harvestInd.style.display = 'block'; 
                    const c = seed.cor_colheita || '#FFD700';
                    harvestInd.style.borderColor = c; 
                    harvestInd.style.boxShadow = `0 0 20px ${c}`;
                } else { 
                    harvestInd.style.display = 'none'; 
                }
            } else { 
                harvestInd.style.display = 'none'; 
            }
        }

        // Tool action timer
        if (activeTool && hoveredTileId !== null && toolActionActive && toolActionTileId === hoveredTileId && !cam.isDragging && !document.getElementById('game-modal').style.display.includes('flex')) {
            const data = tileData[hoveredTileId];
            let canUse = canUseToolOnTile(data, activeTool, isOwner, hoveredTileId);
            if (canUse) {
                actionTimer += dt; if (toolCursor) toolCursor.classList.add('working');
                let actionDuration = (activeTool === 'enxada' || activeTool === 'regador' || activeTool === 'mao') ? 3000 : 1000;
                if (actionTimer >= actionDuration) { performToolAction(hoveredTileId, activeTool); toolActionActive = false; toolActionTileId = null; }

                // Tool effect animation
                if (activeTool === 'enxada' || activeTool === 'regador') {
                    if (!toolEffectEl) {
                        toolEffectEl = document.createElement('div');
                        toolEffectEl.className = 'tool-effect';
                        document.getElementById('game-container').appendChild(toolEffectEl);
                    }
                    toolEffectEl.style.display = 'block';
                    effectFrameTimer += dt;

                    if (activeTool === 'enxada') {
                        toolEffectEl.style.width = '60px';
                        toolEffectEl.style.height = '60px';
                        if (toolCursor) {
                            toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
                            toolEffectEl.style.top = toolCursor.style.top;
                        }
                        let frameIndex = Math.floor(effectFrameTimer / 150) % ENXADA_FRAMES.length;
                        toolEffectEl.style.backgroundImage = `url('${ENXADA_FRAMES[frameIndex]}')`;   
                    } else {
                        // Regador: água caindo da ponta do bico (esquerda)
                        toolEffectEl.style.width = '28px';
                        toolEffectEl.style.height = '28px';
                        if (toolCursor) {
                            toolEffectEl.style.left = (parseFloat(toolCursor.style.left) - 30) + 'px';
                            toolEffectEl.style.top = (parseFloat(toolCursor.style.top) - 5) + 'px';
                        }
                        let frameIndex = Math.floor(effectFrameTimer / 100) % REGADOR_FRAMES.length;
                        toolEffectEl.style.backgroundImage = `url('${REGADOR_FRAMES[frameIndex]}')`;   
                    }
                } else {
                    hideToolEffect();
                }
            } else {
                actionTimer = 0; toolActionActive = false; toolActionTileId = null;
                if (toolCursor && !instantAnimating) toolCursor.classList.remove('working');
                if (!instantAnimating) hideToolEffect();
            }
        } else {
            if (toolActionActive && hoveredTileId !== toolActionTileId) {
                toolActionActive = false; toolActionTileId = null; actionTimer = 0;
            }
            if (!toolActionActive) { actionTimer = 0; }
            if (toolCursor && !instantAnimating) toolCursor.classList.remove('working');
            if (!instantAnimating) hideToolEffect();
        }

        // Tooltip
        if (hoveredTileId === null || cam.isDragging || document.getElementById('game-modal').style.display.includes('flex')) {
            tooltip.style.display = 'none';
        } else {
            tooltip.style.display = 'block'; const d = tileData[hoveredTileId];
            let tpCont = document.getElementById('tooltip-progress-container');
            let tpBar = document.getElementById('tooltip-progress'); let txt = document.getElementById('tooltip-text');
            let tpSeason = document.getElementById('tooltip-season');

            // Tile trancado
            if (!unlockedTiles.includes(parseInt(hoveredTileId))) {
                const nextTile = getNextUnlockableTile();
                if (parseInt(hoveredTileId) === nextTile) {
                    const tReq = tileReqs.find(r => r.tile_id === nextTile);
                    if (tReq) {
                        txt.innerText = `Desbloquear — Nível ${tReq.nivel_minimo} | ${tReq.preco.toLocaleString('pt-BR')} moedas`;
                    } else {
                        txt.innerText = 'Clique para desbloquear';
                    }
                    tooltip.style.display = 'block';
                } else {
                    tooltip.style.display = 'none';
                }
                tpCont.style.display = 'none'; tpSeason.style.display = 'none';
            } else if (activeTool && actionTimer > 0) {
                let actionDur = (activeTool === 'enxada' || activeTool === 'regador' || activeTool === 'mao') ? 3000 : 1000;
                tpCont.style.display = 'block'; tpBar.style.backgroundColor = "#ffeb3b";
                tpBar.style.width = Math.min(100, (actionTimer / actionDur) * 100) + '%';
                tpSeason.style.display = 'none';
                if (activeTool === 'enxada') txt.innerText = 'Arando terra...';
                else if (activeTool === 'regador') txt.innerText = 'Regando terra...';
                else if (activeTool === 'mao') txt.innerText = 'Colhendo frutos...';
                else txt.innerText = 'Plantando semente...';
            } else {
                // Helper: format remaining time
                function fmtTime(ms) {
                    let sec = Math.max(0, Math.ceil(ms / 1000));
                    let h = Math.floor(sec / 3600);
                    let m = Math.floor((sec % 3600) / 60);
                    let s = sec % 60;
                    if (h >= 1) return `${h} hora${h>1?'s':''} ${m} min`;
                    return `${m} min ${s} sec`;
                }

                // State 3: Grama seca
                if (d.state === 3) {
                    txt.innerText = "Terra Seca - Precisa Molhar para Plantar";
                    tpCont.style.display = 'none'; tpSeason.style.display = 'none';
                }
                // State 4: Grama super seca
                else if (d.state === 4) {
                    txt.innerText = "Terra Super Seca - Precisa Molhar para Plantar";
                    tpCont.style.display = 'none'; tpSeason.style.display = 'none';
                }
                // State 2: Grama molhada (sem planta)
                else if (d.state === 2 && !d.planted) {
                    txt.innerText = "Terra Molhada - Apropriada para Plantação";
                    tpCont.style.display = 'none'; tpSeason.style.display = 'none';
                }
                // State 10: Plantado crescendo
                else if (d.state === 10) {
                    let seed = SEEDS_CONFIG[d.planted];
                    let t1 = (seed.tempo_fase2 || 60)*1000;
                    let t2 = (seed.tempo_fase3 || 60)*1000;
                    let t3 = (seed.tempo_fase4 || 60)*1000;
                    let totalGrow = t1 + t2 + t3;
                    let p = Math.min(100, (d.timer / totalGrow) * 100);
                    let fase, remaining;
                    if (d.timer < t1) { fase = 'Germinando'; remaining = t1 - d.timer; }
                    else if (d.timer < t1 + t2) { fase = 'Brotando'; remaining = (t1 + t2) - d.timer; }
                    else { fase = 'Crescendo'; remaining = totalGrow - d.timer; }
                    let _maxT = seed.temporadas || 1;
                    let _curT = d.currentSeason || 1;
                    txt.innerText = `${seed.nome} — ${fase} (${fmtTime(remaining)})`;
                    tpCont.style.display = 'block'; tpBar.style.backgroundColor = '#4caf50';
                    tpBar.style.width = p + '%';
                    if (_maxT > 1 && d.timer >= t1 + t2) {
                        document.getElementById('tooltip-season-text').innerText = `Temporada ${_curT}/${_maxT}`;
                        document.getElementById('tooltip-season-bar').style.width = ((_curT / _maxT) * 100) + '%';
                        tpSeason.style.display = 'block';
                    } else {
                        tpSeason.style.display = 'none';
                    }
                }
                // State 11: Pronto para colher
                else if (d.state === 11) {
                    let seed = SEEDS_CONFIG[d.planted];
                    let maxYield = seed.rendimento || 50;
                    let available = maxYield - (d.harvested || 0);
                    let _maxTemp11 = seed.temporadas || 1;
                    let _curTemp11 = d.currentSeason || 1;
                    txt.innerText = `${seed.nome} - Restam ${available}/${maxYield}`;
                    tpCont.style.display = 'block'; 
                    tpBar.style.backgroundColor = '#4caf50';
                    tpBar.style.width = ((available / maxYield) * 100) + '%';
                    if (_maxTemp11 > 1) {
                        document.getElementById('tooltip-season-text').innerText = `Temporada ${_curTemp11}/${_maxTemp11}`;
                        document.getElementById('tooltip-season-bar').style.width = ((_curTemp11 / _maxTemp11) * 100) + '%';
                        tpSeason.style.display = 'block';
                    } else {
                        tpSeason.style.display = 'none';
                    }
                }
                // State 12: Planta morta
                else if (d.state === 12) {
                    txt.innerText = "Planta Morta — Use a enxada para limpar";
                    tpCont.style.display = 'none'; tpSeason.style.display = 'none';
                }
                else {
                    txt.innerText = "Terra Seca - Precisa Molhar para Plantar";
                    tpCont.style.display = 'none'; tpSeason.style.display = 'none';
                }
            }
        }
        requestAnimationFrame(gameLoop);
    }
    requestAnimationFrame(gameLoop);

    window.addEventListener('message', function(event) { if(event.data === 'update') { updateHUD(); } });
}

// ========== TILE UNLOCK ==========
function showTileUnlockPopup(tileId) {
    const req = tileReqs.find(r => r.tile_id === tileId);
    if (!req) { showToast('Tile não disponível.', true); return; }

    const meetsLevel = myLevel >= req.nivel_minimo;
    const meetsGold = myGold >= req.preco;
    const canBuy = meetsLevel && meetsGold;

    let popup = document.getElementById('tile-unlock-popup');
    if (!popup) {
        popup = document.createElement('div');
        popup.id = 'tile-unlock-popup';
        document.getElementById('game-container').appendChild(popup);
    }

    popup.innerHTML = `
        <div class="tile-unlock-content">
            <div class="tile-unlock-title">Liberar novo espaço</div>
            <div style="font-size:20px; font-weight:bold; color:#FFD700; margin-bottom:8px; text-align:center; width:100%; position:relative; top:-35px; text-shadow: -3px -3px 0 #000, 3px -3px 0 #000, -3px 3px 0 #000, 3px 3px 0 #000, -3px 0 0 #000, 3px 0 0 #000, 0 -3px 0 #000, 0 3px 0 #000, -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000, -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;">Requisitos Mínimos</div>
            <div class="tile-unlock-reqs">
                <div class="req-item ${meetsLevel ? 'ok' : 'fail'}">
                    <span>${meetsLevel ? '✅' : '❌'}</span>
                    <span style="color:${meetsLevel ? '#2e7d32' : '#c62828'}">Nível: ${myLevel}/${req.nivel_minimo}</span>
                </div>
                <div class="req-item ${meetsGold ? 'ok' : 'fail'}">
                    <span>${meetsGold ? '✅' : '❌'}</span>
                    <span style="color:${meetsGold ? '#2e7d32' : '#c62828'}">🪙 ${myGold.toLocaleString('pt-BR')}/${req.preco.toLocaleString('pt-BR')}</span>
                </div>
            </div>
            ${canBuy ? '<button class="tile-unlock-btn" onclick="unlockTile(' + tileId + ')"><img src="imagens_colheita/ok.png" alt="OK"><span>Confirmar</span></button>' : '<div style="color:#f44336; font-size:12px; position:absolute; bottom:20px; left:0; right:0; text-align:center;">Você não atende aos requisitos.</div>'}
            <button class="tile-unlock-close" onclick="closeTileUnlockPopup()"><img src="imagens_colheita/fechar.png" alt="Fechar"></button>
        </div>
    `;
    popup.style.display = 'flex';
}

function closeTileUnlockPopup() {
    const popup = document.getElementById('tile-unlock-popup');
    if (popup) popup.style.display = 'none';
}

async function unlockTile(tileId) {
    try {
        const resp = await fetch('/api/colheita/unlock-tile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tile_id: tileId })
        }).then(r => r.json());

        if (resp.success) {
            unlockedTiles = resp.unlockedTiles;
            myGold = resp.ouro;
            updateHUD();
            closeTileUnlockPopup();
            forceVisualUpdate();
            showToast(`✅ Tile ${tileId + 1} liberado!`, false);
        } else {
            showToast(resp.message || 'Erro ao liberar tile.', true);
        }
    } catch(err) {
        showToast('Erro ao liberar tile.', true);
    }
}

// ========== INIT ==========
document.addEventListener('DOMContentLoaded', async () => {
    await loadLayout({ activePage: 'colheita' });

    const params = new URLSearchParams(window.location.search);
    const targetUid = params.get('uid') || '';

    // Fetch farm data from API
    const apiUrl = targetUid ? `/api/colheita/farm/${targetUid}` : '/api/colheita/farm/me';
    let meResp;
    try {
        // If no uid, get from /api/me first
        if (!targetUid) {
            const meData = await fetch('/api/me').then(r => r.json());
            if (!meData.success || !meData.user) { window.location.href = '/index.php'; return; }
            window.location.href = `/colheita.php?uid=${meData.user.id}`;
            return;
        }
        meResp = await fetch(`/api/colheita/farm/${targetUid}`).then(r => r.json());
    } catch(err) {
        showToast('Erro ao carregar fazenda.', true);
        return;
    }

    if (!meResp.success) {
        showToast('Erro ao carregar fazenda: ' + (meResp.message || 'desconhecido'), true);
        return;
    }

    // Set globals
    isOwner = meResp.isOwner;
    profileId = meResp.profileId;
    loggedUserId = meResp.loggedUserId;
    SEEDS_CONFIG = meResp.seedsConfig;
    FERTILIZERS_CONFIG = meResp.fertilizersConfig || {};
    EXP_TABLE = meResp.expTable || {};
    MAX_LEVEL = meResp.maxLevel || 84;
    unlockedTiles = meResp.unlockedTiles || [0];
    tileReqs = meResp.tileReqs || [];
    rawFarmData = meResp.farmData;
    rawInventory = meResp.inventory;
    myLevel = meResp.level || 1;
    myXP = meResp.xp || 0;
    // Processar level-ups pendentes ao carregar
    addXP(0);
    myGold = meResp.ouro || 0;
    myKutCoin = meResp.kutcoin || 0;
    gridSize = meResp.gridSize || 24;
    lastUpdatedTs = meResp.lastUpdated;
    serverNowTs = meResp.serverNow;
    myFoto = (meResp.loggedUser && meResp.loggedUser.foto_perfil) || 'img/perfilsemfoto.jpg';

    // Set HUD pic
    document.getElementById('hud-pic').src = meResp.owner.foto_perfil || 'img/perfilsemfoto.jpg';

    // Set breadcrumb & farm title
    if (isOwner) {
        document.getElementById('breadcrumb').innerHTML = 'Início &gt; Colheita Feliz';
    } else {
        document.getElementById('breadcrumb').innerHTML = `Início &gt; <a href="colheita.php?uid=${loggedUserId}">Colheita Feliz</a> &gt; Fazenda de ${meResp.owner.nome}`;
    }

    // Build UI
    buildGrid();
    renderTopPanel();
    renderToolbar();
    renderFriendsList(meResp.friends || []);
    renderLogsList(meResp.logs || []);

    // Setup farm data
    if (rawInventory) inventory = rawInventory;
    if (rawFarmData) {
        tileData = rawFarmData;
        normalizeTileStates();
        // Progresso offline de solo (estados 2/3 → 4) — plantas já recalculadas pelo servidor
        let elapsedMs = (serverNowTs - lastUpdatedTs) * 1000;
        if (elapsedMs > 0) simulateOfflineProgress(elapsedMs);
    } else {
        for (let i = 0; i < gridSize; i++) { tileData[i] = { state: 3, timer: 0, planted: null, harvested: 0, stolenBy: [] }; }
    }

    updateHUD();
    initGameEngine();

    // Visitor sync polling
    if (!isOwner) {
        setInterval(() => {
            fetch(`/api/colheita/sync/${profileId}`).then(r => r.json()).then(data => {
                if (data.farm_data) { tileData = data.farm_data; normalizeTileStates(); simulateOfflineProgress((data.server_now - data.last_updated) * 1000); forceVisualUpdate(); }
            });
        }, 3000);
    }
});
</script>
</body>
</html>
