define([
  'jquery',
  'jquery/ui',
  'jquery/validate',
  'mage/translate',
  'moment',
], function ($) {
  'use strict'

  return function (param) {
    $.validator.addMethod(
      'germiniPassword4',
      function (value, element) {
        let item = value

        if (item.length < 8 || item.length > 20) {
          return false
        }

        var paswd =
          /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/

        if (!paswd.test(item)) {
          return false
        }

        return true
      },
      $.mage.__(
        'No mínimo 8 caracteres Conter letras, números e caracteres especiais',
      ),
    )
  }
})
