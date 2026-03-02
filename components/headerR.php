<header class="w-full bg-white shadow z-10">
                <!--logo y texto central-->
                <div class="flex items-center justify-between p-4 w-full">
                    <img src="../images/logo.png" alt="Global Care Digital" class="h-20 w-auto">
                    <h1 class="text-3xl font-semibold text-blue-900 uppercase tracking-tight">Portal RECEPCIONISTA</h1>
                    <!--perfil-->
                    <div class="flex items-center gap-4 relative">
                        <span class="text-l text-gray-600 font-medium"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                        <button onclick="toggleMenu()" id="btnPerfil" class="bg-slate-100 p-2 w-12 h-12 flex items-center justify-center rounded-full hover:bg-slate-200 transition-all shadow-sm cursor-pointer focus:outline-none">
                            <svg width="35" height="35" class="fill-current text-blue-900">
                                <use xlink:href="../assets/sprite.svg?v=4#icon-recep"></use>
                            </svg>
                        </button>
                        <div id="perfilMenu" class="hidden absolute right-0 top-14 w-40 bg-white border border-gray-200 shadow-xl rounded-lg py-2 z-50">
                            <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-semibold">Cerrar Sesion</a>
                        </div>
                    </div>
                    <script>
                    function toggleMenu() {
                        const menu = document.getElementById('perfilMenu');
                        menu.classList.toggle('hidden');
                    }

                    // Cerrar menú al clickear fuera
                    document.addEventListener('click', function(event) {
                        const menu = document.getElementById('perfilMenu');
                        const btn = document.getElementById('btnPerfil');
                        if (!btn.contains(event.target) && !menu.contains(event.target)) {
                            menu.classList.add('hidden');
                        }
                    });
                </script>
                </div>
            </header> 