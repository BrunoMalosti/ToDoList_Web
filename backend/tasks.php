<?php
require_once __DIR__."/config.php";

$action = $_GET['action'] ?? '';

switch($action){

    case "list":
        $user_id = intval($_GET['user_id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id=? ORDER BY data_criacao ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($tasks);
        break;

    case "add":
        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $data['user_id'];
        $titulo = $data['titulo'];
        $descricao = isset($data['descricao']) ? $data['descricao'] : '';
        $data_conclusao = isset($data['data_conclusao']) ? $data['data_conclusao'] : null;
        $status = isset($data['status']) ? $data['status'] : 'aberta';

        $stmt = $conn->prepare("INSERT INTO tasks (user_id, titulo, descricao, data_conclusao, status) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $user_id, $titulo, $descricao, $data_conclusao, $status);
        $stmt->execute();
        echo json_encode(["success" => true]);
        break;

    case "edit":
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];
        $titulo = $data['titulo'];
        $descricao = isset($data['descricao']) ? $data['descricao'] : '';
        $data_conclusao = isset($data['data_conclusao']) ? $data['data_conclusao'] : null;
        $status = isset($data['status']) ? $data['status'] : 'aberta';

        $stmt = $conn->prepare("UPDATE tasks SET titulo=?, descricao=?, data_conclusao=?, status=? WHERE id=?");
        $stmt->bind_param("ssssi", $titulo, $descricao, $data_conclusao, $status, $id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        break;

    case "update_status":
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];
        $status = $data['status'];

        $stmt = $conn->prepare("UPDATE tasks SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        break;

    case "delete":
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];

        $stmt = $conn->prepare("DELETE FROM tasks WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        break;

    default:
        echo json_encode(["error" => "Ação inválida"]);
}
