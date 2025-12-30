<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// Handle POST actions with basic validation and CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['acao'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $erro = 'Requisição inválida.';
    } else {
        if ($action === 'adicionar') {
            $titulo = substr(trim($_POST['titulo'] ?? ''), 0, 255);
            $descricao = substr(trim($_POST['descricao'] ?? ''), 0, 1000);
            if ($titulo === '') {
                $erro = 'O título é obrigatório.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO tarefas (usuario_id, titulo, descricao) VALUES (?, ?, ?)');
                $stmt->execute([$user_id, $titulo, $descricao]);
                header('Location: index.php');
                exit;
            }
        } elseif ($action === 'concluir') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE tarefas SET concluida = 1 WHERE id = ? AND usuario_id = ?');
                $stmt->execute([$id, $user_id]);
            }
            header('Location: index.php');
            exit;
        }
    }
}

// Listar tarefas (colunas explícitas)
$stmt = $pdo->prepare('SELECT id, titulo, descricao, concluida, data_criacao FROM tarefas WHERE usuario_id = ? ORDER BY data_criacao DESC');
$stmt->execute([$user_id]);
$tarefas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>ToDo List - <?= htmlspecialchars($_SESSION['user_nome'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>📝 Minhas Tarefas</h1>
            <a href="logout.php" class="btn btn-outline-danger">Sair</a>
        </div>

        <!-- Form adicionar tarefa -->
        <div class="card mb-4">
            <div class="card-body">
                <h5>➕ Nova Tarefa</h5>
                <form method="POST" novalidate>
                    <input type="hidden" name="acao" value="adicionar">
                    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <input type="text" class="form-control" name="titulo" placeholder="Título da tarefa" required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <input type="text" class="form-control" name="descricao" placeholder="Descrição">
                        </div>
                        <div class="col-md-2 mb-2">
                            <button class="btn btn-success w-100">Adicionar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de tarefas -->
        <div class="row">
                        <?php foreach($tarefas as $tarefa): ?>
            <div class="col-md-6 mb-3">
                <div class="card <?= $tarefa['concluida'] ? 'border-success bg-light' : '' ?>">
                    <div class="card-body">
                        <?php if($tarefa['concluida']): ?>
                            <h6 class="text-success text-decoration-line-through"><?= htmlspecialchars($tarefa['titulo']) ?></h6>
                        <?php else: ?>
                            <h6><?= htmlspecialchars($tarefa['titulo']) ?></h6>
                        <?php endif; ?>
                        <p class="small text-muted"><?= htmlspecialchars($tarefa['descricao']) ?></p>
                        <small class="text-muted"><?= htmlspecialchars($tarefa['data_criacao']) ?></small>
                        <?php if(!$tarefa['concluida']): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="acao" value="concluir">
                                <input type="hidden" name="id" value="<?= (int)$tarefa['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
                                <button class="btn btn-sm btn-outline-success">✅ Concluída</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
