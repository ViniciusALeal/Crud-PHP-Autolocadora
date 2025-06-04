<?php
// Incluindo a classe de conexão
include('dbintegration.php');

class Model {
    private $conexao;

    public function __construct($conexao) {
        $this->conexao = $conexao;
    }

    // Método para verificar disponibilidade do veículo
    public function veiculoDisponivel($id_veiculo, $data_inicio, $data_fim) {
        // Defina uma consulta SQL para verificar se o veículo está disponível no período
        $sql = "SELECT COUNT(*) as count FROM tblocacao 
                WHERE id_veiculo = ? 
                AND (
                    (data_inicio BETWEEN ? AND ?) 
                    OR (data_fim BETWEEN ? AND ?)
                    OR (? BETWEEN data_inicio AND data_fim)
                )";
        
        // Prepare a consulta
        if ($stmt = $this->conexao->prepare($sql)) {
            $stmt->bind_param("isssss", $id_veiculo, $data_inicio, $data_fim, $data_inicio, $data_fim, $data_inicio);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            // Se a contagem for 0, o veículo está disponível
            return $count == 0 ? true : false;
        }

        return false; // Se algo der errado na execução
    }
}

// Criando a instância da classe DBconect
$db = new DBconect("localhost", "root", "", "auto");
$conexao = $db->getConnection(); // Obtém a conexão através do método

// Pega dados
$tabela = $_POST['tabela'] ?? '';
$acao = $_POST['acao'] ?? '';

// Verifica se a ação é de verificação de disponibilidade
if ($acao === 'verificar_disponibilidade') {
    $id_veiculo = $_POST["id_veiculo"] ?? '';
    $data_inicio = $_POST["data_inicio"] ?? '';
    $data_fim = $_POST["data_fim"] ?? '';
    
    // Instancia o modelo
    $model = new Model($conexao);

    // Verifica a disponibilidade
    $disponivel = $model->veiculoDisponivel($id_veiculo, $data_inicio, $data_fim);

    // Retorna a resposta em JSON
    echo json_encode(["disponivel" => $disponivel]);
    exit; // Finaliza o script aqui para evitar qualquer outro processamento
}

$campos = [];
$result = $conexao->query("DESCRIBE $tabela");
if (!$result) {
    die("Erro na consulta DESCRIBE: " . $conexao->error);
}
while ($row = $result->fetch_assoc()) {
    $campos[] = $row;
}
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
} elseif ($acao === 'editar') {
    $dados = $_POST;
    unset($dados['acao'], $dados['tabela']);

    // Separa ID
    $id = $conexao->real_escape_string($dados[$chave_primaria]);
    unset($dados[$chave_primaria]);

    // Monta pares coluna='valor'
    $pares = [];
    foreach ($dados as $coluna => $valor) {
        $valor = $conexao->real_escape_string($valor);
        $pares[] = "$coluna = '$valor'";
    }

    $sql = "UPDATE $tabela SET " . implode(", ", $pares) . " WHERE $chave_primaria = '$id'";
    $conexao->query($sql);
}

// Fechar a conexão com o banco ao final
$db->closeConnection();

// Voltar para a index
header("Location: index.php?tabela=$tabela");
exit;
?>
