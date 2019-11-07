jQuery(document).ready(function($) {
	/************************ TRANSACTIONS ************************/
	var transaction = {
		current_month: null,
		current_year: null,
		active_page: 1,
		sum_rows: 0
	};
	
	function getTransaction() {
		$('#ys-overlay, .ys-spinner').addClass('show');
		var router = YSBackEnd.siteURL+'/wp-json/my_rest_server/v1/admin/transactions';
		
		transaction.current_month = parseInt($('#transactions-filters #months').val());
		transaction.current_year = parseInt($('#transactions-filters #years').val());
		transaction.sum_rows =  parseInt($('#transactions-filters #sum_rows').text());
		
		xhr(router, transaction, true).then(function (result) {
			$('#ys-overlay, .ys-spinner').removeClass('show');
			if(result.success) {
				$('#transactions-wrapper').html(result.data.html);
			};
		});	
	};

	$(document.body).on('change', '#transactions-filters #months', function () {
		transaction.active_page = 1;
		getTransaction();
	});

	$(document.body).on('change', '#transactions-filters #years', function () {
		transaction.active_page = 1;
		getTransaction();  
	});
	
	$(document.body).on('click', '#transactions #ys_pagination li', function () {
		transaction.active_page = parseInt(this.innerText) || 1;
		getTransaction();
	});



	/************************ SINGLE PRODUCT TABS ************************/
	$('#ys-product-description #new-tab').click(function (){


	});



});