<?php
/**
 * listar.php
 *
 * Exibe a lista de todos os ministros cadastrados com busca
 * em tempo real, acoes de geracao de PDF e exclusao.
 *
 * @package  CarteirinhaMinisterial
 */
require_once 'config.php';

$pdo = conectar();
$result = $pdo->query("SELECT * FROM ministros ORDER BY created_at DESC");
$ministros = $result->fetchAll();

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
session_write_close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ministros | Carteirinha Ministerial</title>
<link rel="icon" type="image/png" href="favicon.png">
<link rel="prefetch" href="index.php">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --primary: #1b3a5c;
    --primary-light: #2a5a8a;
    --accent: #b8860b;
    --bg: #f4f1ec;
    --card-bg: #ffffff;
    --text: #2c2c2c;
    --text-muted: #6b6b6b;
    --border: #ddd;
    --danger: #a83232;
    --success-bg: #e8f5e9;
    --success-text: #2e7d32;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family: 'Montserrat', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    scrollbar-gutter: stable;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
  }

  /* Touch improvements */
  a, button, input, select { -webkit-tap-highlight-color: rgba(0,0,0,0.1); }
  input, select, textarea { font-size: 16px; } /* Previne zoom no iOS */

  .topbar {
    padding: 24px 0 12px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .topbar-logo {
    width: 80px;
    height: 80px;
    margin-bottom: 12px;
    display: block;
  }
  .topbar h1 {
    display: inline-block;
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    background: var(--primary);
    padding: 10px 28px;
    border-radius: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
  }

  .container { max-width: 1000px; margin: 0 auto; padding: 30px 20px; }

  .nav-bar {
    display: inline-flex;
    gap: 6px;
    margin-bottom: 28px;
    border-bottom: 2px solid #e0dcd6;
  }
  .nav-bar a {
    padding: 10px 24px;
    text-decoration: none;
    font-weight: 600;
    font-size: .85rem;
    color: var(--text-muted);
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all .2s;
  }
  .nav-bar a.active {
    color: var(--primary);
    border-bottom-color: var(--accent);
  }
  .nav-bar a:hover:not(.active) { color: var(--text); }

  .card {
    background: var(--card-bg);
    border-radius: 10px;
    padding: 30px;
    border: 1px solid #e5e1db;
    overflow: hidden;
  }
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 30px 16px;
    margin: -30px -30px 20px;
    border-bottom: 1px solid #eee;
    flex-wrap: wrap;
    gap: 12px;
  }
  .card-header h2 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
  }
  .card-header .count {
    font-size: .8rem;
    color: var(--text-muted);
    font-weight: 500;
  }

  .search-box {
    position: relative;
    display: inline-flex;
    align-items: center;
  }
  .search-box svg {
    position: absolute;
    left: 10px;
    color: var(--text-muted);
    pointer-events: none;
    width: 16px;
    height: 16px;
  }
  .search-box input {
    padding: 8px 16px 8px 34px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: .85rem;
    font-family: 'Montserrat', sans-serif;
    transition: border-color .2s;
    width: 220px;
  }
  .search-box input:focus { outline: none; border-color: var(--primary-light); }

  .alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
    font-size: .88rem;
    background: var(--success-bg);
    color: var(--success-text);
    border-left: 4px solid var(--success-text);
  }

  table { width: calc(100% + 60px); margin-left: -30px; margin-right: -30px; border-collapse: collapse; font-size: .85rem; }
  th {
    background: var(--primary);
    color: #fff;
    padding: 11px 14px;
    text-align: left;
    font-weight: 600;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .6px;
  }
  th:first-child { padding-left: 30px; }
  th:last-child  { padding-right: 30px; }
  td {
    padding: 10px 14px;
    border-bottom: 1px solid #f0ede8;
    vertical-align: middle;
  }
  td:first-child { padding-left: 30px; }
  td:last-child  { padding-right: 30px; }
  tr:hover td { background: #faf8f4; }

  .foto-thumb {
    width: 40px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #ddd;
  }
  .no-foto {
    width: 40px;
    height: 50px;
    background: #eee;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .no-foto svg { width: 18px; height: 18px; fill: #bbb; }

  .badge {
    padding: 3px 10px;
    border-radius: 4px;
    font-size: .72rem;
    font-weight: 600;
  }
  .badge-pastor   { background: #fef3cd; color: #7a6312; }
  .badge-diacono  { background: #d1ecf1; color: #0c5460; }
  .badge-obreiro  { background: #d4edda; color: #155724; }
  .badge-default  { background: #e8e4f0; color: #5a3d8a; }

  .btn {
    padding: 6px 12px;
    border-radius: 4px;
    font-weight: 600;
    font-size: .78rem;
    font-family: 'Montserrat', sans-serif;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all .15s;
    cursor: pointer;
    border: none;
    vertical-align: middle;
  }
  .btn-pdf { background: var(--primary); color: #fff; }
  .btn-edit { background: #e8f0fb; color: #1b3a5c; }
  .btn-del { background: #f5e6e6; color: var(--danger); }
  .btn-pdf:hover  { background: var(--primary-light); }
  .btn-edit:hover { background: #d0e0f5; }
  .btn-del:hover  { background: #f0d0d0; }

  .empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
  .empty p { font-size: .95rem; margin-bottom: 16px; }

  /* Modal de confirmação */
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 1000;
    align-items: center;
    justify-content: center;
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: #fff;
    border-radius: 12px;
    padding: 24px 28px;
    max-width: 360px;
    width: 90%;
    box-shadow: 0 8px 32px rgba(0,0,0,.18);
    text-align: left;
    animation: modalIn .18s ease;
  }
  @keyframes modalIn {
    from { transform: scale(.92); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
  }
  .modal h3 { font-size: 1.05rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
  .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
  .modal-actions .btn { min-width: 100px; justify-content: center; padding: 9px 16px; font-size: .85rem; }
  .btn-cancel { background: #f0ede8; color: var(--text); }
  .btn-cancel:hover { background: #e5e1db; }

  /* Checkbox */
  .cb-select { width: 16px; height: 16px; cursor: pointer; accent-color: var(--danger); }

  /* Barra de seleção */
  .sel-bar {
    display: none;
    align-items: center;
    gap: 12px;
    padding: 10px 18px;
    background: #fdecea;
    border-bottom: 1px solid #f5c6c6;
    font-size: .85rem;
    font-weight: 600;
    color: var(--danger);
  }
  .sel-bar.show { display: flex; }
  .sel-bar span { flex: 1; }

  @media(max-width:700px){
    table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .card { padding: 18px; }
    .card-header { flex-direction: column; align-items: flex-start; padding: 12px 18px; margin: -18px -18px 16px; }
    .card-header > div { width: 100%; }
    .search-box { width: 100%; margin-bottom: 8px; }
    .search-box input { width: 100%; }
    .topbar h1 { font-size: .95rem; padding: 8px 20px; }
    .topbar-logo { width: 60px; height: 60px; }
    .nav-bar { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .nav-bar a { white-space: nowrap; }
    th, td { font-size: .75rem; padding: 8px 10px; }
    th:first-child, td:first-child { padding-left: 18px; }
    th:last-child, td:last-child { padding-right: 18px; }
    .btn { font-size: .72rem; padding: 5px 10px; }
    .foto-thumb, .no-foto { width: 32px; height: 40px; }
    .badge { font-size: .68rem; padding: 2px 8px; }
    .modal { max-width: 90%; padding: 20px; }
    .modal h3 { font-size: .95rem; }
    .modal-actions { flex-direction: column; }
    .modal-actions .btn { width: 100%; }
  }
</style>
</head>
<body>

<div class="topbar">
  <img src="favicon.png" alt="Logo" class="topbar-logo">
  <h1>Carteirinha Ministerial</h1>
</div>

<div class="container">
  <nav class="nav-bar">
    <a href="index.php">Cadastrar</a>
    <a href="listar.php" class="active">Ministros</a>
  </nav>

  <div class="card">
    <div class="card-header">
      <div class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11.5a7.5 7.5 0 1 1-15 0a7.5 7.5 0 0 1 15 0m-2.107 5.42l3.08 3.08"/></svg>
        <input type="text" id="busca" placeholder="Buscar..." oninput="filtrar()">
      </div>
      <div style="text-align:right">
        <h2>Ministros Cadastrados</h2>
        <span class="count"><?= count($ministros) ?> registro(s)</span>
      </div>
    </div>

    <?php if($msg): ?><div class="alert"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <?php if (empty($ministros)): ?>
      <div class="empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128"><path fill="#b9e4ea" d="M4 93.33V34.67c0-4.68 3.83-8.5 8.5-8.5h103c4.68 0 8.5 3.83 8.5 8.5v58.67c0 4.68-3.83 8.5-8.5 8.5h-103c-4.67 0-8.5-3.83-8.5-8.51"/><path fill="#fff" d="M42.43 89.13H15.19c-.85 0-1.54-.69-1.54-1.54V50.57c0-.85.69-1.54 1.54-1.54h27.24c.85 0 1.54.69 1.54 1.54V87.6c0 .84-.69 1.53-1.54 1.53"/><linearGradient id="SVGELktKcSs" x1="28.813" x2="28.813" y1="74.347" y2="38.979" gradientTransform="matrix(1 0 0 -1 0 128.11)" gradientUnits="userSpaceOnUse"><stop offset=".153" stop-color="#1e88e5"/><stop offset="1" stop-color="#1565c0"/></linearGradient><path fill="url(#SVGELktKcSs)" d="M43.87 87.95v1.18H13.75v-1.18c0-4.63 5.87-6.87 12.38-7.34v-.32c-2.74-.83-5.19-2.89-6.4-6.28c-1.24-.44-1.95-4.51-1.62-5.04c-.24-1.71-1.86-15.12 10.7-15.21c12.53.06 10.93 13.44 10.67 15.18c.32.53-.38 4.6-1.62 5.04c-1.18 3.39-3.63 5.48-6.37 6.31v.29c6.55.53 12.38 3.01 12.38 7.37"/><path fill="#2f7889" d="M115.5 26.16h-103c-4.68 0-8.5 3.83-8.5 8.5v5.33h120v-5.33c0-4.67-3.83-8.5-8.5-8.5"/><path fill="#82aec0" d="M79.09 53.76H54.33c-1.31 0-2.37-1.06-2.37-2.37s1.06-2.37 2.37-2.37h24.76c1.31 0 2.37 1.06 2.37 2.37a2.38 2.38 0 0 1-2.37 2.37m8.87 11.79H54.33c-1.31 0-2.37-1.06-2.37-2.37s1.06-2.37 2.37-2.37h33.63c1.31 0 2.37 1.06 2.37 2.37a2.38 2.38 0 0 1-2.37 2.37M75.27 77.34H54.33c-1.31 0-2.37-1.06-2.37-2.37s1.06-2.37 2.37-2.37h20.94c1.31 0 2.37 1.06 2.37 2.37a2.38 2.38 0 0 1-2.37 2.37m-5.7 11.79H54.33c-1.31 0-2.37-1.06-2.37-2.37s1.06-2.37 2.37-2.37h15.24c1.31 0 2.37 1.06 2.37 2.37s-1.06 2.37-2.37 2.37"/><path fill="#94d1e0" d="M124 53.79c-.17-.01-.34-.03-.52-.03c-7.84 0-14.19 6.35-14.19 14.19s6.35 14.19 14.19 14.19c.17 0 .34-.02.52-.03z"/></svg>
        <p>Nenhum ministro cadastrado ainda.</p>
        <a href="index.php" class="btn btn-pdf">Cadastrar primeiro ministro</a>
      </div>
    <?php else: ?>

    <!-- Barra de seleção múltipla -->
    <div id="sel-bar" class="sel-bar">
      <span id="sel-count">0 selecionado(s)</span>
      <button class="btn btn-del" onclick="abrirModalMultiplo()">Excluir selecionados</button>
      <button class="btn btn-cancel" onclick="limparSelecao()">Cancelar</button>
    </div>

    <table id="tabela">
      <thead>
        <tr>
          <th><input type="checkbox" class="cb-select" id="cb-todos" onclick="toggleTodos(this)" title="Selecionar todos"></th>
          <th>Foto</th>
          <th>Nome</th>
          <th>Cargo</th>
          <th>Registro</th>
          <th>Ordenacao</th>
          <th>Validade</th>
          <th>Acoes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ministros as $m): ?>
        <?php
          $cargo_lc = strtolower($m['cargo']);
          $badge_class = 'badge-default';
          if (strpos($cargo_lc,'pastor') !== false)   $badge_class = 'badge-pastor';
          elseif (strpos($cargo_lc,'diacon') !== false) $badge_class = 'badge-diacono';
          elseif (strpos($cargo_lc,'obreiro') !== false || strpos($cargo_lc,'obreira') !== false) $badge_class = 'badge-obreiro';

          $ord = $m['data_ordenacao'] ? date('d/m/Y', strtotime($m['data_ordenacao'])) : '—';
          $val = $m['data_validade']  ? date('d/m/Y', strtotime($m['data_validade']))  : '—';
        ?>
        <tr class="linha">
          <td><input type="checkbox" class="cb-select cb-linha" value="<?= $m['id'] ?>" onclick="atualizarSelecao()"></td>
          <td>
            <?php if ($m['foto'] && file_exists(UPLOAD_DIR . $m['foto'])): ?>
              <img src="uploads/<?= htmlspecialchars($m['foto']) ?>" class="foto-thumb" alt="">
            <?php else: ?>
              <div class="no-foto">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
              </div>
            <?php endif; ?>
          </td>
          <td><strong><?= htmlspecialchars($m['nome']) ?></strong></td>
          <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($m['cargo']) ?></span></td>
          <td><?= htmlspecialchars($m['registro']) ?></td>
          <td><?= $ord ?></td>
          <td><?= $val ?></td>
          <td style="white-space:nowrap">
            <a href="editar.php?id=<?= $m['id'] ?>" class="btn btn-edit" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><mask id="SVG8dJ0oeeE" width="15" height="15" x="4" y="5" fill="#000" maskUnits="userSpaceOnUse"><path fill="#fff" d="M4 5h15v15H4z"/><path d="m13.586 7.414l-7.194 7.194c-.195.195-.292.292-.36.41c-.066.119-.1.252-.166.52l-.664 2.654c-.09.36-.135.541-.035.641s.28.055.641-.035l2.655-.664c.267-.066.4-.1.518-.167c.119-.067.216-.164.41-.359l7.195-7.194c.667-.666 1-1 1-1.414s-.333-.748-1-1.414l-.172-.172c-.667-.666-1-1-1.414-1s-.748.334-1.414 1"/></mask><g fill="none"><path stroke="currentColor" stroke-width="1" d="m13.586 7.414l-7.194 7.194c-.195.195-.292.292-.36.41c-.066.119-.1.252-.166.52l-.664 2.654c-.09.36-.135.541-.035.641s.28.055.641-.035l2.655-.664c.267-.066.4-.1.518-.167c.119-.067.216-.164.41-.359l7.195-7.194c.667-.666 1-1 1-1.414s-.333-.748-1-1.414l-.172-.172c-.667-.666-1-1-1.414-1s-.748.334-1.414 1Z" mask="url(#SVG8dJ0oeeE)"/><path fill="currentColor" d="m12.5 7.5l3-2l3 3l-2 3z"/></g></svg></a>
            <a href="gerar_pdf.php?id=<?= $m['id'] ?>" class="btn btn-pdf" target="_blank">Gerar PDF</a>
            <button class="btn btn-del" onclick="abrirModal(<?= $m['id'] ?>, '<?= addslashes($m['nome']) ?>')">Excluir</button>


        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="sem-resultado" class="empty" style="display:none;">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" style="margin-bottom:12px;color:var(--text-muted)"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"><path d="m13.5 8.5l-5 5m0-5l5 5"/><circle cx="11" cy="11" r="8"/><path d="m21 21l-4.3-4.3"/></g></svg>
      <p>Nenhum ministro encontrado.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de confirmação -->
<div id="modal-excluir" class="modal-overlay" onclick="fecharModal(event)">
  <div class="modal" onclick="event.stopPropagation()">
    <h3>Confirmar exclusão</h3>
    <p id="modal-texto" style="font-size:.88rem;color:var(--text-muted);margin-bottom:20px;margin-top:4px">Tem certeza que deseja excluir este ministro?</p>
    <div class="modal-actions">
      <button class="btn btn-cancel" onclick="fecharModal()">Cancelar</button>
      <a id="modal-confirmar" href="#" class="btn btn-del">Excluir</a>
    </div>
  </div>
</div>

<!-- Modal de confirmação exclusão múltipla -->
<div id="modal-multiplo" class="modal-overlay" onclick="fecharModalMultiplo(event)">
  <div class="modal" onclick="event.stopPropagation()">
    <h3>Confirmar exclusão</h3>
    <p id="modal-multiplo-texto" style="font-size:.88rem;color:var(--text-muted);margin-bottom:20px;margin-top:4px">Tem certeza que deseja excluir os selecionados?</p>
    <div class="modal-actions">
      <button class="btn btn-cancel" onclick="fecharModalMultiplo()">Cancelar</button>
      <button class="btn btn-del" onclick="confirmarExclusaoMultipla()">Excluir</button>
    </div>
  </div>
</div>

<script>
// ── Seleção múltipla ──────────────────────────────────────────────
function toggleTodos(cb) {
  document.querySelectorAll('.cb-linha').forEach(c => {
    if (c.closest('.linha').style.display !== 'none') c.checked = cb.checked;
  });
  atualizarSelecao();
}

function atualizarSelecao() {
  var selecionados = document.querySelectorAll('.cb-linha:checked').length;
  var bar = document.getElementById('sel-bar');
  document.getElementById('sel-count').textContent = selecionados + ' selecionado(s)';
  bar.classList.toggle('show', selecionados > 0);
  // Atualiza checkbox "todos"
  var total = document.querySelectorAll('.cb-linha:not([style*="display: none"])').length;
  document.getElementById('cb-todos').indeterminate = selecionados > 0 && selecionados < total;
  document.getElementById('cb-todos').checked = selecionados === total && total > 0;
}

function limparSelecao() {
  document.querySelectorAll('.cb-linha').forEach(c => c.checked = false);
  document.getElementById('cb-todos').checked = false;
  document.getElementById('sel-bar').classList.remove('show');
}

function abrirModalMultiplo() {
  var n = document.querySelectorAll('.cb-linha:checked').length;
  document.getElementById('modal-multiplo-texto').textContent = 'Tem certeza que deseja excluir ' + n + ' ministro(s)?';
  document.getElementById('modal-multiplo').classList.add('open');
}

function fecharModalMultiplo(event) {
  if (!event || event.target.id === 'modal-multiplo') {
    document.getElementById('modal-multiplo').classList.remove('open');
  }
}

function confirmarExclusaoMultipla() {
  var ids = Array.from(document.querySelectorAll('.cb-linha:checked')).map(c => c.value);
  if (!ids.length) return;
  window.location.href = 'excluir.php?ids=' + ids.join(',');
}

// ── Modal exclusão individual ─────────────────────────────────────
let modalIdExcluir = null;

function abrirModal(id, nome) {
  modalIdExcluir = id;
  document.getElementById('modal-texto').textContent = `Tem certeza que deseja excluir ${nome}?`;
  document.getElementById('modal-confirmar').href = `excluir.php?id=${id}`;
  document.getElementById('modal-excluir').classList.add('open');
}

function fecharModal(event) {
  if (!event || event.target.id === 'modal-excluir') {
    document.getElementById('modal-excluir').classList.remove('open');
    modalIdExcluir = null;
  }
}

function filtrar() {
  var q = document.getElementById('busca').value.toLowerCase();
  var linhas = document.querySelectorAll('.linha');
  var visiveis = 0;
  for (var i = 0; i < linhas.length; i++) {
    var visivel = linhas[i].textContent.toLowerCase().indexOf(q) > -1;
    linhas[i].style.display = visivel ? '' : 'none';
    if (visivel) visiveis++;
  }
  document.getElementById('sem-resultado').style.display = visiveis === 0 ? '' : 'none';
}
</script>
</body>
</html>



