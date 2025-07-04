<?php
session_start();
header('Content-Type: application/json');

$arquivo = 'usuarios.json';

// Inicializa arquivo se não existir
if (!file_exists($arquivo)) {
    file_put_contents($arquivo, json_encode([]));
}

$usuarios = json_decode(file_get_contents($arquivo), true);
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

function salvaUsuarios($usuarios, $arquivo) {
    file_put_contents($arquivo, json_encode($usuarios, JSON_PRETTY_PRINT));
}

switch ($acao) {
    case 'status':
        if (isset($_SESSION['logado'])) {
            $login = $_SESSION['logado'];
            foreach ($usuarios as $u) {
                if ($u['login'] === $login) {
                    echo json_encode(['ok' => true, 'usuario' => $u]);
                    exit;
                }
            }
        }
        echo json_encode(['ok' => false]);
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['ok' => true]);
        break;

    case 'editar':
        if (!isset($_SESSION['logado'])) {
            echo json_encode(['ok' => false, 'msg' => 'Não autorizado']);
            exit;
        }
        $loginEdicao = $_POST['login'] ?? $_SESSION['logado'];

        foreach ($usuarios as &$u) {
            if ($u['login'] === $loginEdicao) {
                $u['nome'] = $_POST['nome'] ?? $u['nome'];
                $u['email'] = $_POST['email'] ?? $u['email'];
                $u['senha'] = $_POST['senha'] ?? $u['senha'];
                salvaUsuarios($usuarios, $arquivo);
                echo json_encode(['ok' => true, 'msg' => 'Dados atualizados']);
                exit;
            }
        }
        echo json_encode(['ok' => false, 'msg' => 'Usuário não encontrado']);
        break;

    case 'criar':
        $login = $_POST['login'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';

        if (!$login || !$senha || !$nome || !$email) {
            echo json_encode(['ok' => false, 'msg' => 'Todos os campos são obrigatórios']);
            exit;
        }

        foreach ($usuarios as $u) {
            if ($u['login'] === $login) {
                echo json_encode(['ok' => false, 'msg' => 'Login já existe']);
                exit;
            }
        }

        $usuarios[] = [
            'login' => $login,
            'senha' => $senha,
            'nome' => $nome,
            'email' => $email
        ];
        salvaUsuarios($usuarios, $arquivo);
        echo json_encode(['ok' => true]);
        break;

    case 'checkEditar':
        $login = $_POST['login'] ?? '';
        $email = $_POST['email'] ?? '';

        foreach ($usuarios as $u) {
            if ($u['login'] === $login && $u['email'] === $email) {
                echo json_encode(['ok' => true, 'usuario' => $u]);
                exit;
            }
        }
        echo json_encode(['ok' => false, 'msg' => 'Login ou email incorretos']);
        break;

    case 'login':
        $login = $_POST['login'] ?? '';
        $senha = $_POST['senha'] ?? '';

        foreach ($usuarios as $u) {
            if ($u['login'] === $login && $u['senha'] === $senha) {
                $_SESSION['logado'] = $login;
                $_SESSION['nome'] = $u['nome']; // *** aqui, grava nome na sessão ***
                echo json_encode(['ok' => true]);
                exit;
            }
        }
        echo json_encode(['ok' => false, 'msg' => 'Login ou senha inválidos']);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Ação inválida']);
}
