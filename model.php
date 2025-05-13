<?php
class Model {
    private $conexao;
    private $tabela;
    private $chave_primaria;

    public function __construct($tabela, $chave_primaria) {
        $this->tabela = $tabela;
        $this->chave_primaria = $chave_primaria;

        $this->conexao = new mysqli("localhost", "root", "", "autolocadora");

        if ($this->conexao->connect_error) {
            die("Erro na conexão: " . $this->conexao->connect_error);
        }
    }

    public function inserir($dados) {
        $colunas = implode(", ", array_keys($dados));
        $valores = "'" . implode("', '", array_map([$this->conexao, 'real_escape_string'], array_values($dados))) . "'";
        $sql = "INSERT INTO {$this->tabela} ($colunas) VALUES ($valores)";
        return $this->conexao->query($sql);
    }

    public function deletar($id) {
        $id = $this->conexao->real_escape_string($id);
        $sql = "DELETE FROM {$this->tabela} WHERE {$this->chave_primaria} = '$id'";
        return $this->conexao->query($sql);
    }

    public function ler($condicoes = "") {
        $sql = "SELECT * FROM {$this->tabela}";
        if (!empty($condicoes)) {
            $sql .= " WHERE $condicoes";
        }
        $resultado = $this->conexao->query($sql);
        $dados = [];
        if ($resultado) {
            while ($linha = $resultado->fetch_assoc()) {
                $dados[] = $linha;
            }
        }
        return $dados;
    }

    // Editar dados
    public function editar($id, $dados) {
        $set = [];
        foreach ($dados as $coluna => $valor) {
            $valor = $this->conexao->real_escape_string($valor);
            $set[] = "$coluna = '$valor'";
        }
        $set_string = implode(", ", $set);
        $id = $this->conexao->real_escape_string($id);
        $sql = "UPDATE {$this->tabela} SET $set_string WHERE {$this->chave_primaria} = '$id'";
        return $this->conexao->query($sql);
    }

    // Verificar se veículo está disponível
    public function veiculoDisponivel($id_veiculo, $data_inicio, $data_fim) {
        $id_veiculo = $this->conexao->real_escape_string($id_veiculo);
        $data_inicio = $this->conexao->real_escape_string($data_inicio);
        $data_fim = $this->conexao->real_escape_string($data_fim);

        $sql = "SELECT * FROM tblocacao 
                WHERE veiculo_id = '$id_veiculo' 
                  AND ('$data_inicio' <= data_fim AND '$data_fim' >= data_inicio)";
        $resultado = $this->conexao->query($sql);
        return $resultado->num_rows === 0; // true = disponível
    }
}
?>
