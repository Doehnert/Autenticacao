define([
  "jquery",
  "moment",
  "jquery/ui",
  "jquery/validate",
  "mage/translate",
], function ($, moment) {
  "use strict";

  return function (param) {
    $.validator.addMethod(
      "germiniPassword",
      function (value, element) {
        debugger;
        let cpf = $("#cpf").val();
        let dateOfBirth = $("#nasc").val();
        let phoneNumber = $("#telephone").val();
        let phoneNumber2 = $("#telephone2").val();
        let item = value;

        if (
          item.length === 6 &&
          ((dateOfBirth &&
            moment(dateOfBirth, "DD/MM/YYYY")
              .format("DDMMYY")
              .includes(item)) ||
            (cpf && cpf.replace(/\.|-|\/|\(|\)|\/| /g, "").includes(item)) ||
            (phoneNumber &&
              phoneNumber.replace(/\.|-|\/|\(|\)|\/| /g, "").includes(item)) ||
            (phoneNumber2 &&
              phoneNumber2.replace(/\.|-|\/|\(|\)|\/| /g, "").includes(item)))
        ) {
          return false;
        }

        return true;
      },
      $.mage.__(
        "Senha não pode estar contida em CPF/CNPJ, DATA DE NASCIMENTO, CEL OU TEL",
      ),
    );
  };
});
