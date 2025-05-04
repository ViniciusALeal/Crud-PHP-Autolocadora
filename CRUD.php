<?php
// Incluindo a classe de conexão
include('dbintegration.php');

// Criando a instância da classe DBconect
$db = new DBconect("localhost", "root", "", "autobahn");
$conexao = $db->getConnection(); // Obtém a conexão através do método

// Pega dados
$tabela = $_POST['tabela'];
$acao = $_POST['acao'];

// Descobrir estrutura para pegar chave primária
$campos = [];
$result = $conexao->query("DESCRIBE $tabela");
while ($row = $result->fetch_assoc()) {
    $campos[] = $row;
}
$chave_primaria = $campos[0]['Field'];

if ($acao === 'inserir') {
    $dados = $_POST;
    unset($dados['acao'], $dados['tabela']);
    
    $colunas = implode(",", array_keys($dados));
    $valores = "'" . implode("','", array_map([$conexao, 'real_escape_string'], array_values($dados))) . "'";

    $sql = "INSERT INTO $tabela ($colunas) VALUES ($valores)";
    $conexao->query($sql);

} elseif ($acao === 'deletar') {
    $id = $conexao->real_escape_string($_POST['id']);
    
    $sql = "DELETE FROM $tabela WHERE $chave_primaria = '$id'";
    $conexao->query($sql);
}

// Fechar a conexão com o banco ao final
$db->closeConnection();

// Voltar para a index
header("Location: index.php?tabela=$tabela");
exit;
?>
