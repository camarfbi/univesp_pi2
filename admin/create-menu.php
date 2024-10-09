<?php
declare(strict_types=1);

require '../vendor/autoload.php';

use App\Includes\Database;

session_start();

// Verifique se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$pdo = $db->getPdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Adicionar Categoria
    if (isset($_POST['categoria_nome']) && !empty($_POST['categoria_nome'])) {
        $stmt = $pdo->prepare("INSERT INTO Xcategorias (nome) VALUES (:nome)");
        $stmt->execute(['nome' => $_POST['categoria_nome']]);
    }

    // Adicionar Subcategoria
    if (isset($_POST['subcategoria_nome'], $_POST['categoria_id']) && !empty($_POST['subcategoria_nome']) && !empty($_POST['categoria_id'])) {
        $stmt = $pdo->prepare("INSERT INTO Xsubcategorias (nome, categoria_id) VALUES (:nome, :categoria_id)");
        $stmt->execute([
            'nome' => $_POST['subcategoria_nome'],
            'categoria_id' => $_POST['categoria_id']
        ]);
    }
}

// Obter categorias para exibir no formulário de subcategorias
$stmt = $pdo->query("SELECT id, nome FROM Xcategorias");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
</head>
<body>
    <h1>Editar Usuário</h1>
    
</body>
</html>
<div class="page-content">
            <div class="transition-all duration-150 container-fluid" id="page_layout">
              <div id="content_layout">
                <!-- BEGIN: Breadcrumb -->
                <div class="mb-5">
                  <ul class="m-0 p-0 list-none">
                    <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter ">
                      <a href="index.html">
                        <iconify-icon icon="heroicons-outline:home"></iconify-icon>
                        <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
                      </a>
                    </li>
                    <li class="inline-block relative text-sm text-primary-500 font-Inter ">
                      Forms
                      <iconify-icon icon="heroicons-outline:chevron-right" class="relative top-[3px] text-slate-500 rtl:rotate-180"></iconify-icon>
                    </li>
                    <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
                      Input</li>
                  </ul>
                </div>
                <!-- END: BreadCrumb -->
                <div class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                  <!-- Formatter Support -->
                  <div class="card xl:col-span-2 rounded-md bg-white dark:bg-slate-800 lg:h-full shadow-base">
                    <div class="card-body flex flex-col p-6">
                      <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5 -mx-6 px-6">
                        <div class="flex-1">
                          <div class="card-title text-slate-900 dark:text-white">Editar Usuário</div>
                        </div>
                      </header>
                        <form method="post" action="update.php">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                            <div class="card-text h-full space-y-4">
                            <div class="input-area">
                                <label for="textFormatter" class="form-label">Nome</label>
                                <div class="relative">
                                <input id="textFormatter" type="text" class="form-control lowercase" placeholder="Text Formatter">
                                </div>
                            </div>
                            <div class="card-text h-full space-y-4">
                            <div class="input-area">
                                <label for="textFormatter" class="form-label">Text Input With Formatter (On Input)</label>
                                <div class="relative">
                                <input id="textFormatter" type="text" class="form-control lowercase" placeholder="Text Formatter">
                                </div>
                            </div>
                            </div>

                            <label for="nome">Nome:</label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>"><br>
                            <label for="sobrenome">Sobrenome:</label>
                            <input type="text" id="sobrenome" name="sobrenome" value="<?php echo htmlspecialchars($user['sobrenome']); ?>"><br>
                            <label for="user">Usuário:</label>
                            <input type="text" id="user" name="user" value="<?php echo htmlspecialchars($user['user']); ?>"><br>
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"><br>
                            <label for="password">Senha:</label>
                            <input type="text" id="password" name="password" value="<?php echo htmlspecialchars($user['password']); ?>"><br>
                            <label for="access">Permissão:</label>
                            <input type="text" id="access" name="access" value="<?php echo htmlspecialchars($user['access']); ?>"><br>
                            <label for="descricao">Sobre:</label>
                            <input type="text" id="descricao" name="descricao" value="<?php echo htmlspecialchars($user['descricao']); ?>"><br>
                            <label for="bloqueado">Permissões:</label>
                            <input type="text" id="bloqueado" name="bloqueado" value="<?php echo htmlspecialchars($user['bloqueado']); ?>"><br>
                            <label for="foto">Sobre:</label>
                            <input type="text" id="foto" name="foto" value="<?php echo htmlspecialchars($user['foto']); ?>"><br>
                            <label for="admin">Sobre:</label>
                            <input type="text" id="admin" name="admin" value="<?php echo htmlspecialchars($user['admin']); ?>"><br>
                            <input type="submit" value="Atualizar">
                        </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
</div>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Categorias e Subcategorias</title>
</head>
<body>
    <h1>Criar Categoria</h1>
    <form method="post" action="">
        <label for="categoria_nome">Nome da Categoria:</label>
        <input type="text" name="categoria_nome" id="categoria_nome" required>
        <button type="submit">Adicionar Categoria</button>
    </form>

    <h1>Criar Subcategoria</h1>
    <form method="post" action="">
        <label for="categoria_id">Categoria:</label>
        <select name="categoria_id" id="categoria_id" required>
            <option value="">Selecione uma Categoria</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= $categoria['id'] ?>"><?= $categoria['nome'] ?></option>
            <?php endforeach; ?>
        </select>
        <label for="subcategoria_nome">Nome da Subcategoria:</label>
        <input type="text" name="subcategoria_nome" id="subcategoria_nome" required>
        <button type="submit">Adicionar Subcategoria</button>
    </form>
</body>
</html>
