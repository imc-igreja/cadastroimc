<?php
/**
 * excluir.php
 *
 * Remove um ministro do banco, apaga a foto do disco
 * e exclui o PDF gerado. Redireciona para a listagem.
 *
 * @package  CarteirinhaMinisterial
 */
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: listar.php'); exit; }

$pdo = conectar();

// Busca foto para deletar o arquivo
$stmt = $pdo->prepare("SELECT foto FROM ministros WHERE id = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if ($row && $row['foto']) {
    $foto_path = UPLOAD_DIR . $row['foto'];
    if (file_exists($foto_path)) {
        unlink($foto_path);
    }
}

// Deleta PDF gerado se existir
$pdf_path = __DIR__ . '/pdfs/carteirinha_' . $id . '.pdf';
if (file_exists($pdf_path)) unlink($pdf_path);

// Remove do banco
$stmt = $pdo->prepare("DELETE FROM ministros WHERE id = :id");
$stmt->execute([':id' => $id]);

$_SESSION['msg'] = 'Ministro excluído com sucesso.';
header('Location: listar.php');
exit;
