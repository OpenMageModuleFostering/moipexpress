Validation.add('validar_cpf', 'O CPF ou CNPJ informado é inválido', function(v) {return validarCPF(v);});
Validation.add('validar_vencimento', 'A data de vencimento é inválida', function(v) {return validaVencimento(v);});
Validation.add('validar_dob', 'A data de nacimento é inválida', function(v) {return validaDob(v);});
Validation.add('validar_cvv', 'O seu CVV esta inválido', function(v) {return validaCVV(v);});
Validation.add('validate-cc-br', 'O número do cartão é inválido', function(v) {return validaCC(v);});

function validarCPF(cpf) {
	cpf = cpf.replace(/[^\d]+/g, '');
	if (cpf == '') return false;
	// Elimina CPFs invalidos conhecidos
	if (cpf.length != 11 || cpf == "00000000000" || cpf == "11111111111" || cpf == "22222222222" || cpf == "33333333333" || cpf == "44444444444" || cpf == "55555555555" || cpf == "66666666666" || cpf == "77777777777" || cpf == "88888888888" || cpf == "99999999999")
	return false;
	// Valida 1o digito
	add = 0;
	for (i = 0; i < 9; i++)      add += parseInt(cpf.charAt(i)) * (10 - i); rev = 11 - (add % 11);
	if (rev == 10 || rev == 11)    rev = 0;
	if (rev != parseInt(cpf.charAt(9)))
	return false;
	// Valida 2o digito
	add = 0;
	for (i = 0; i < 10; i++)       add += parseInt(cpf.charAt(i)) * (11 - i); rev = 11 - (add % 11);
	if (rev == 10 || rev == 11)    rev = 0;
	if (rev != parseInt(cpf.charAt(10)))
	return false;
	return true;
}

function validaVencimento(v){
	var value = v.split('/');
	var today = new Date();
	var month = today.getMonth()+1;
	var year = today.getFullYear();
	year = parseInt(year.toString().substring(2,4));
	if(parseInt(value[1]) < year)
	return false;
	if(parseInt(value[0]) < month && parseInt(value[1]) == year)
	return false;
	return true;
}

function validaDob(date){
	var dob = date.split('/');
	var currentDate = new Date();
	if(parseInt(dob[0]) > 31 || parseInt(dob[1]) > 12 || parseInt(dob[2]) > (currentDate.getFullYear()-10))
	return false;
	return true;
}

function validaCC(value){
	return CreditCard.validate(value);
}

function validaCVV(value){
	return Boolean($$('#moip-new-cc-form input[type=radio]:checked')[0].value != 'amex' | ($$('#moip-new-cc-form input[type=radio]:checked')[0].value == 'amex' & value.length==4))
}