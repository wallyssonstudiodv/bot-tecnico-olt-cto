<?php
$comandosFile = 'comandos.json';
$configFile = 'config.json';

$respostas = file_exists($comandosFile) ? json_decode(file_get_contents($comandosFile), true) : [];
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [
    'responder_usuarios' => true,
    'grupos_autorizados' => []
];

$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        $acao = $_POST['acao'];
        $chave = strtolower(trim($_POST['chave'] ?? ''));
        $valor = trim($_POST['valor'] ?? '');

        if ($acao === 'add' && $chave && $valor) {
            $respostas[$chave] = $valor;
            $feedback = 'Comando adicionado com sucesso!';
        } elseif ($acao === 'edit' && $chave && $valor) {
            $respostas[$chave] = $valor;
            $feedback = 'Comando editado com sucesso!';
        } elseif ($acao === 'delete' && $chave) {
            unset($respostas[$chave]);
            $feedback = 'Comando excluído com sucesso!';
        }

        file_put_contents($comandosFile, json_encode($respostas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    if (isset($_POST['config_update'])) {
        $config['responder_usuarios'] = isset($_POST['responder_usuarios']) ? true : false;
        $grupos = explode("\n", trim($_POST['grupos_autorizados']));
        $gruposLimpos = array_map('trim', $grupos);
        $config['grupos_autorizados'] = array_filter($gruposLimpos);
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $feedback = 'Configurações salvas com sucesso!';
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($feedback));
    exit;
}

$msgFeedback = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Painel do Bot</title>
<style>
/* (mesmo CSS que você já tem) */
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
input[type=text], textarea {
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
.feedback {
    background-color: #238636;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    font-weight: bold;
    color: #fff;
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

<?php if ($msgFeedback): ?>
<div class="feedback"><?= htmlspecialchars($msgFeedback) ?></div>
<?php endif; ?>

<h2>🧠 Adicionar Novo Comando</h2>
<form method="POST">
    <input type="hidden" name="acao" value="add" />
    <label>Comando:</label>
    <input type="text" name="chave" required />
    <label>Resposta:</label>
    <textarea name="valor" rows="3" required></textarea>
    <button type="submit">Adicionar</button>
</form>

<h2>📜 Comandos Existentes</h2>
<table>
    <tr><th>Comando</th><th>Resposta</th><th>Ações</th></tr>
    <?php foreach ($respostas as $cmd => $resp): ?>
    <tr>
        <td><?= htmlspecialchars($cmd) ?></td>
        <td><?= nl2br(htmlspecialchars($resp)) ?></td>
        <td>
            <form method="POST" style="display:inline-block;">
                <input type="hidden" name="acao" value="edit" />
                <input type="hidden" name="chave" value="<?= htmlspecialchars($cmd) ?>" />
                <textarea name="valor" rows="2" style="width:100%;"><?= htmlspecialchars($resp) ?></textarea>
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