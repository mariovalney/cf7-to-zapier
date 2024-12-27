'use strict';

jQuery(document).ready(function($) {
  $( '.ctz-accordion-wrapper' ).each(function(index, el) {
    const trigger = $(el).find('.ctz-accordion-trigger');
    const content = $(el).find('.ctz-accordion-content');

    content.hide();

    trigger.on('click', function(event) {
      event.preventDefault();

      trigger.toggleClass('open');
      content.slideToggle(trigger.hasClass('open'));
    });
  });
});
