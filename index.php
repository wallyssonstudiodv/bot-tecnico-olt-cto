<?php
$comandosFile = 'comandos.json';
$configFile = 'config.json';

$respostas = file_exists($comandosFile) ? json_decode(file_get_contents($comandosFile), true) : [];
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [
    'responder_usuarios' => true,
    'grupos_autorizados' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        $acao = $_POST['acao'];
        $chave = strtolower(trim($_POST['chave'] ?? ''));
        $mensagem = trim($_POST['mensagem'] ?? '');
        $arquivo_link = trim($_POST['arquivo'] ?? '');

        // Upload do arquivo
        $arquivo_final = $arquivo_link;
        if (!empty($_FILES['arquivo_upload']['name'])) {
            $nome_arquivo = basename($_FILES['arquivo_upload']['name']);
            $caminho = 'uploads/' . $nome_arquivo;
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            move_uploaded_file($_FILES['arquivo_upload']['tmp_name'], $caminho);
            $arquivo_final = $caminho;
        }

        if ($acao === 'add' && $chave && $mensagem) {
            $respostas[$chave] = [
                'mensagem' => $mensagem,
                'arquivo' => $arquivo_final
            ];
        } elseif ($acao === 'edit' && $chave && $mensagem) {
            $respostas[$chave] = [
                'mensagem' => $mensagem,
                'arquivo' => $arquivo_final
            ];
        } elseif ($acao === 'delete' && $chave) {
            unset($respostas[$chave]);
        }

        file_put_contents($comandosFile, json_encode($respostas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    if (isset($_POST['config_update'])) {
        $config['responder_usuarios'] = isset($_POST['responder_usuarios']) ? true : false;
        $grupos = explode("\n", trim($_POST['grupos_autorizados']));
        $gruposLimpos = array_map('trim', $grupos);
        $config['grupos_autorizados'] = array_filter($gruposLimpos);
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Painel do Bot</title>
<style>
    body {
        background-color: #0d1117;
        color: #c9d1d9;
        font-family: Arial, sans-serif;
        padding: 15px;
        margin: 0;
    }
    h1, h2 {
        color: #58a6ff;
    }
    label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    input[type=text], textarea, input[type=file] {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        background-color: #161b22;
        border: 1px solid #30363d;
        color: #c9d1d9;
        border-radius: 5px;
    }
    button {
        background-color: #238636;
        color: #fff;
        border: none;
        padding: 10px 16px;
        cursor: pointer;
        border-radius: 5px;
        margin-top: 5px;
    }
    button:hover {
        background-color: #2ea043;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        background-color: #161b22;
    }
    th, td {
        border: 1px solid #30363d;
        padding: 10px;
        text-align: left;
    }
    th {
        background-color: #21262d;
        color: #58a6ff;
    }
    .danger {
        background-color: #da3633;
        margin-left: 5px;
    }
    .danger:hover {
        background-color: #f85149;
    }
    @media (max-width: 600px) {
        table, thead, tbody, th, td, tr {
            display: block;
            width: 100%;
        }
        td, th {
            box-sizing: border-box;
        }
        td {
            margin-bottom: 10px;
        }
    }
</style>
</head>
<body>

<h1>Painel de Administração do Bot</h1>

<h2>🧠 Adicionar Novo Comando</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="acao" value="add" />
    <label>Comando:</label>
    <input type="text" name="chave" required />
    <label>Mensagem de resposta:</label>
    <textarea name="mensagem" rows="3" required></textarea>
    <label>Link de arquivo (opcional, pode deixar vazio se fizer upload):</label>
    <input type="text" name="arquivo" />
    <label>Ou envie um arquivo:</label>
    <input type="file" name="arquivo_upload" />
    <button type="submit">Adicionar</button>
</form>

<h2>📜 Comandos Existentes</h2>
<table>
    <tr>
        <th>Comando</th>
        <th>Mensagem</th>
        <th>Arquivo</th>
        <th>Ações</th>
    </tr>
    <?php foreach ($respostas as $cmd => $dados): ?>
    <tr>
        <td><?= htmlspecialchars($cmd) ?></td>
        <td><?= nl2br(htmlspecialchars($dados['mensagem'] ?? '')) ?></td>
        <td><?= htmlspecialchars($dados['arquivo'] ?? '') ?></td>
        <td>
            <form method="POST" enctype="multipart/form-data" style="display:inline-block;">
                <input type="hidden" name="acao" value="edit" />
                <input type="hidden" name="chave" value="<?= htmlspecialchars($cmd) ?>" />
                <textarea name="mensagem" rows="2" style="width:100%;"><?= htmlspecialchars($dados['mensagem'] ?? '') ?></textarea>
                <input type="text" name="arquivo" value="<?= htmlspecialchars($dados['arquivo'] ?? '') ?>" placeholder="Link de arquivo" style="width:100%;" />
                <input type="file" name="arquivo_upload" />
                <button type="submit">Salvar</button>
            </form>
            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Excluir comando?')">
                <input type="hidden" name="acao" value="delete" />
                <input type="hidden" name="chave" value="<?= htmlspecialchars($cmd) ?>" />
                <button type="submit" class="danger">Excluir</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<h2>🔒 Configurações de Resposta</h2>
<form method="POST">
    <input type="hidden" name="config_update" value="1" />
    <label>
        <input type="checkbox" name="responder_usuarios" <?= $config['responder_usuarios'] ? 'checked' : '' ?> />
        Responder usuários no privado
    </label><br /><br />

    <label>Grupos autorizados (1 por linha):</label>
    <textarea name="grupos_autorizados" rows="5"><?= implode("\n", $config['grupos_autorizados']) ?></textarea><br />

    <button type="submit">Salvar Configurações</button>
</form>

</body>
</html>