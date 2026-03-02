<?php
session_start();
if(!isset($_SESSION['user_id']))
    exit;
$paciente = $_GET['nombre'] ?? 'Paciente no especificado';
$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receta Medica</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-white p-10 text-gray-800" onload="window.print()">
    <div class="border-b-4 border-blue-900 pb-6 mb-10 flex justify-between items-end">
        <div>
            <h1 class="text-4xl font-black text-blue-900 uppercase tracking-tighter">Global Care Digital</h1>
        </div>
        <div class="text-right text-xs text-gray-400">
            <p>Fecha: <?php echo date('d/m/Y'); ?></p>
        </div>
    </div>
    <div class="mb-8 bg-slate-50 p-4 rounded-xl border border-slate-100">
        <h2 class="text-xs uppercase font-black text-blue-800 mb-1">Paciente:</h2>
        <p class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($paciente); ?></p>
    </div>
    <div class="min-h-[300px]">
        <h2 class="text-xs uppercase font-black text-blue-800 mb-4 border-b pb-2">Receta:</h2>
        <div class="text-lg leading-relaxed text-gray-700 italic">
            <?php echo nl2br(htmlspecialchars($mensaje)); ?>
        </div>
    </div>
    <div class="mt-32 flex justify-end">
        <div class="w-72 border-t border-gray-400 pt-4 text-center">
            <p class="text-[10px] text-gray-400 uppercase trackng-widest mt-1">Firma Medico</p>
        </div>
    </div>
</body>
</html>