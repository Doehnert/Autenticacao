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

        return true
      },
      $.mage.__(
        'A senha deve ser numérica e ter no mínimo 8 caracteres e no máximo 20 caracteres',
      ),
    )
  }
})
