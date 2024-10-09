<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use App\Includes\Database;
use App\Includes\User;
use App\Includes\Auth;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
}

/* end session */

$db = new Database();
$userModel = new User($db->getPdo());
$auth = new Auth($userModel);

// Verifica se o usuário está autenticado
if ($auth->isAuthenticated()) {
    header('Location: dashboard');
    exit();
}
// O restante do código HTML e formulário de login continua aqui...
?>


<!DOCTYPE html>
<html lang="en" dir="ltr" class="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="">
  <title>Dashcode - HTML Template</title>
  <link rel="icon" type="image/png" href="assets/images/logo/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/rt-plugins.css">
  <link href="https://unpkg.com/aos@2.3.0/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="">
  <link rel="stylesheet" href="assets/css/app.css">
  <!-- START : Theme Config js-->
  <script src="assets/js/settings.js" sync></script>
  <!-- END : Theme Config js-->
  <style>
  #cookieConsentBanner {
    z-index: 1000;
    font-size: 14px;
}

#cookieConsentBanner button {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
}
</style>
</head>

<body class=" font-inter skin-default">

<div class="loginwrapper">
    <div class="lg-inner-column">
      <div class="left-column relative z-[1]">
        <div class="max-w-[520px] pt-20 pl-15" style="padding:20px">
          <a href="index.php">
            <img src="assets/images/logo/logo.jpg" alt="" class="mb-5 dark_logo" style="border-radius: 100px; width: 120px;">
          </a>
          <h4>
            Bem-vindo Colaborador associado
            <span class="text-slate-800 dark:text-slate-400 font-bold"> ONG ASAS </span>
          </h4><br>                  
          Se você ainda não for um Colaborador associado, associe-se <a href="">clicando aqui</a>
        </div>
       
        <div class="absolute left-0 2xl:bottom-[-160px] bottom-[-130px] h-full w-full z-[-1]">
          <img src="assets/images/auth/ils1.svg" alt="" class=" h-full w-full object-contain">
        </div>
      </div>
      <div class="right-column  relative">
        <div class="inner-content h-full flex flex-col bg-white dark:bg-slate-800">
          <div class="auth-box h-full flex flex-col justify-center">
            <div class="mobile-logo text-center mb-6 lg:hidden block">
              <a href="index.php">
                <img src="assets/images/logo/logo.svg" alt="" class="mb-10 dark_logo">
                <img src="assets/images/logo/logo-white.svg" alt="" class="mb-10 white_logo">
              </a>
            </div>
            <div class="text-center 2xl:mb-10 mb-4">
              <h4 class="font-medium">Acessar</h4>
              <div class="text-slate-500 text-base">
                Entre com seu usuário e senha para acessar o painel do Colaborador Associado
              </div>
            </div>
            <!-- BEGIN: Login Form -->
            <form class="space-y-4" method="post" action="login.php">
              <div class="fromGroup">
                <label class="block capitalize form-label">Usuário</label>
                <div class="relative ">
                  <input type="text" id="username" name="username" class="form-control py-2" placeholder="Seu usuário" value="">
                </div>
              </div>
              <div class="fromGroup">
                <label class="block capitalize form-label">password</label>
                <div class="relative ">
                  <input type="password" id="password" name="password" class="form-control py-2" placeholder="Sua senha" value="">
                </div>
              </div>
              <div class="flex justify-between">
                <label class="flex items-center cursor-pointer">
                  <input type="checkbox" class="hiddens" id="remember" name="remember">
                  <span class="text-slate-500 dark:text-slate-400 text-sm leading-6 capitalize">Manter conectado</span>
                </label>
                <a class="text-sm text-slate-800 dark:text-slate-400 leading-6 font-medium" href="forget-password-one.html">Esqueceu a senha?</a>
              </div>
              <div class="error-message" style="color: red; font-weight: bold;">
              <?php if (isset($_SESSION['login_error'])): ?>
				<div class="error">
                    <?php echo htmlspecialchars($_SESSION['login_error']); ?>
                     </div>
					<?php unset($_SESSION['login_error']); ?>
				<?php endif; ?>
              <button class="btn btn-dark block w-full text-center">Entrar</button>
            </form>
            <!-- END: Login Form -->
            <div class="relative border-b-[#9AA2AF] border-opacity-[16%] border-b pt-6">
            </div>
            <div class="md:max-w-[345px] mx-auto font-normal text-slate-500 dark:text-slate-400 mt-12 uppercase text-sm">
              Não é um colaborador associado?
              <a href="signup-one.html" class="text-slate-900 dark:text-white font-medium hover:underline">
                Associe-se
              </a>
            </div>
          </div>
          <div class="auth-footer text-center">
            Copyright 2024, ONG ASAS todos os direitos reservados.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Banner de Consentimento de Cookies -->
<div id="cookieConsentBanner" style="position: fixed; bottom: 0; width: 100%; background-color: #333; color: white; text-align: center; padding: 10px; display: none;">
    Este site usa cookies para melhorar sua experiência. Ao continuar navegando, você aceita o uso de cookies.
    <button id="acceptCookies" style="background-color: #4CAF50; color: white; border: none; padding: 5px 10px; margin-left: 10px; cursor: pointer;">
        Aceitar
    </button>
</div>


  <!-- scripts -->
  <script src="assets/js/jquery-3.6.0.min.js"></script>
  <script src="assets/js/rt-plugins.js"></script>
  <script src="assets/js/app.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
      // Verifica se o usuário já deu consentimento
      if (!getCookie('cookies_accepted')) {
          document.getElementById('cookieConsentBanner').style.display = 'block';
      }

      // Quando o usuário clicar em "Aceitar"
      document.getElementById('acceptCookies').addEventListener('click', function() {
          setCookie('cookies_accepted', 'true', 365); // Cookie válido por 1 ano
          document.getElementById('cookieConsentBanner').style.display = 'none';
      });
  });

  // Função para definir o cookie
  function setCookie(name, value, days) {
      var expires = "";
      if (days) {
          var date = new Date();
          date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
          expires = "; expires=" + date.toUTCString();
      }
      document.cookie = name + "=" + (value || "") + expires + "; path=/";
  }

  // Função para obter o valor do cookie
  function getCookie(name) {
      var nameEQ = name + "=";
      var ca = document.cookie.split(';');
      for (var i = 0; i < ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == ' ') c = c.substring(1, c.length);
          if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
      }
      return null;
  }

  </script>
</body>
</html>

