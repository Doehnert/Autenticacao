define([
  "jquery",
  "jquery/ui",
  "jquery/validate",
  "mage/translate",
  "moment",
], function ($) {
  "use strict";

  return function (param) {
    $.validator.addMethod(
      "germiniPassword2",
      function (value, element) {
        let cpf = $("#cpf").val();
        let dateOfBirth = $("#nasc").val();
        let phoneNumber = $("#telephone").val();
        let phoneNumber2 = $("#telephone2").val();
        let item = value;

        if (/(?:(?=012|123|234|345|456|567|678|789)\d)+\d/.test(item)) {
          return false;
        }

        return true;
      },
      $.mage.__("Não é permitido ter 3 números sequenciais"),
    );
  };
});
