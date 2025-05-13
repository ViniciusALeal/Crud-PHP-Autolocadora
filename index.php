<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('dbintegration.php');

$host     = "localhost";
$username = "root";
$password = "";
$database = "autobahn";

$db       = new DBconect($host, $username, $password, $database);
$conexao  = $db->getConnection();

$tabela    = $_GET['tabela'] ?? 'tbcliente';
$acao      = $_GET['acao']   ?? '';
$id_editar = $_GET['id']     ?? null;

// Estrutura da tabela
$campos = [];
$res1 = $conexao->query("DESCRIBE `$tabela`");
while ($res1 && $row = $res1->fetch_assoc()) {
    $campos[] = $row;
}

// Detecta PK e auto_increment
$chave_primaria   = '';
$pk_auto_increment = false;
$res_pk = $conexao->query("SHOW KEYS FROM `$tabela` WHERE Key_name = 'PRIMARY'");
if ($res_pk && $row_pk = $res_pk->fetch_assoc()) {
    $chave_primaria = $row_pk['Column_name'];
    $res_col = $conexao->query("SHOW COLUMNS FROM `$tabela` WHERE Field = '$chave_primaria'");
    if ($res_col && $colinfo = $res_col->fetch_assoc()) {
        $pk_auto_increment = strpos($colinfo['Extra'], 'auto_increment') !== false;
    }
}

// Dados para listar
$dados = [];
$res2 = $conexao->query("SELECT * FROM `$tabela`");
while ($res2 && $row2 = $res2->fetch_assoc()) {
    $dados[] = $row2;
}

// Carrega registro para edição
$dado_edicao = [];
if ($acao === 'editar' && $id_editar) {
    $stmt = $conexao->prepare("SELECT * FROM `$tabela` WHERE `$chave_primaria` = ?");
    $stmt->bind_param("s", $id_editar);
    $stmt->execute();
    $res_ed = $stmt->get_result();
    $dado_edicao = $res_ed ? $res_ed->fetch_assoc() : [];
    $stmt->close();
}

// Nomes amigáveis
$nometb = [
    "tbcliente" => "Cliente",
    "tblocacao" => "Locação",
    "tbmarca"   => "Marca",
    "tbveiculo" => "Veículo"
];
$nomeTabela = $nometb[$tabela] ?? 'Desconhecida';

// Detecta FKs (uso genérico nas opções de select)
$fks = [];
$sql_fk = "
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = '$database'
      AND TABLE_NAME = '$tabela'
      AND REFERENCED_TABLE_NAME IS NOT NULL
";
$res_fk = $conexao->query($sql_fk);
while ($res_fk && $row = $res_fk->fetch_assoc()) {
    $fks[$row['COLUMN_NAME']] = [
        'tabela_ref' => $row['REFERENCED_TABLE_NAME'],
        'coluna_ref' => $row['REFERENCED_COLUMN_NAME']
    ];
}

