<?php
require_once __DIR__."/config.php";

// Função para obter todos os cabeçalhos de requisição
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Tenta obter o token do cabeçalho de Autorização
$auth_header = null;
$jwt_token = null;

// 1. Tenta obter de $_SERVER (HTTP_AUTHORIZATION)
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
}
// 2. Tenta obter via getallheaders()
elseif (function_exists('getallheaders')) {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
}
// 3. Última tentativa de fallback (REDIRECT_HTTP_AUTHORIZATION)
elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}


if ($auth_header && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $jwt_token = $matches[1];
}

// NOVO: Valida o token e extrai o user_id
$user_id = validate_jwt($jwt_token);


// Continua o processamento, AGORA USANDO O $user_id SEGURO
$action = $_GET['action'] ?? '';

switch($action){

    case "list":
        // $user_id é pego do JWT, não mais do GET inseguro
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id=? ORDER BY data_conclusao ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($tasks);
        break;

    case "add":
        $data = json_decode(file_get_contents("php://input"), true);
        // $user_id é pego do JWT, não mais do body
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

        // Garante que o usuário só edite suas próprias tarefas
        $stmt = $conn->prepare("UPDATE tasks SET titulo=?, descricao=?, data_conclusao=?, status=? WHERE id=? AND user_id=?");
        $stmt->bind_param("ssssii", $titulo, $descricao, $data_conclusao, $status, $id, $user_id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        break;

    case "update_status":
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];
        $status = $data['status'];

        // Garante que o usuário só atualize o status de suas próprias tarefas
        $stmt = $conn->prepare("UPDATE tasks SET status=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii", $status, $id, $user_id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        break;

    case "delete":
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];

        // Garante que o usuário só delete suas próprias tarefas
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        echo json_encode(["success" => true]);
        break;

    default:
        echo json_encode(["error" => "Ação inválida"]);
}
?>