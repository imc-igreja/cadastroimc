<?php
/**
 * editar.php
 *
 * Formulário de edição de ministro.
 *
 * @package  CarteirinhaMinisterial
 */
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: listar.php'); exit; }

$pdo = conectar();
$stmt = $pdo->prepare("SELECT * FROM ministros WHERE id = :id");
$stmt->execute([':id' => $id]);
$m = $stmt->fetch();
if (!$m) { header('Location: listar.php'); exit; }

$msg  = $_SESSION['msg']  ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['msg'], $_SESSION['erro']);
session_write_close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Ministro | Carteirinha Ministerial</title>
<link rel="icon" type="image/png" href="favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
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
    --error-bg: #fdecea;
    --error-text: #9a2020;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Montserrat',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; scrollbar-gutter:stable; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

  /* Touch improvements */
  a, button, input, select { -webkit-tap-highlight-color: rgba(0,0,0,0.1); }
  input, select, textarea { font-size: 16px; } /* Previne zoom no iOS */

  .topbar { padding:24px 0 12px; text-align:center; display:flex; flex-direction:column; align-items:center; }
  .topbar-logo { width:80px; height:80px; margin-bottom:12px; display:block; }
  .topbar h1 { display:inline-block; font-size:1.1rem; font-weight:700; color:#fff; background:var(--primary); padding:10px 28px; border-radius:10px; letter-spacing:1.5px; text-transform:uppercase; }

  .container { max-width:860px; margin:0 auto; padding:30px 20px; }

  .nav-bar { display:inline-flex; gap:6px; margin-bottom:28px; border-bottom:2px solid #e0dcd6; }
  .nav-bar a { padding:10px 24px; text-decoration:none; font-weight:600; font-size:.85rem; color:var(--text-muted); border-bottom:3px solid transparent; margin-bottom:-2px; transition:all .2s; }
  .nav-bar a.active { color:var(--primary); border-bottom-color:var(--accent); }
  .nav-bar a:hover:not(.active) { color:var(--text); }

  .card { background:var(--card-bg); border-radius:10px; padding:36px 40px; border:1px solid #e5e1db; overflow:hidden; }
  .card-title { font-size:1.15rem; font-weight:700; color:var(--primary); padding:16px 40px; margin:-36px -40px 28px; border-bottom:1px solid #eee; text-align:center; }

  .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
  .form-grid .full { grid-column:1/-1; }

  .form-group { display:flex; flex-direction:column; gap:6px; }
  .form-group label { font-size:.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.6px; }
  .form-group input,
  .form-group select { padding:10px 14px; border:1px solid var(--border); border-radius:6px; font-size:.9rem; font-family:'Montserrat',sans-serif; transition:border-color .2s; background:#fafaf8; color:var(--text); appearance:none; -webkit-appearance:none; }
  .form-group select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b6b6b' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 14px center; padding-right:36px; cursor:pointer; }
  .form-group input:focus,
  .form-group select:focus { outline:none; border-color:var(--primary-light); background:#fff; }

  .foto-upload { border:1px dashed var(--border); border-radius:8px; padding:24px; text-align:center; cursor:pointer; transition:all .2s; background:#fafaf8; }
  .foto-upload:hover { background:#f5f2ed; border-color:var(--accent); }
  .foto-upload .icon { width:40px; height:40px; margin:0 auto 10px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; }
  .foto-upload .icon svg { width:20px; height:20px; fill:#fff; }
  .foto-upload p { color:var(--text-muted); font-size:.82rem; }
  .foto-upload small { color:#aaa; font-size:.72rem; }
  .foto-preview { width:100px; height:100px; border-radius:6px; object-fit:cover; border:2px solid var(--accent); margin:12px auto 0; display:block; }
  #foto_input { display:none; }

  .section-title { grid-column:1/-1; font-size:.72rem; font-weight:800; color:var(--primary); text-transform:uppercase; letter-spacing:1.5px; padding:12px 40px 4px; border-top:1px solid #eee; margin-top:8px; margin-left:-40px; margin-right:-40px; }

  .btn-submit { width:100%; padding:14px; background:var(--primary); color:#fff; font-family:'Montserrat',sans-serif; font-size:.95rem; font-weight:700; border:none; border-radius:8px; cursor:pointer; text-transform:uppercase; letter-spacing:1.5px; margin-top:12px; transition:background .2s; }
  .btn-submit:hover { background:var(--primary-light); }

  .alert { padding:12px 16px; border-radius:6px; margin-bottom:20px; font-weight:500; font-size:.88rem; }
  .alert-success { background:var(--success-bg); color:var(--success-text); border-left:4px solid var(--success-text); }
  .alert-error   { background:var(--error-bg); color:var(--error-text); border-left:4px solid var(--danger); }

  @media(max-width:600px){ .form-grid { grid-template-columns: 1fr; } .card { padding: 24px 20px; } .card-title { font-size: 1rem; padding: 12px 20px; margin: -24px -20px 20px; } .section-title { padding: 12px 20px 4px; margin-left: -20px; margin-right: -20px; } .topbar h1 { font-size: .95rem; padding: 8px 20px; } .topbar-logo { width: 60px; height: 60px; } .nav-bar { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; } .nav-bar a { white-space: nowrap; } }
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
    <h2 class="card-title">Editar Ministro</h2>

    <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($erro): ?><div class="alert alert-error"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <form action="atualizar.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $m['id'] ?>">
      <div class="form-grid">

        <div class="form-group full">
          <label>Foto</label>
          <div class="foto-upload" onclick="document.getElementById('foto_input').click()">
            <div class="icon">
              <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            <p>Clique para trocar a foto</p>
            <small>JPG, PNG — max. 5MB — deixe em branco para manter a atual</small>
            <?php if ($m['foto'] && file_exists(UPLOAD_DIR . $m['foto'])): ?>
              <img id="foto_preview" class="foto-preview" src="uploads/<?= htmlspecialchars($m['foto']) ?>" alt="Foto atual">
            <?php else: ?>
              <img id="foto_preview" class="foto-preview" alt="Preview" style="display:none">
            <?php endif; ?>
          </div>
          <input type="file" id="foto_input" name="foto" accept="image/*" onchange="previewFoto(this)">
        </div>

        <div class="section-title">Dados Pessoais</div>

        <div class="form-group full">
          <label>Nome Completo *</label>
          <input type="text" name="nome" value="<?= htmlspecialchars($m['nome']) ?>" required>
        </div>

        <div class="form-group">
          <label>RG</label>
          <input type="text" name="rg" value="<?= htmlspecialchars($m['rg'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>CPF</label>
          <input type="text" name="cpf" value="<?= htmlspecialchars($m['cpf'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Nacionalidade</label>
          <input type="text" name="nacionalidade" value="<?= htmlspecialchars($m['nacionalidade'] ?? 'Brasileira') ?>">
        </div>

        <div class="form-group">
          <label>Naturalidade (Cidade-UF)</label>
          <input type="text" name="naturalidade" value="<?= htmlspecialchars($m['naturalidade'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Estado Civil</label>
          <select name="estado_civil">
            <?php foreach(['Solteiro(a)','Casado(a)','Divorciado(a)','Viuvo(a)'] as $ec): ?>
              <option value="<?= $ec ?>" <?= ($m['estado_civil'] ?? '') === $ec ? 'selected' : '' ?>><?= $ec ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="section-title">Dados Ministeriais</div>

        <div class="form-group">
          <label>Cargo / Função *</label>
          <select name="cargo" required>
            <option value="">Selecione...</option>
            <?php foreach(['Pastor','Pastora','Evangelista','Presbitero','Diacono','Diaconisa','Missionario','Missionaria','Obreiro','Obreira','Pregador Pentecostal'] as $cargo): ?>
              <option value="<?= $cargo ?>" <?= $m['cargo'] === $cargo ? 'selected' : '' ?>><?= $cargo ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Data de Ordenação</label>
          <input type="date" name="data_ordenacao" value="<?= htmlspecialchars($m['data_ordenacao'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Validade da Carteirinha</label>
          <input type="date" name="data_validade" value="<?= htmlspecialchars($m['data_validade'] ?? '') ?>">
        </div>

      </div>

      <button type="submit" class="btn-submit">Salvar Alterações</button>
    </form>
  </div>
</div>

<script>
function previewFoto(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var img = document.getElementById('foto_preview');
      img.src = e.target.result;
      img.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