// === Disponibilidade (apenas para tbveiculo) ===
$veiculos_indisponiveis = [];
if ($tabela === 'tbveiculo') {
    // 1) Descobre qual campo em tblocacao referencia tbveiculo
    $res_fk2 = $conexao->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = '$database'
          AND TABLE_NAME = 'tblocacao'
          AND REFERENCED_TABLE_NAME = 'tbveiculo'
    ");
    if ($res_fk2 && $fk2 = $res_fk2->fetch_assoc()) {
        $col_veic_fk = $fk2['COLUMN_NAME'];
        // 2) Executa consulta segura
        $sql_dispon = "
            SELECT `$col_veic_fk`
            FROM `tblocacao`
            WHERE (`locacao_data_inicio` <= NOW() AND `locacao_data_fim` >= NOW())
              OR `locacao_data_fim` IS NULL
        ";
        $res_dispon = $conexao->query($sql_dispon);
        if ($res_dispon) {
            while ($row_disp = $res_dispon->fetch_assoc()) {
                $veiculos_indisponiveis[] = $row_disp[$col_veic_fk];
            }
        } else {
            error_log("Erro SQL disponibilidade: " . $conexao->error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Autolocadora – <?= htmlspecialchars($nomeTabela) ?></title>
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

    <h2><?= $acao === 'editar' ? 'Editar Registro' : 'Inserir Novo Registro' ?></h2>
    <form method="post" action="crud.php">
        <input type="hidden" name="tabela" value="<?= htmlspecialchars($tabela) ?>">
        <input type="hidden" name="acao"  value="<?= $acao === 'editar' ? 'editar' : 'inserir' ?>">
        <?php if ($acao === 'editar'): ?>
            <input type="hidden" name="<?= htmlspecialchars($chave_primaria) ?>"
                   value="<?= htmlspecialchars($id_editar) ?>">
        <?php endif; ?>

        <?php foreach ($campos as $campo):
            $nomeCampo = $campo['Field'];
            if ($pk_auto_increment && $nomeCampo === $chave_primaria) continue;
            $valor = $dado_edicao[$nomeCampo] ?? '';
        ?>
            <label><?= htmlspecialchars($nomeCampo) ?>:</label>
            <?php if (isset($fks[$nomeCampo])):
                $tr = $fks[$nomeCampo]['tabela_ref'];
                $cr = $fks[$nomeCampo]['coluna_ref'];
                $res_col = $conexao->query("SHOW COLUMNS FROM `$tr`");
                $labels = [];
                while ($c = $res_col->fetch_assoc()) {
                    $labels[] = $c['Field'];
                }
                $label = $labels[1] ?? $cr;
                $ops = $conexao->query("SELECT `$cr`,`$label` FROM `$tr`");
            ?>
                <select name="<?= htmlspecialchars($nomeCampo) ?>">
                    <option value="">Selecione</option>
                    <?php while ($opt = $ops->fetch_assoc()):
                        $sel = ($opt[$cr] == $valor) ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($opt[$cr]) ?>" <?= $sel ?>>
                            <?= htmlspecialchars($opt[$label]) ?>
                        </option>
                    <?php endwhile; ?>
                </select><br>
            <?php else: ?>
                <input type="text" name="<?= htmlspecialchars($nomeCampo) ?>"
                       value="<?= htmlspecialchars($valor) ?>"><br>
            <?php endif; ?>
        <?php endforeach; ?>

        <button type="submit"><?= $acao === 'editar' ? 'Salvar Alterações' : 'Inserir' ?></button>
        <?php if ($acao === 'editar'): ?>
            <a href="index.php?tabela=<?= htmlspecialchars($tabela) ?>">Cancelar</a>
        <?php endif; ?>
    </form>

    <h2>Registros Cadastrados</h2>
    <table>
        <tr>
            <?php foreach ($campos as $campo): ?>
                <th><?= htmlspecialchars($campo['Field']) ?></th>
            <?php endforeach; ?>
            <?php if ($tabela === 'tbveiculo'): ?>
                <th>Disponível?</th>
            <?php endif; ?>
            <th>Ações</th>
        </tr>
        <?php foreach ($dados as $linha): ?>
            <tr>
                <?php foreach ($campos as $campo): ?>
                    <td><?= htmlspecialchars($linha[$campo['Field']]) ?></td>
                <?php endforeach; ?>
                <?php if ($tabela === 'tbveiculo'): ?>
                    <td>
                        <?= in_array($linha[$chave_primaria], $veiculos_indisponiveis)
                            ? '❌ Não' : '✅ Sim' ?>
                    </td>
                <?php endif; ?>
                <td>
                    <form method="post" action="crud.php" style="display:inline;">
                        <input type="hidden" name="tabela" value="<?= htmlspecialchars($tabela) ?>">
                        <input type="hidden" name="acao"  value="deletar">
                        <input type="hidden" name="<?= htmlspecialchars($chave_primaria) ?>"
                               value="<?= htmlspecialchars($linha[$chave_primaria]) ?>">
                        <button type="submit" onclick="return confirm('Confirmar exclusão?')">Deletar</button>
                    </form>
                    <form method="get" action="index.php" style="display:inline;">
                        <input type="hidden" name="tabela" value="<?= htmlspecialchars($tabela) ?>">
                        <input type="hidden" name="acao"  value="editar">
                        <input type="hidden" name="<?= htmlspecialchars($chave_primaria) ?>"
                               value="<?= htmlspecialchars($linha[$chave_primaria]) ?>">
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

</body>
</html>