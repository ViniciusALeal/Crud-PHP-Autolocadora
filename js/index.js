document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.getElementById('toggleFiltro');
    const filtroDiv = document.getElementById('filtroColunas');

    if (toggleBtn && filtroDiv) {
        toggleBtn.addEventListener('click', () => {
            const aberto = filtroDiv.style.display === 'block';
            filtroDiv.style.display = aberto ? 'none' : 'block';
            toggleBtn.textContent = aberto ? '▼' : '▲';
            toggleBtn.setAttribute('aria-expanded', String(!aberto));
        });

        if (filtroDiv.querySelector('input[type="checkbox"]:checked')) {
            filtroDiv.style.display = 'block';
            toggleBtn.textContent = '▲';
            toggleBtn.setAttribute('aria-expanded', 'true');
        }
    }

    const btnContato = document.getElementById('btnContato');
    if (btnContato) {
        btnContato.addEventListener('click', () => {
            if (window.confirm("Contato é direcionado a relatar erros do sistema.\nDeseja entrar em contato?")) {
                window.open("https://github.com/ViniciusALeal/Crud-PHP-Autolocadora/issues", "_blank");
            }
        });
    }
});
