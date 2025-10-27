<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "todo_app";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["error" => "Falha na conexão com o banco de dados."]));
}
?>
