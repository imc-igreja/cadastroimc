<?php
/**
 * atualizar.php
 *
 * Recebe o POST do formulário de edição, valida, faz upload
 * da nova foto (se enviada) e atualiza o registro no banco.
 *
 * @package  CarteirinhaMinisterial
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar.php');
    exit;
}

$id    = intval($_POST['id'] ?? 0);
$nome  = trim($_POST['nome']  ?? '');
$cargo = trim($_POST['cargo'] ?? '');

if (!$id || !$nome || !$cargo) {
    $_SESSION['erro'] = 'Preencha todos os campos obrigatórios.';
    header("Location: editar.php?id=$id");
    exit;
}

$pdo = conectar();

// Busca foto atual
$stmt = $pdo->prepare("SELECT foto FROM ministros WHERE id = :id");
$stmt->execute([':id' => $id]);
$atual = $stmt->fetch();
if (!$atual) { header('Location: listar.php'); exit; }

$foto_path = $atual['foto'];

// ── Upload de nova foto (opcional) ───────────────────────────────
if (!empty($_FILES['foto']['name'])) {
    $ext   = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allow)) {
        $_SESSION['erro'] = 'Formato de imagem inválido. Use JPG ou PNG.';
        header("Location: editar.php?id=$id");
        exit;
    }
    if ($_FILES['foto']['size'] > MAX_FILE_SIZE) {
        $_SESSION['erro'] = 'Imagem muito grande (máx. 5 MB).';
        header("Location: editar.php?id=$id");
        exit;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = uniqid('min_') . '.' . $ext;
    $destino  = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
        $_SESSION['erro'] = 'Erro ao salvar a foto.';
        header("Location: editar.php?id=$id");
        exit;
    }

    // Remove foto antiga
    if ($foto_path && file_exists(UPLOAD_DIR . $foto_path)) {
        @unlink(UPLOAD_DIR . $foto_path);
    }

    $foto_path = $filename;
}

// ── Atualizar no banco ────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        UPDATE ministros SET
            nome           = :nome,
            cargo          = :cargo,
            rg             = :rg,
            cpf            = :cpf,
            nacionalidade  = :nacionalidade,
            naturalidade   = :naturalidade,
            estado_civil   = :estado_civil,
            data_ordenacao = :data_ordenacao,
            data_validade  = :data_validade,
            foto           = :foto
        WHERE id = :id
    ");
    $stmt->execute([
        ':id'             => $id,
        ':nome'           => $nome,
        ':cargo'          => $cargo,
        ':rg'             => $_POST['rg']             ?? '',
        ':cpf'            => $_POST['cpf']             ?? '',
        ':nacionalidade'  => $_POST['nacionalidade']   ?? 'Brasileira',
        ':naturalidade'   => $_POST['naturalidade']    ?? '',
        ':estado_civil'   => $_POST['estado_civil']    ?? '',
        ':data_ordenacao' => $_POST['data_ordenacao']  ?: null,
        ':data_validade'  => $_POST['data_validade']   ?: null,
        ':foto'           => $foto_path,
    ]);

    $_SESSION['msg'] = 'Ministro atualizado com sucesso!';
    header("Location: listar.php");
    exit;
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro ao atualizar: ' . $e->getMessage();
    header("Location: editar.php?id=$id");
    exit;
}
