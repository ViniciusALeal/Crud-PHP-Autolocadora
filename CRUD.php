<?php
require_once("model.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST["acao"];
    $tabela = $_POST["tabela"];
    $chave_primaria = $_POST["chave_primaria"]; // Ex: "id_cliente", "id_veiculo"

    $model = new Model($tabela, $chave_primaria);

    if ($acao === "inserir") {
        $dados = $_POST;
        unset($dados["acao"], $dados["tabela"], $dados["chave_primaria"]);
        $model->inserir($dados);

    } elseif ($acao === "editar") {
        $id = $_POST[$chave_primaria];
        $dados = $_POST;
        unset($dados["acao"], $dados["tabela"], $dados["chave_primaria"], $dados[$chave_primaria]);
        $model->editar($id, $dados);

    } elseif ($acao === "deletar") {
        $id = $_POST[$chave_primaria];
        $model->deletar($id);

    } elseif ($acao === "ler") {
        $condicoes = isset($_POST["condicoes"]) ? $_POST["condicoes"] : "";
        $resultado = $model->ler($condicoes);
        echo json_encode($resultado);

    } elseif ($acao === "disponibilidade") {
        $id_veiculo = $_POST["id_veiculo"];
        $data_inicio = $_POST["data_inicio"];
        $data_fim = $_POST["data_fim"];
        $disponivel = $model->veiculoDisponivel($id_veiculo, $data_inicio, $data_fim);
        echo json_encode(["disponivel" => $disponivel]);
    }
}
?>
