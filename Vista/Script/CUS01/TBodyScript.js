const minRows = 5;
const tbody = document.getElementById("table-body");
const tbody2 = document.getElementById("table-body2");
const currentRows = tbody.rows.length;
const currentRows2 = tbody2.rows.length;
if (currentRows < minRows) {
    for (let i = currentRows; i < minRows; i++) {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td colspan="5">&nbsp;</td>`;
        tbody.appendChild(tr);
    }
}
if (currentRows2 < minRows) {
    for (let i = currentRows2; i < minRows; i++) {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td colspan="5">&nbsp;</td>`;
        tbody2.appendChild(tr);
    }
}

