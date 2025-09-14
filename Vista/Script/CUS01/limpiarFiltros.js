$(document).ready(function () {

    $("#btn-clear-filters").on("click", function () {
        
        $("#filter-product")[0].reset();
        renderTabla(productosOriginales);
        
    });
});