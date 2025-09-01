$(function () {
  $("#filter-product").on("submit", function (e) {
    e.preventDefault();

    let min = $("#price-min").val();
    let max = $("#price-max").val();
    let code = $("#code").val();

    if ((min && isNaN(min)) || (max && isNaN(max)) || (code && isNaN(code))) {
      alert("Los campos de precio y código solo permiten números.");
      return;
    }

    min = parseFloat(min) || 0;
    max = parseFloat(max) || 0;

    let brand = $("#brand").val();
    let name = $("#name").val().toLowerCase();

    if (min > 0 && max > 0 && min > max) {
      alert("El precio mínimo no puede ser mayor que el máximo.");
      $("#price-min").val("");
      $("#price-max").val("");
      return;
    }

    let filtrados = productosOriginales.filter(p => {
      let cumple = true;
      if (brand && brand != "0") cumple = cumple && p.Marca.toLowerCase().trim() === brand.toLowerCase().trim();
      if (min) cumple = cumple && parseFloat(p.PrecioUnitario) >= min;
      if (max) cumple = cumple && parseFloat(p.PrecioUnitario) <= max;
      if (code) cumple = cumple && p.Id_Producto == code;
      if (name) cumple = cumple && p.NombreProducto.toLowerCase().includes(name);
      return cumple;
    });

    renderTabla(filtrados);
  });

  renderTabla(productosOriginales);
});
