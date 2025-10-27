<?php
$senha_digitada = 'senha123'; // a senha que você quer testar
$hash_banco = '$2y$10$e9Jx9KJz2o3gqfE1m8mV9u2a7c8eK4qFv7JdW1yZ0q3B9fG7pH8a';

if(password_verify($senha_digitada, $hash_banco)){
    echo "Senha correta!";
}else{
    echo "Senha incorreta!";
}
