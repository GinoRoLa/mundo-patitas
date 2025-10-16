document.addEventListener('change', function(e) {
    if(e.target.classList.contains('chkReprogramar')) {
        const checkboxes = document.querySelectorAll('.chkReprogramar');
        const form = document.getElementById('formReprogramacion');

        if(e.target.checked) {
            checkboxes.forEach(c => { if(c !== e.target) c.disabled = true; });

            form.hidden = false;
            document.getElementById('idPedidoRep').value = e.target.dataset.idpedido;
            document.getElementById('idclienteRep').value = e.target.dataset.idcliente;
            document.getElementById('fechaConsolRep').value = e.target.dataset.fecha;

        } else {
            checkboxes.forEach(c => c.disabled = false);
            form.hidden = true;
            document.getElementById('obsRep').value = '';
        }
    }
});

