<?php
session_start();

// --- Configurações de sessão ---
$duracao_inatividade = 300; // 5 minutos

if (isset($_SESSION['ULTIMA_ATIVIDADE']) && (time() - $_SESSION['ULTIMA_ATIVIDADE']) > $duracao_inatividade) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit;
}

if (!isset($_SESSION['login'])) {
    header("Location: login.html");
    exit;
}

$_SESSION['ULTIMA_ATIVIDADE'] = time();

// --- Configurações do banco ---
include('dbintegration.php');
$host = "localhost";
$username = "root";
$password = "";
$database = "auto";

$db = new DBconect($host, $username, $password, $database);
$conexao = $db->getConnection();

// --- Validação da tabela ---
$tabelasValidas = ['tbcliente', 'tbveiculo', 'tbmarca', 'tblocacao'];
$tabela = $_GET['tabela'] ?? 'tbcliente';

if (!in_array($tabela, $tabelasValidas)) {
    die("Tabela inválida.");
}

// --- Obter estrutura da tabela ---
$campos = [];
$sql_describe = "DESCRIBE `$tabela`";
if (!$result = $conexao->query($sql_describe)) {
    die("Erro ao descrever a tabela '$tabela': " . $conexao->error);
}
while ($row = $result->fetch_assoc()) {
    $campos[] = $row;
}

// --- Identificar chave primária e auto_increment ---
$chave_primaria = '';
$pk_auto_increment = false;

$sql_pk = "SHOW KEYS FROM `$tabela` WHERE Key_name = 'PRIMARY'";
if (!$res_pk = $conexao->query($sql_pk)) {
    die("Erro ao buscar chave primária: " . $conexao->error);
}
if ($row_pk = $res_pk->fetch_assoc()) {
    $chave_primaria = $row_pk['Column_name'];

    $sql_col = "SHOW COLUMNS FROM `$tabela` WHERE Field = '$chave_primaria'";
    if (!$res_col = $conexao->query($sql_col)) {
        die("Erro ao verificar se é auto_increment: " . $conexao->error);
    }
    if ($colinfo = $res_col->fetch_assoc()) {
        if (strpos($colinfo['Extra'], 'auto_increment') !== false) {
            $pk_auto_increment = true;
        }
    }
} else {
    die("Chave primária não encontrada para a tabela '$tabela'.");
}

// --- Buscar dados para exibir ---
$dados = [];
$sql_dados = "SELECT * FROM `$tabela`";
if (!$result2 = $conexao->query($sql_dados)) {
    die("Erro ao buscar dados da tabela '$tabela': " . $conexao->error);
}
while ($row2 = $result2->fetch_assoc()) {
    $dados[] = $row2;
}

// --- Buscar chaves estrangeiras ---
$fks = [];
$sql_fk = "
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$tabela' AND REFERENCED_TABLE_NAME IS NOT NULL
";
if (!$res_fk = $conexao->query($sql_fk)) {
    die("Erro ao buscar chaves estrangeiras: " . $conexao->error);
}
while ($row = $res_fk->fetch_assoc()) {
    $fks[$row['COLUMN_NAME']] = [
        'tabela_ref' => $row['REFERENCED_TABLE_NAME'],
        'coluna_ref' => $row['REFERENCED_COLUMN_NAME']
    ];
}

// --- Lógica de edição ---
$modoEdicao = ($_GET['acao'] ?? '') === 'editar';
$idEdicao = $_GET['id'] ?? null;
$registroEdicao = [];

if ($modoEdicao && $idEdicao) {
    $idEdicaoEscapado = $conexao->real_escape_string($idEdicao);
    $sql_edicao = "SELECT * FROM `$tabela` WHERE `$chave_primaria` = '$idEdicaoEscapado'";
    if (!$res = $conexao->query($sql_edicao)) {
        die("Erro ao buscar registro para edição: " . $conexao->error);
    }
    $registroEdicao = $res->fetch_assoc() ?? [];
}

$nometb = [
    "tbcliente" => "Cliente",
    "tblocacao" => "Locação",
    "tbmarca" => "Marca",
    "tbveiculo" => "Veículo"
];
$nomeTabela = $nometb[$tabela] ?? 'Desconhecida';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Autolocadora - <?= htmlspecialchars($nomeTabela) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="?tabela=tbcliente">Clientes</a>
    <a href="?tabela=tbveiculo">Veículos</a>
    <a href="?tabela=tbmarca">Marcas</a>
    <a href="?tabela=tblocacao">Locações</a>
    <span style="float:right;">Usuário: <?= htmlspecialchars($_SESSION['nome']); ?> | <a href="logout.php">Sair</a></span>
