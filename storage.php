<?php
/**
 * storage.php
 *
 * Gerencia upload de fotos para o Supabase Storage.
 * Usa a API REST do Supabase para fazer upload e obter URLs públicas.
 *
 * @package  CarteirinhaMinisterial
 */

define('SUPABASE_URL',        getenv('SUPABASE_URL')        ?: 'https://mrzfopdvijuwixovfsql.supabase.co');
define('SUPABASE_ANON_KEY',   getenv('SUPABASE_ANON_KEY')   ?: '');
define('SUPABASE_SERVICE_KEY',getenv('SUPABASE_SERVICE_KEY')?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im1yemZvcGR2aWp1d2l4b3Zmc3FsIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NjUyMjM4NiwiZXhwIjoyMDkyMDk4Mzg2fQ.JWlO9XGcSbbMlS6GU5d87i6tPd5ji4E3qqqnCbLCz0M');
define('STORAGE_BUCKET',      getenv('STORAGE_BUCKET')      ?: 'fotos');

/**
 * Faz upload de uma foto para o Supabase Storage.
 * Retorna o nome do arquivo salvo ou false em caso de erro.
 */
function uploadFotoStorage(string $tmp_path, string $filename): string|false {
    $service_key = SUPABASE_SERVICE_KEY;
    if (!$service_key) {
        // Fallback: salva localmente se não tiver chave do Supabase
        return false;
    }

    $url = SUPABASE_URL . '/storage/v1/object/' . STORAGE_BUCKET . '/' . $filename;

    $file_content = file_get_contents($tmp_path);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = match($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png'         => 'image/png',
        'webp'        => 'image/webp',
        default       => 'application/octet-stream',
    };

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => $file_content,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $service_key,
            'Content-Type: ' . $mime,
            'x-upsert: true',
        ],
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        return $filename;
    }

    return false;
}

/**
 * Retorna a URL pública de uma foto no Supabase Storage.
 */
function getUrlFoto(string $filename): string {
    if (!$filename) return '';
    // Se for URL completa, retorna direto
    if (str_starts_with($filename, 'http')) return $filename;
    // Se tiver chave do Supabase, retorna URL do storage
    if (SUPABASE_SERVICE_KEY) {
        return SUPABASE_URL . '/storage/v1/object/public/' . STORAGE_BUCKET . '/' . $filename;
    }
    // Fallback: URL local
    return 'uploads/' . $filename;
}

/**
 * Faz download de uma foto do Supabase Storage para um arquivo temporário local.
 * Necessário para o Python processar a imagem.
 * Retorna o caminho do arquivo temporário ou o caminho local.
 */
function downloadFotoParaPython(string $filename): string {
    if (!$filename) return '';

    // 1. Verifica se existe localmente primeiro
    $local = __DIR__ . '/uploads/' . $filename;
    if (file_exists($local)) return $local;

    // 2. Se não existe localmente e tem service key, tenta baixar do Supabase Storage
    if (SUPABASE_SERVICE_KEY) {
        $url = SUPABASE_URL . '/storage/v1/object/' . STORAGE_BUCKET . '/' . $filename;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . SUPABASE_SERVICE_KEY],
        ]);
        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $content) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $tmp = tempnam(sys_get_temp_dir(), 'foto_') . '.' . $ext;
            file_put_contents($tmp, $content);
            return $tmp;
        }
    }

    return '';
}
