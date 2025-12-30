<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php';

if ($_POST) {
    echo "POST recebido!<br>";
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    echo "Email: '$email'<br>";
    echo "Senha: '$senha'<br>";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Usuário: " . $user['nome'] . "<br>";
        echo "Hash: " . substr($user['senha'], 0, 20) . "...<br>";
        echo "Verify: " . (password_verify($senha, $user['senha']) ? '✅ OK' : '❌ FALHOU') . "<br>";
        
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            echo "✅ SESSION OK! Redirecionando...<br>";
            header('Location: index.php');
            exit;
        }
    } else {
        echo "❌ Usuário não encontrado!<br>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>🔍 Login Debug</h3>
                        <form method="POST">
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="mathe@teste.com" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="senha" placeholder="123456" required>
                            </div>
                            <button class="btn btn-primary w-100">Testar Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
