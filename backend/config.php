<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "todo_app";

// Chave Secreta para Assinatura do JWT (MUITO IMPORTANTE!)
// Mantenha esta chave em segredo e use uma mais complexa em produção.
define("JWT_SECRET_KEY", "SuaChaveSecretaParaAssinaturaDoToken12345"); 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Retorna erro formatado como JSON, como o restante da API
    die(json_encode(["error" => "Falha na conexão com o banco de dados."]));
}

/**
 * Codifica o JWT
 */
function create_jwt($user_id) {
    // 1 hora de validade
    $expiration_time = time() + (60 * 60); 

    // Header (Cabeçalho)
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    // Payload (Corpo com dados do usuário)
    $payload = json_encode(['user_id' => $user_id, 'exp' => $expiration_time]);
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    // Signature (Assinatura)
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET_KEY, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Retorna o Token Completo
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Decodifica e Valida o JWT
 * Retorna o user_id se for válido, ou 0 se inválido.
 */
function validate_jwt($jwt_token) {
    if (!$jwt_token) return 0;

    $parts = explode('.', $jwt_token);
    if (count($parts) !== 3) return 0;

    list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

    // 1. Valida a Assinatura
    $expected_signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET_KEY, true);
    $base64UrlExpectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expected_signature));

    if ($base64UrlSignature !== $base64UrlExpectedSignature) return 0;

    // 2. Decodifica o Payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);

    // 3. Valida a Expiração
    if (!isset($payload['exp']) || $payload['exp'] < time()) return 0;

    // Retorna o ID do usuário
    return isset($payload['user_id']) ? intval($payload['user_id']) : 0;
}
?>