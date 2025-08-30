function actualizarHora() {
    const ahora = new Date();
    let horas = ahora.getHours().toString().padStart(2, "0");
    let minutos = ahora.getMinutes().toString().padStart(2, "0");
    let segundos = ahora.getSeconds().toString().padStart(2, "0");

    document.getElementById("hora").value = `${horas}:${minutos}:${segundos}`;
}
setInterval(actualizarHora, 1000);
actualizarHora();

