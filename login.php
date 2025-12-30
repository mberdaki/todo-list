<?php
require 'config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// Simple brute-force protection (session-based)
if (empty($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    // Rate limiting: 5 attempts, 5 minute lock
    if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 300) {
        $erro = 'Muitas tentativas. Tente novamente mais tarde.';
    } elseif (!hash_equals($_SESSION['csrf_token'], $token)) {
        $erro = 'Requisição inválida.';
    } elseif (empty($email) || empty($senha) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email ou senha inválidos.';
    } else {
        // Use a narrow select and LIMIT 1
        $stmt = $pdo->prepare('SELECT id, nome, senha FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            // reset attempts on success
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt_time'] = 0;
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            $erro = 'Email ou senha inválidos.';
            // small delay to slow down brute force
            sleep(1);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - ToDo List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Login</h3>
                        <?php if(isset($erro)): ?>
                            <div class="alert alert-danger"><?=htmlspecialchars($erro)?></div>
                        <?php endif; ?>
                        <form method="POST" novalidate>
                            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="Email" value="<?=htmlspecialchars($email ?? '')?>" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="senha" placeholder="Senha" value="" required>
                            </div>
                            <button class="btn btn-primary w-100">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
