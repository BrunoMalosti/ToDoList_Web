<?php
header("Content-Type: application/json");
require_once "config.php";

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents("php://input"), true);

if($action === 'register'){
    $nome = $input['nome'];
    $sobrenome = $input['sobrenome'];
    $nascimento = $input['nascimento'];
    $login = $input['login'];
    $senha = password_hash($input['senha'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (nome, sobrenome, nascimento, login, senha) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nome, $sobrenome, $nascimento, $login, $senha);
    $stmt->execute();

    echo json_encode(["success" => true]);
}

if($action === 'login'){
    $login = $input['login'];
    $senha = $input['senha'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE login=?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if($user && password_verify($senha, $user['senha'])){
        
        // --- NOVO: Cria e retorna o JWT ---
        $jwt_token = create_jwt($user['id']);
        echo json_encode(["success"=>true, "token"=>$jwt_token]);
        
    } else {
        echo json_encode(["success"=>false,"error"=>"Usuário ou senha inválidos"]);
    }
}
?>