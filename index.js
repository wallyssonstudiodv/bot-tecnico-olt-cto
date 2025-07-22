<?php
header('Content-Type: application/json');

$comandosFile = 'comandos.json';

$respostas = file_exists($comandosFile) ? json_decode(file_get_contents($comandosFile), true) : [];

$input = json_decode(file_get_contents('php://input'), true);
$number = $input['number'] ?? '';
$message = strtolower(trim($input['message'] ?? ''));

$respostaCompleta = $respostas[$message] ?? null;

if (is_array($respostaCompleta)) {
    $resposta = ['reply' => $respostaCompleta['mensagem'] ?? ''];
    
    if (!empty($respostaCompleta['arquivo']) && file_exists($respostaCompleta['arquivo'])) {
        $conteudo = file_get_contents($respostaCompleta['arquivo']);
        $base64 = base64_encode($conteudo);
        $resposta['file_base64'] = $base64;
        $resposta['filename'] = basename($respostaCompleta['arquivo']);
    }

    echo json_encode($resposta);
} else {
    echo json_encode(['reply' => 'IAE MAJO. Digite "Menu" para ver as opções.']);
}
?>