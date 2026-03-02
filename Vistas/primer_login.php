<?php
session_start();
require_once '../config/db.php';

//protejo contra clickjacking
header("X-Frame-Options: DENY");
header("Content-Security-Policy: frame-ancestors 'none';");

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php?error=sin_sesion");
    exit;
}
$stmt = $pdo->prepare("SELECT cambio_pass, id_rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
//reenvio segun rol
if(!$user || (int)$user['cambio_pass'] === 0){
    switch ((int)$user['rol']){
                case 1: //admin
                    header("Location: dashboard_admin.php");
                    break;
                case 2: //medico
                    header("Location: dashboard_medico.php");
                    break;
                case 3: //recepcionista
                    header("Location: dashboard_recepcionista.php");
                    break;
                case 4: //paciente
                    header("Location: dashboard_paciente.php");
                    break;
                default:
                    session_unset();
                    session_destroy();
                    header("Location: ../login.php?error=rol_invalido");
                    break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Actualización Contraseña</title>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-10 rounded-3xl shadow-xl max-w-md w-full text-center">
        <div class="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg width="28" height="28" class="fill-current hover:text-red-600 transition-colors">
                <use href="../assets/sprite.svg?v=2#icon-user"></use>
            </svg>
        </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Actualiza tu contrasena</h2>
            <p class="text-gray-500 text-sm mb-8">Debes cambiar tu contraseña por primera vez. Debe incluir mayúsculas, minúsculas, números y un caracter especial (y tener al menos 12 caracteres).</p>
            <!--formulario-->
            <form action="../vistas/actualizar_pass.php" method="POST" class="text-left space-y-4" onsubmit="return validarPassword()">
                <div>
                    <label class="block text-xs font-black uppercase text-gray-400 mb-1">Nueva Contraseña</label>
                    <input type="password" id="nueva_pass" name="nueva_pass" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{12,}"class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-black uppercase text-gray-400 mb-1">Confirmar Contraseña</label>
                    <input type="password" id="confirmar_pass" name="confirmar_pass" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-blue-500 outline-none">
                </div>
                <button type="submit" class="w-full bg-blue-900 text-white py-4 rounded-2xl font-bold hover:bg-blue-800 transition-all shadow-lg">Actualizar</button>
            </form>
        </div>
    </div>
    <!--validacion de coincidencia pass-->
    <script>
        function validarPassword(){
            const pass = document.getElementById('nueva_pass').value;
            const conf = document.getElementById('confirmar_pass').value;
            if(pass != conf){
                alert("Las contraseñas no coinciden.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>