<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Global Care Ingreso</title>
</head>
<body>
    <div class="flex justify-center items-center h-screen bg-gradient-to-br from-blue-100 via-blue-300 to-blue-500">
        <div class="w-96 p-6 shadow-lg bg-white rounded-md">
            <div class="flex flex-col items-center mb-4"> 
                <img src="./images/logo.png" alt="Global Care Digital" class="h-20 w-auto mb-2">
                <h1 class="text-3xl font-semibold">Ingresar</h1>
            </div>
            <form action="api/auth.php" method="POST" autocomplete="off">
                <div class="mt-3">
                    <label for="username" class="block text-base mb-2">Usuario</label>
                    <input type="email" name="email" id="username" class="border w-full px-2 py-1 focus:outline-none focus:border-gray-600" placeholder="Ingresa tu correo" required />
                </div>
                <div class="mt-3">
                    <label for="password" class="block text-base mb-2">Contraseña</label>
                    <input type="password" name="password" id="password" class="border w-full px-2 py-1 focus:outline-none focus:border-gray-600" placeholder="Ingresar contraseña" required />
                </div>
                <div class="mt-5">
                    <button type="submit" class="border-2 border-indigo-500 bg-indigo-500 text-white py-1 w-full rounded-md hover:bg-indigo-600 transition">Ingresar</button>
                </div>
            </form>
            <div class="mt-6 text-center">
                <button type="button" onclick="cambioPassword()" class="text-indigo-500 text-sm font-semibold hover:underline cursor-pointer">¿No puedes acceder a tu cuenta?</button>  
            </div>
        </div>
    </div>
    <script>
        function cambioPassword(){
            window.location.href = "login.php?mensaje=olvido_credenciales";
        }
    </script>
    <script src="scripts.js?v=<?= time() ?>"></script>
    <script src="modales.js?v=<?= time() ?>"></script>
</body>
</html>