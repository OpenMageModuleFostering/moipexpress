Element.prototype.triggerEvent = function(eventName)
{
    if (document.createEvent)
    {
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent(eventName, true, true);

        return this.dispatchEvent(evt);
    }

    if (this.fireEvent)
        return this.fireEvent('on' + eventName);
}

Ajax.Responders.register({
	onComplete: ajaxActions
});

function ajaxActions() {
	doMasks();
	doTabs();
	doBandeiras();
	doSafe();
}
function adjustStreetLines(){
	if($$('input[name="billing[street][]"]').length == 4 && $$('input[name="billing[street][]"] label').length < 4){
		var counter = 0;
		$$('input[name="billing[street][]"]').each(function(element){
			element.up(1).removeClassName('wide').addClassName('field');
			switch(counter){
				case 0:
					element.up(1).down('label').innerHTML = '<em>*</em>Rua';
				break;
				case 1:
					element.addClassName('required-entry');
					element.up(1).innerHTML = '<label for="billing:street2" class="required"><em>*</em>Número</label>'+element.up(1).innerHTML;
				break;
				case 2:
					element.up(1).innerHTML = '<label for="billing:street3" >Complemento</label>'+element.up(1).innerHTML;
				break;
				case 3:
					element.addClassName('required-entry');
					element.up(1).innerHTML = '<label for="billing:street4" class="required"><em>*</em>Bairro</label>'+element.up(1).innerHTML;
				break;
			}
			counter++;
		});
	}
}

function validateCVV(element) {
	if (element.value.length >= 3) {
		$$('#payment_form_moip li.hide').each(function(item) {
			item.slideDown();
		});
	}
}

function doSafe() {
	if ($('moip-safe')) {
		if ($('moip-safe').value == 'new') {
			$('moip-safe-cvv-parent').hide();
			$('moip-safe-parcelas-parent').hide();
			$('moip-new-cc-form').show();
			$('moip-new-cc-form').select('input.required').each(function(element) {
				element.addClassName('required-entry');
			});
			$('moip-safe-form').select('input.required').each(function(element) {
				element.removeClassName('required-entry');
			});
		} else {
			$('bandeira-safe').value = $$('#moip-safe option:checked')[0].readAttribute('data-flag');
			$('moip-safe-cvv-parent').show();
			$('moip-safe-parcelas-parent').show();
			$('moip-new-cc-form').hide();
			$('moip-new-cc-form').select('input.required').each(function(element) {
				element.removeClassName('required-entry');
			});
			$('moip-safe-form').select('input.required').each(function(element) {
				element.addClassName('required-entry');
			});
		}
		$('moip-safe').observe('change', function(event) {
			if ($('moip-safe').value == 'new') {
				$('moip-safe-cvv-parent').hide();
				$('moip-safe-parcelas-parent').hide();
				$('moip-new-cc-form').show();
				$('moip-new-cc-form').select('input.required').each(function(element) {
					element.addClassName('required-entry');
				});
				$('moip-safe-form').select('input.required').each(function(element) {
					element.removeClassName('required-entry');
				});

			} else {
				$('bandeira-safe').value = $$('#moip-safe option:checked')[0].readAttribute('data-flag');

				switch($$('#moip-safe option:checked')[0].readAttribute('data-flag')){
					case 'AmericanExpress':
						var bandeira = 'amex';
					break;
					case 'Diners':
						var bandeira = 'diners';
					break;
					case 'Mastercard':
						var bandeira = 'master';
					break;
					case 'Hipercard':
						var bandeira = 'hiper';
					break;
					case 'Visa':
						var bandeira = 'visa';
					break;
				}


				$$('input[name="payment[bandeira]"][value="'+bandeira+'"]')[0].triggerEvent('click');
				$('moip-safe-cvv-parent').show();
				$('moip-safe-parcelas-parent').show();
				$('moip-new-cc-form').hide();
				$('moip-new-cc-form').select('input.required').each(function(element) {
					element.removeClassName('required-entry');
				});
				$('moip-safe-form').select('input.required').each(function(element) {
					element.addClassName('required-entry');
				});
			}
		});
	}
}

function doBandeiras() {
	$$('ul.form-list .wide.bandeira > label').each(function(element) {
		element.observe('click', function(event) {
			var obj = this;
			if (this.up(0).select('.active')[0]) {
				this.up(0).select('.active')[0].removeClassName('active');
			}
			this.select('img')[0].addClassName('active');
			this.select('input')[0].click();
		});
	});
}
var cc, validate, cc_holder_cpf, cc_holder_dob, cc_holder_phone, billing_phone;

function doMasks() {
	if (cc === undefined || cc.elements.length === 0) {
		cc = new MaskedInput('[id=moip_cc_number]', '9999 9999 9999 99?99 999');
		validade = new MaskedInput('[id=moip_validade]', '99/99');
		cc_holder_cpf = new MaskedInput('[id=moip_cc_holder_cpf]', '999.999.999-99');
		cc_holder_dob = new MaskedInput('[id=moip_cc_holder_dob]', '99/99/9999');
		cc_holder_phone = new MaskedInput('[id=moip_cc_holder_phone]', '(99)9999-9999?9');
	}
	if (billing_phone === undefined || billing_phone.elements.length === 0) {
		billing_phone = new MaskedInput('[id="billing:telephone"]', '(99)9999-9999?9');
	}
}

function selectCCType(input) {
	var value = input.value.replace(new RegExp('_', 'g'),'');

	if (CreditCard.validate(value)) {
		var inputNew = $$('.wide.bandeira input[value=' + CreditCard.type(value) + ']')[0];
		inputNew.click();
		if (inputNew.up(0).select('img')[0] != $$('.wide.bandeira img.active')[0]) {
			if ($$('.wide.bandeira img.active')[0]) {
				$$('.wide.bandeira img.active')[0].removeClassName('active');
			}
			$$('.wide.bandeira input[value=' + CreditCard.type(value) + ']')[0].up(0).select('img')[0].addClassName('active');
		}
	}
}

function doTabs() {
	$$('#payment_form_moip .col-left > span').each(function(element) {
		if (!element.hasClassName('injected')) {
			element.addClassName('injected')
			element.observe('click', function(event) {
				//Button
				var button = this;
				$$('#payment_form_moip .col-left > span').each(function(element) {
					if (button != element) {
						element.removeClassName('active');
					}
				});
				button.addClassName('active');
				//Container
				var target = button.readAttribute('data-target');
				var targetElement = $$('[data-name=' + target + ']')[0];
				var currentTab = $$('.moip-payment-method.active')[0];
				$('moip_moip_method').value = target;
				if (targetElement != currentTab) {
					currentTab.removeClassName('active');
					currentTab.slideUp();
					currentTab.select('input.required').each(function(element) {
						element.removeClassName('required-entry');
					});
					targetElement.addClassName('active').slideDown();
					targetElement.select('input.required').each(function(element) {
						element.addClassName('required-entry');
					});
				}
			});
		}
	});
}

window.onload = function(){
	adjustStreetLines();
	billing_phone = new MaskedInput('[id="billing:telephone"]', '(99)9999-9999?9');
	doSafe();
}