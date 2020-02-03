function add_fee_to_grid(e, elm) {
	e = e || window.event;
    e.preventDefault();
    var element = jQuery(elm);
    var id = element.attr('data-fee_id');
    var get_tr = element.parents('tbody').first().find('tr');
    var count = parseInt(get_tr.length + 1);
    var miles_from_name = id +'['+ count + '][from_miles]';
    var miles_to_name = id +'['+ count + '][to_miles]';
    var fee_name = id +'['+ count + '][miles_fee]';
    var template = document.getElementById("template-add-fee-field");
    var templateHtml = template.innerHTML;
    var field_html = templateHtml.replace(/{{miles_from_name}}/g, miles_from_name)
    								.replace(/{{miles_to_name}}/g, miles_to_name)
    								.replace(/{{fee_name}}/g, fee_name)
    								.replace(/{{fee_id}}/g, id);
    element.parents('tbody').first().append(field_html);
}
function remove_fee_to_grid(e, elm) {
	e = e || window.event;
    e.preventDefault();
    var element = jQuery(elm);
    var get_tr = element.parents('tr').first();
    get_tr.remove();
}