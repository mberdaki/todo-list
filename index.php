<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Adicionar tarefa
if (isset($_POST['acao']) && $_POST['acao'] == 'adicionar') {
    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO tarefas (usuario_id, titulo, descricao) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $titulo, $descricao]);
    header('Location: index.php');  // ← REDIRECIONA (resolve duplicação)
    exit;
}

// Marcar como concluída
if (isset($_POST['acao']) && $_POST['acao'] == 'concluir') {
    $id = $_POST['id'] ?? 0;
    $stmt = $pdo->prepare("UPDATE tarefas SET concluida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $user_id]);
    header('Location: index.php');  // ← REDIRECIONA
    exit;
}

// Listar tarefas
$stmt = $pdo->prepare("SELECT * FROM tarefas WHERE usuario_id = ? ORDER BY data_criacao DESC");
$stmt->execute([$user_id]);
$tarefas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>ToDo List - <?= $_SESSION['user_nome'] ?></title>
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
                <form method="POST">
                    <input type="hidden" name="acao" value="adicionar">
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
                        <small class="text-muted"><?= $tarefa['data_criacao'] ?></small>
                        <?php if(!$tarefa['concluida']): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="acao" value="concluir">
                                <input type="hidden" name="id" value="<?= $tarefa['id'] ?>">
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
