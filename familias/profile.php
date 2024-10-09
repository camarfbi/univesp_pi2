<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/Search.php'; 
require './includes/Familias.php';
require './includes/SessionMessage.php';
require './includes/funcoes.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\Associado;
use App\Includes\PermissionManager;
use App\Includes\SessionMessage;

// Inicializa a conexão com o banco de dados e a autenticação
$db = new Database();
$pdo = $db->getPdo();

// Verifica se o usuário está autenticado
$auth = new Auth(new User($pdo));
if (!$auth->isAuthenticated()) {
    header('Location: index.php');
    exit();
}

// ID do usuário logado (admin)
$userLogadoId = $_SESSION['user_id'];

// Inicializa o PermissionManager
$permissionManager = new PermissionManager($pdo, $userLogadoId);

// Obtém o caminho da página dinamicamente
$pageAndDir = $permissionManager->getCurrentPageAndDirectory();
$dirAndPage = $pageAndDir['dir'] . "/" . $pageAndDir['page'];

// Verifica permissão
if (!$permissionManager->hasPermission($pageAndDir['page'], $pageAndDir['dir'])) {
    SessionMessage::setResponseAndRedirect("Você não tem permissão para acessar esta página.", 'bg-danger-500', $dirAndPage);
}

// Inicializa o model do Associado
$associadoModel = new Associado($pdo);

// Captura o ID do responsável da URL (se existir)
$editAssociadoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Se for edição, busca as informações do responsável
$editAssociado = $editAssociadoId ? $associadoModel->getAssociadoById($editAssociadoId) : null;

// Preenche as variáveis com os valores do responsável, se for edição
$nome = $editAssociado['nome'] ?? '';
$sobrenome = $editAssociado['sobrenome'] ?? '';
$cpf = $editAssociado['cpf'] ?? '';
$rg = $editAssociado['rg'] ?? '';
$nascimento = $editAssociado['nascimento'] ?? '';
$email = $editAssociado['email'] ?? '';
$telefone = $editAssociado['telefone'] ?? '';
$celular = $editAssociado['celular'] ?? '';
$cep = $editAssociado['cep'] ?? '';
$endereco = $editAssociado['endereco'] ?? '';
$num = $editAssociado['num'] ?? '';
$complemento = $editAssociado['complemento'] ?? '';
$bairro = $editAssociado['bairro'] ?? '';
$cidade = $editAssociado['cidade'] ?? '';
$uf = $editAssociado['uf'] ?? '';
$foto = $editAssociado['foto'] ?? './assets/images/all-img/user.png';
$status = $editAssociado['status'] ?? 0;
$adimplencia = $editAssociado['adimplencia'] ?? 0;
$bloqueado = $editAssociado['bloqueado'] ?? 0;
$user = $editAssociado['user'] ?? '';
$password = '';

// Processamento do formulário (inserção ou edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] == 'ASSOCIADO') {
        // Captura os dados do formulário
        $formData = $associadoModel->getFormData($_POST, $editAssociado, $editAssociadoId);

        // Verifica se o email já existe para outro responsável
        if ($associadoModel->emailExists($formData['email'], $editAssociadoId)) {
            SessionMessage::setResponseAndRedirect('Este e-mail já existe!', 'bg-danger-500', "{$dirAndPage}&id={$editAssociadoId}");
        }
        
        // Verifica se o usuário já existe para outro responsável
        if ($associadoModel->userExists($formData['user'], $editAssociadoId)) {
            SessionMessage::setResponseAndRedirect('O usuário ' . $formData['user'] . ' já existe!', 'bg-danger-500', "{$dirAndPage}&id={$editAssociadoId}");
        }

        // Atualização de um responsável existente
        if ($editAssociadoId) {
            if ($associadoModel->updateAssociado($editAssociadoId, $formData)) {
                // Verifica e processa a imagem, caso exista no envio do formulário
                if (!empty($_FILES['foto']['name'])) {
                    $formData['foto'] = $associadoModel->processImage($editAssociadoId, $_FILES['foto'], $formData['foto']);
                    $associadoModel->updateAssociado($editAssociadoId, ['foto' => $formData['foto']]);
                }
                SessionMessage::setResponseAndRedirect('Responsável atualizado com sucesso!', 'bg-success-500', "{$dirAndPage}&id={$editAssociadoId}");
            } else {
                SessionMessage::setResponseAndRedirect('Erro ao atualizar responsável!', 'bg-danger-500', "{$dirAndPage}&id={$editAssociadoId}");
            }
        } else {
            // Inserção de um novo responsável
            $newId = $associadoModel->insertAssociado($formData);

            if ($newId) {
                // Processa a imagem com o novo ID
                if (!empty($_FILES['foto']['name'])) {
                    $formData['foto'] = $associadoModel->processImage($newId, $_FILES['foto'], null);
                    $associadoModel->updateAssociado($newId, ['foto' => $formData['foto']]);
                }
                SessionMessage::setResponseAndRedirect('Responsável cadastrado com sucesso!', 'bg-success-500', "{$dirAndPage}&id={$newId}");
            } else {
                SessionMessage::setResponseAndRedirect('Erro ao inserir responsável!', 'bg-danger-500', "{$dirAndPage}");
            }
        }
    }
}

// Exibir mensagem de resposta, se houver
$responseMessage = SessionMessage::getMessage();
?>