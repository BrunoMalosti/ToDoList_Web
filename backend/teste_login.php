<?php
require_once __DIR__."/config.php";

$login = 'aluno';
$senha = 'senha123';

$stmt = $conn->prepare("SELECT * FROM users WHERE login=?");
$stmt->bind_param("s", $login);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

var_dump($user);

if($user && password_verify($senha, $user['senha'])){
    echo "Login OK!";
}else{
    echo "Usuário ou senha inválidos";
}
?>
