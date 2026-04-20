<?php
/**
 * excluir.php
 *
 * Remove um ou múltiplos ministros do banco, apaga as fotos do disco
 * e exclui os PDFs gerados. Redireciona para a listagem.
 *
 * @package  CarteirinhaMinisterial
 */
require_once 'config.php';
require_once 'storage.php';

$pdo = conectar();

// Suporte a múltiplos IDs (?ids=1,2,3) ou único (?id=1)
if (!empty($_GET['ids'])) {
    $ids = array_filter(array_map('intval', explode(',', $_GET['ids'])));
} elseif (!empty($_GET['id'])) {
    $ids = [intval($_GET['id'])];
} else {
    header('Location: listar.php');
    exit;
}

if (empty($ids)) { header('Location: listar.php'); exit; }

foreach ($ids as $id) {
    // Busca foto para deletar
    $stmt = $pdo->prepare("SELECT foto FROM ministros WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if ($row && $row['foto']) {
        // Remove do storage local
        $foto_local = UPLOAD_DIR . $row['foto'];
        if (file_exists($foto_local)) @unlink($foto_local);

        // Remove do Supabase Storage
        if (SUPABASE_SERVICE_KEY) {
            $url = SUPABASE_URL . '/storage/v1/object/' . STORAGE_BUCKET . '/' . $row['foto'];
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => 'DELETE', CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . SUPABASE_SERVICE_KEY]]);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    // Deleta PDF gerado se existir
    $pdf_path = __DIR__ . '/pdfs/carteirinha_' . $id . '.pdf';
    if (file_exists($pdf_path)) @unlink($pdf_path);

    // Remove do banco
    $stmt = $pdo->prepare("DELETE FROM ministros WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

$total = count($ids);
$_SESSION['msg'] = $total === 1 ? 'Ministro excluído com sucesso.' : "$total ministros excluídos com sucesso.";
header('Location: listar.php');
exit;
