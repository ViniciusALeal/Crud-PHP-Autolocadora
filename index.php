<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include('dbintegration.php');

$host = "localhost";
$username = "root";
$password = "";
$database = "autobahn";

$db = new DBconect($host, $username, $password, $database);
$conexao = $db->getConnection();

$tabela = $_GET['tabela'] ?? 'tbcliente';

// Obter estrutura da tabela
$campos = [];
$result = $conexao->query("DESCRIBE $tabela");
while ($row = $result->fetch_assoc()) {
    $campos[] = $row;
}

// Chave primária e auto_increment
$chave_primaria = '';
$pk_auto_increment = false;

$res_pk = $conexao->query("SHOW KEYS FROM $tabela WHERE Key_name = 'PRIMARY'");
if ($res_pk && $row_pk = $res_pk->fetch_assoc()) {
    $chave_primaria = $row_pk['Column_name'];

    $res_col = $conexao->query("SHOW COLUMNS FROM $tabela WHERE Field = '$chave_primaria'");
    if ($res_col && $colinfo = $res_col->fetch_assoc()) {
        if (strpos($colinfo['Extra'], 'auto_increment') !== false) {
            $pk_auto_increment = true;
        }
    }
}

// Dados existentes
$dados = [];
$result2 = $conexao->query("SELECT * FROM $tabela");
while ($row2 = $result2->fetch_assoc()) {
    $dados[] = $row2;
}

// FK
$fks = [];
$sql_fk = "
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$tabela' AND REFERENCED_TABLE_NAME IS NOT NULL
";
$res_fk = $conexao->query($sql_fk);
while ($row = $res_fk->fetch_assoc()) {
    $fks[$row['COLUMN_NAME']] = [
        'tabela_ref' => $row['REFERENCED_TABLE_NAME'],
        'coluna_ref' => $row['REFERENCED_COLUMN_NAME']
    ];
}

// Modo edição
$modoEdicao = ($_GET['acao'] ?? '') === 'editar';
$idEdicao = $_GET['id'] ?? null;
$registroEdicao = [];

if ($modoEdicao && $idEdicao) {
    $idEdicaoEscapado = $conexao->real_escape_string($idEdicao);
    $res = $conexao->query("SELECT * FROM $tabela WHERE $chave_primaria = '$idEdicaoEscapado'");
    $registroEdicao = $res->fetch_assoc() ?? [];
}

// Nome amigável
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

                $res_col = $conexao->query("SHOW COLUMNS FROM $tabela_ref");
                $cols_ref = [];
                while ($col = $res_col->fetch_assoc()) {
                    $cols_ref[] = $col['Field'];
                }
                $label_ref = $cols_ref[1] ?? $coluna_ref;

                $res_op = $conexao->query("SELECT `$coluna_ref`, `$label_ref` FROM `$tabela_ref`");
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
    <table>
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
</body>
</html>

<?php
$db->closeConnection();
?>