</nav>

<h1>Gerenciar <?= htmlspecialchars($nomeTabela) ?></h1>

<h2><?= $modoEdicao ? 'Editar Registro' : 'Inserir Novo Registro' ?></h2>
<form method="post" action="crud.php">
    <input type="hidden" name="tabela" value="<?= htmlspecialchars($tabela) ?>">
    <input type="hidden" name="acao" value="<?= $modoEdicao ? 'editar' : 'inserir' ?>">
    <?php if ($modoEdicao): ?>
        <input type="hidden" name="<?= htmlspecialchars($chave_primaria) ?>" value="<?= htmlspecialchars($registroEdicao[$chave_primaria]) ?>">
    <?php endif; ?>

    <?php foreach ($campos as $campo):
        $nomeCampo = $campo['Field'];
        if ($pk_auto_increment && $nomeCampo == $chave_primaria && !$modoEdicao) continue;

        $valorCampo = $registroEdicao[$nomeCampo] ?? '';
    ?>
        <label><?= htmlspecialchars($nomeCampo) ?>:</label>

        <?php if (isset($fks[$nomeCampo])):
            $tabela_ref = $fks[$nomeCampo]['tabela_ref'];
            $coluna_ref = $fks[$nomeCampo]['coluna_ref'];

            if (!in_array($tabela_ref, $tabelasValidas)) {
                die("Tabela referenciada inválida: $tabela_ref");
            }

            $sql_col_ref = "SHOW COLUMNS FROM `$tabela_ref`";
            if (!$res_col = $conexao->query($sql_col_ref)) {
                die("Erro ao buscar colunas de $tabela_ref: " . $conexao->error);
            }

            $cols_ref = [];
            while ($col = $res_col->fetch_assoc()) {
                $cols_ref[] = $col['Field'];
            }

            $label_ref = $cols_ref[1] ?? $coluna_ref;

            $sql_op = "SELECT `$coluna_ref`, `$label_ref` FROM `$tabela_ref`";
            if (!$res_op = $conexao->query($sql_op)) {
                die("Erro ao buscar dados de $tabela_ref: " . $conexao->error);
            }
        ?>
            <select name="<?= htmlspecialchars($nomeCampo) ?>">
                <option value="">Selecione</option>
                <?php while ($opt = $res_op->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($opt[$coluna_ref]) ?>"
                        <?= $opt[$coluna_ref] == $valorCampo ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt[$label_ref]) ?>
                    </option>
                <?php endwhile; ?>
            </select><br>
        <?php else: ?>
            <input type="text" name="<?= htmlspecialchars($nomeCampo) ?>" value="<?= htmlspecialchars($valorCampo) ?>"><br>
        <?php endif; ?>
    <?php endforeach; ?>

    <button type="submit"><?= $modoEdicao ? 'Salvar Alterações' : 'Inserir' ?></button>
    <?php if ($modoEdicao): ?>
        <a href="index.php?tabela=<?= htmlspecialchars($tabela) ?>">Cancelar</a>
    <?php endif; ?>
</form>

<h2>Registros Cadastrados</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <?php foreach ($campos as $campo): ?>
            <th><?= htmlspecialchars($campo['Field']) ?></th>
        <?php endforeach; ?>
        <th>Ações</th>
    </tr>
    <?php foreach ($dados as $linha): ?>
        <tr>
            <?php foreach ($campos as $campo): ?>
                <td><?= htmlspecialchars($linha[$campo['Field']]) ?></td>
            <?php endforeach; ?>
            <td>
                <form method="post" action="crud.php" style="display:inline;">
                    <input type="hidden" name="tabela" value="<?= htmlspecialchars($tabela) ?>">
                    <input type="hidden" name="acao" value="deletar">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($linha[$chave_primaria]) ?>">
                    <button type="submit" onclick="return confirm('Confirmar exclusão?')">Deletar</button>
                </form>
                <form method="get" action="index.php" style="display:inline;">
                    <input type="hidden" name="tabela" value="<?= htmlspecialchars($tabela) ?>">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($linha[$chave_primaria]) ?>">
                    <button type="submit">Editar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php
$db->closeConnection();
?>
</body>
</html>
