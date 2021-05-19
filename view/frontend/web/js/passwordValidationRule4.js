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
      "germiniPassword4",
      function (value, element) {
        let cpf = $("#cpf").val();
        let dateOfBirth = $("#nasc").val();
        let phoneNumber = $("#telephone").val();
        let phoneNumber2 = $("#telephone2").val();
        let item = value;

        if (!(Number(item, 10) && item.length === 6)) {
          return false;
        }

        return true;
      },
      $.mage.__("A senha deve ser numérica e ter 6 caracteres"),
    );
  };
});
