sessionStorage.removeItem("wc_cart_created"); //refresh mini cart 

jQuery(document).ready(function($) {


	
	$(document.body ).on( 'wc_fragments_refreshed', function() {
			$(document.body ).trigger( 'wc_fragments_loaded' );
	});

		/************************ SECTION COMPANY ************************/
	$('#toggle-company-data').change(function (){
		$('#container-company-data').toggle('slow');
	});

	$('#dds').change(function (e){
		$('#vatnumber').toggle('slow');
	});


		/************************ MY ACCCOUT PAGE ************************/
		function redirectUserAddress(e){
			e.preventDefault();
			e.stopPropagation();
			var value = $('.woocommerce .edit-account #delivery_address option:selected').val();
			var url = $(e.currentTarget).attr('href')+"/"+value;
			window.location.replace(url);
		}

		$(document.body).on('click', '.woocommerce .edit-account #edit_address', redirectUserAddress);
		$(document.body).on('click', '.woocommerce .edit-account #delete_address', function (e){
			var action = confirm("Сигурни ли сте, че желаете да изтриете този адрес?");
			if(action == true) {
				redirectUserAddress(e);
			}
		});


	/************************ MINI CART ************************/
	$(document.body ).on('click', '.cart-head', function (e){
		window.location.replace(wc_add_to_cart_params.cart_url);	
	});


	/************************ FISCAL MODAL WINDOW ************************/
	function FiscalModal(){
		var self = this;
		this.currentProduct = null;
		this.input = {};
		complete = null;


		this.open = function (){
			var router = YSFrontEnd.siteURL+'/wp-json/my_rest_server/v1/modal/fiscal';
			xhr(router, this.input, true).then(function (result) {
				if(result.success) {
					$('body').append(result.data);
					$('#ys-modal').addClass('show');	
				};
			});	
		}

		this.submit = function (e) {
			e.preventDefault();
			e.stopPropagation();

			var spiner = $('#ys-modal .ys-spinner').addClass('show');
			$('#modal-form .ys-modal-row .error').empty().removeClass('show');
	
			var router = YSFrontEnd.siteURL+'/wp-json/my_rest_server/v1/modal/fiscal/submit';
			var formData = {
				'productID': self.input.productID,
				'company': $('textarea[name=company]').val(),
				'company_address': $('input[name=company_address]').val(),
				'object_name': $('input[name=object_name]').val(),
				'object_address': $('input[name=object_address]').val(),
				'business_type': $('input[name=business_type]').val(),
				'tax_service': $('input[name=tax_service]').val(),
				'tax_region': $('input[name=tax_region]').val(),
				'bulstat': $('input[name=bulstat]').val(),
				'dds': $('select[name=dds]').val(),
				'mol': $('input[name=mol]').val(),
				'phone': $('input[name=phone]').val(),
				'email': $('input[name=email]').val(),
			}
		
			xhr(router, formData, true).then(function (result) {
				spiner.removeClass('show');
				if(!result.success) {
					var i;
					for(i in result.data) {
						$('#modal-form #'+i+' .ys-modal-row .error').text(result.data[i]).addClass('show');
					};
					$('#modal-form .error.show').first()[0].scrollIntoView();
				} else {
					$('#ys-modal').remove();
					self.complete();
				};
			});
		}
	};

	FiscalModal.prototype.catalog = function(e){
		e.preventDefault();
		e.stopImmediatePropagation();
		this.currentProduct = $(e.currentTarget);
		this.input = {
			productID: parseInt(this.currentProduct.attr('data-product_id')),
		};

		this.complete = function () {			
			this.currentProduct.removeClass('ys-fiscal');
			this.currentProduct.addClass("add_to_cart_button ajax_add_to_cart");
			this.currentProduct.trigger('click');
		}

		this.open();
		return false;
	}

	FiscalModal.prototype.singleProduct = function(element){
		this.currentProduct = $(element);
		this.input = {
			productID: parseInt(this.currentProduct.val()),
		};

		this.complete = function () {
			$(document.body).off('click', '.single_add_to_cart_button', singleAddToCartBtn);
			this.currentProduct.trigger('click');
		}

		this.open();	
	}

	var fiscalModal = null;
	$(document.body).on('click', '.ys-fiscal', function (e){
		fiscalModal = new FiscalModal();
		fiscalModal.catalog(e);
	});

	$(document.body).on('submit', '#fiscal-product #modal-form', function (e){
		fiscalModal.submit(e);
	});

	$(document).on( 'added_to_cart', 'body', function(e) {
		if(fiscalModal) {
			fiscalModal.currentProduct.removeClass("add_to_cart_button ajax_add_to_cart");
			fiscalModal.currentProduct.addClass('ys-fiscal');
			fiscalModal = null;
		};
	});



	/************************ PACKAGE PRODUCT ************************/
	function PackageProduct() {
		this.currentProduct = null;
		this.package = null;
		this.input = {};
		this.complete = null;
		this.additions = {};
		this.totalPrice = 0.00;
		var self = this;
		
		this.getPrice = function () {
			self.totalPrice = parseFloat($('.product-summary-wrap .price .woocommerce-Price-amount')[0].childNodes[0].nodeValue);
		}

		this.setPrice = function () {
			$('.product-summary-wrap .price .woocommerce-Price-amount')[0].childNodes[0].nodeValue = this.totalPrice.toFixed(2);
		}

		this.changePackage = function (e) {
			self.package = $(this);
			self.totalPrice = parseFloat(self.package.attr('data-price'));
			var i;
			for(i in self.additions) {
				self.totalPrice += self.additions[i];
			};
			self.setPrice();
		};

		this.toggleAdditions = function(e) {
			self.getPrice();

			if(this.checked) {
				self.additions[this.name] = parseFloat(this.value);
				self.totalPrice += self.additions[this.name];
			}
			else {
				delete(self.additions[this.name]);
				self.totalPrice -= parseFloat(this.value);
			}

			var additionsToString = Object.keys(self.additions).toString();
			$('form.cart input[name="ys_additions"]').val(additionsToString);
			self.setPrice();
		};
	};

	var packageProduct = new PackageProduct();
//	$(document.body).on('change', '#ys-package-product #products-drop-down', packageProduct.changePackage);
	$(document.body).on('change', '#ys-package-product input[name=products-list]', packageProduct.changePackage);
	$(document.body).on('change', '#ys-package-product #modifier input[type="checkbox"] ', packageProduct.toggleAdditions);




	/************************ CLOTHES PRODUCT ************************/
	$('#ys-sizes-list input[name="size"]').change(function (e) {

		var li = $(this).parents('li');
		var ulID = li.attr('id');

		if($('#ys-color-list ul#'+ulID).children().length > 0) {
			$('#ys-color-list').addClass('active');
		} 
		else {
			$('#ys-color-list').removeClass('active');
		}
		
		
		$("#ys-sizes-list li, #ys-color-list ul, #ys-color-list li").removeClass('active');
		$('#ys-color-list input[name="color"]').prop('checked', false);


		if($(this).is(':checked')) {
			li.addClass('active');
			$('#ys-color-list #'+ulID).addClass('active');
		}
		else  {
			li.removeClass('active'); 
		}	
	});

	$('#ys-color-list input[name="color"]').change(function (e){
		$("#ys-color-list li").removeClass('active');

		var li = $(this).parents('li');
		if($(this).is(':checked')) {
			li.addClass('active');
		}
		else  {
			li.removeClass('active'); 
		}	
	});



(function ($) {
	$.fn.serializeFormJSON = function () {
		var o = {};
		var a = this.serializeArray();
		$.each(a, function () {
		if (o[this.name]) {
			if (!o[this.name].push) {
				o[this.name] = [o[this.name]];
			}
			o[this.name].push(this.value || '');
		} else {
				o[this.name] = this.value || '';
			}
	});
	return o;
	};
})(jQuery);




	/************************ SINGLE ADD TO CART BUTTON ************************/
	function singleAddToCartBtn(e) {
		var form = $(this).parents('form.cart').serializeFormJSON();
		if(form.hasOwnProperty('ys_fiscal_product')) {
			e.preventDefault();
			e.stopPropagation();
			fiscalModal = new FiscalModal(); 
			return fiscalModal.singleProduct(this);
		}
		else if(form.hasOwnProperty('ys_clothes_product')) {
			if(!form.hasOwnProperty('size')) {
				e.preventDefault();
				e.stopPropagation();
				alert('Моля, изберете размер!');
			}
			else {
				var ulID = form['size'];
				if(!form.hasOwnProperty('color') && $('#ys-color-list ul#'+ulID).children().length > 0) {
					e.preventDefault();
					e.stopPropagation();
					alert('Моля, изберете цвят!');
				}
			}
		}

	};

	$(document.body).on('click', '.single_add_to_cart_button', singleAddToCartBtn);




	/************************ CHECKOUT PAGE ************************/
	$('form[name="checkout"] #billing_address').change(function (e) {
		if(this.value === 'custom') {
			$('form[name="checkout"] .ys_address').removeClass('hide');
		} else {
			$('form[name="checkout"] .ys_address').addClass('hide');
		} 
	});

});