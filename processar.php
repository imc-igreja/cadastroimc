<?php
/**
 * processar.php
 *
 * Recebe o POST do formulario de cadastro, valida os campos,
 * faz upload da foto e persiste o registro no Supabase.
 * Ao concluir, redireciona para a geracao do PDF.
 *
 * @package  CarteirinhaMinisterial
 */
require_once 'config.php';
require_once 'storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── Validação básica ──────────────────────────────────────────────
$nome   = trim($_POST['nome']   ?? '');
$cargo  = trim($_POST['cargo']  ?? '');

if (!$nome || !$cargo) {
    $_SESSION['erro'] = 'Preencha todos os campos obrigatórios (Nome, Cargo).';
    header('Location: index.php');
    exit;
}

// ── Upload de foto ────────────────────────────────────────────────
$foto_path = '';
if (!empty($_FILES['foto']['name'])) {
    $ext   = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','webp','avif'];

    if (!in_array($ext, $allow)) {
        $_SESSION['erro'] = 'Formato de imagem inválido. Use JPG ou PNG.';
        header('Location: index.php');
        exit;
    }
    if ($_FILES['foto']['size'] > MAX_FILE_SIZE) {
        $_SESSION['erro'] = 'Imagem muito grande (máx. 5 MB).';
        header('Location: index.php');
        exit;
    }

    $filename = uniqid('min_') . '.' . $ext;

    // Tenta salvar no Supabase Storage primeiro
    $resultado = uploadFotoStorage($_FILES['foto']['tmp_name'], $filename);

    if ($resultado === false) {
        // Fallback: salva localmente
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        $destino = UPLOAD_DIR . $filename;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $_SESSION['erro'] = 'Erro ao salvar a foto.';
            header('Location: index.php');
            exit;
        }
    }

    $foto_path = $filename;
}

// ── Salvar no banco ───────────────────────────────────────────────
$pdo = conectar();

$rg              = $_POST['rg']              ?? '';
$cpf             = $_POST['cpf']             ?? '';
$nacionalidade   = $_POST['nacionalidade']   ?? 'Brasileira';
$naturalidade    = $_POST['naturalidade']    ?? '';
$estado_civil    = $_POST['estado_civil']    ?? '';
$data_ordenacao  = $_POST['data_ordenacao']  ?: date('Y-m-d');
$data_validade   = $_POST['data_validade']   ?: null;

// Gerar numero de registro automaticamente (proximo sequencial)
$ultimo = $pdo->query("SELECT COALESCE(MAX(CAST(registro AS INTEGER)), 0) AS ultimo FROM ministros")->fetch();
$registro = str_pad(($ultimo['ultimo'] + 1), 4, '0', STR_PAD_LEFT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO ministros
            (nome, cargo, registro, rg, cpf, nacionalidade, naturalidade,
             estado_civil, data_ordenacao, data_validade, foto)
        VALUES (:nome, :cargo, :registro, :rg, :cpf, :nacionalidade, :naturalidade,
                :estado_civil, :data_ordenacao, :data_validade, :foto)
    ");
    $stmt->execute([
        ':nome'           => $nome,
        ':cargo'          => $cargo,
        ':registro'       => $registro,
        ':rg'             => $rg,
        ':cpf'            => $cpf,
        ':nacionalidade'  => $nacionalidade,
        ':naturalidade'   => $naturalidade,
        ':estado_civil'   => $estado_civil,
        ':data_ordenacao' => $data_ordenacao,
        ':data_validade'  => $data_validade,
        ':foto'           => $foto_path,
    ]);

    $id = $pdo->lastInsertId();

    // Redireciona para geração do PDF
    header("Location: gerar_pdf.php?id=$id");
    exit;
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro ao salvar: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}
