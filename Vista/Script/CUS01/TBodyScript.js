const minRows = 5;
const tbodies = ["table-body", "table-body2", "table-body3"];

tbodies.forEach(id => {
    const tbody = document.getElementById(id);
    if (tbody) {
        const table = tbody.closest("table");
        const colCount = table ? table.querySelectorAll("thead tr th").length : 1;
        const currentRows = tbody.rows.length;
        for (let i = currentRows; i < minRows; i++) {
            const tr = document.createElement("tr");
            tr.innerHTML = `<td colspan="${colCount}">&nbsp;</td>`;
            tbody.appendChild(tr);
        }
    }
});
