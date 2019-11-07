var xhr; //GLOBAL VARIABLE	
jQuery(document).ready(function($) {
   xhr = function (router, data, json_format) {
		var input = {
			type: 'POST',
			url:  router,
			data: (json_format)? JSON.stringify(data) : data,
		};

		if(json_format) {
			input.contentType = "application/json; charset=utf-8";
		}
		return $.ajax(input);
	};

	$(document.body).on('click', '#ys-modal #close',  function (e){
		$('#ys-modal').remove();
	});
	
});