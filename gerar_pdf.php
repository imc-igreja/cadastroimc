<?php
/**
 * gerar_pdf.php
 *
 * Busca os dados do ministro no Supabase, monta o payload JSON
 * e invoca o script Python (gerar_carteirinha.py) para produzir
 * o PDF da carteirinha. Serve o arquivo inline no navegador.
 *
 * @package  CarteirinhaMinisterial
 */
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: listar.php'); exit; }

$pdo = conectar();
$stmt = $pdo->prepare("SELECT * FROM ministros WHERE id = :id");
$stmt->execute([':id' => $id]);
$ministro = $stmt->fetch();

if (!$ministro) { header('Location: listar.php'); exit; }

// Passa os dados para o script Python via JSON
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
          . '://' . $_SERVER['HTTP_HOST']
          . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$dados = json_encode([
    'id'             => $ministro['id'],
    'nome'           => $ministro['nome'],
    'cargo'          => $ministro['cargo'],
    'registro'       => $ministro['registro'],
    'rg'             => $ministro['rg'],
    'cpf'            => $ministro['cpf'],
    'nacionalidade'  => $ministro['nacionalidade'],
    'naturalidade'   => $ministro['naturalidade'],
    'estado_civil'   => $ministro['estado_civil'],
    'data_ordenacao' => $ministro['data_ordenacao'],
    'data_validade'  => $ministro['data_validade'],
    'foto'           => $ministro['foto'] ? __DIR__ . '/uploads/' . $ministro['foto'] : '',
    'saida'          => __DIR__ . '/pdfs/carteirinha_' . $ministro['id'] . '.pdf',
    'igreja_nome'    => IGREJA_NOME,
    'igreja_end'     => IGREJA_ENDERECO,
    'igreja_cnpj'    => IGREJA_CNPJ,
    'presidente'     => PRESIDENTE_NOME,
    'pres_cargo'     => PRESIDENTE_CARGO,
    'url_carteirinha'=> $base_url . '/gerar_pdf.php?id=' . $ministro['id'],
], JSON_UNESCAPED_UNICODE);

if (!is_dir(__DIR__ . '/pdfs')) {
    mkdir(__DIR__ . '/pdfs', 0755, true);
}

// Salva JSON em arquivo temporário para evitar problemas de escape no Windows
$tmp = tempnam(sys_get_temp_dir(), 'cmc_') . '.json';
file_put_contents($tmp, $dados);

$script = __DIR__ . '/gerar_carteirinha.py';
$python = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'python' : 'python3';
$saida_cmd = shell_exec("\"$python\" \"$script\" \"$tmp\" 2>&1");

// Remove arquivo temporário
@unlink($tmp);

$pdf_path = __DIR__ . '/pdfs/carteirinha_' . $id . '.pdf';

if (file_exists($pdf_path)) {
    // Serve o PDF direto no browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="carteirinha_' . $id . '.pdf"');
    header('Content-Length: ' . filesize($pdf_path));
    readfile($pdf_path);
    exit;
} else {
    echo "<h2>Erro ao gerar PDF</h2><pre>$saida_cmd</pre>";
    echo '<a href="listar.php">← Voltar</a>';
}
