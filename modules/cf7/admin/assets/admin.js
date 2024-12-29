'use strict';

jQuery(document).ready(function($) {
  // Modal
  const Confirm = function(message, onContinue, onCancel) {
    $('#ctz-webhook-confirmation').remove();
    $('#ctz-webhook-confirmation-overlay').remove();


    const is_link = typeof onContinue !== 'function';

    const template = $('<div id="ctz-webhook-confirmation"><p class="ctz-webhook-confirmation-message"></p><div class="ctz-webhook-confirmation-buttons"><a href="#" class="btn-cancel"></a><a href="#" class="btn-confirm"></a></div></div>');
    template.find('.ctz-webhook-confirmation-message').html(message);
    template.find('.btn-cancel').html( is_link ? CTZ_ADMIN.messages.btn_no : CTZ_ADMIN.messages.btn_cancel );
    template.find('.btn-confirm').html( is_link ? CTZ_ADMIN.messages.btn_yes : CTZ_ADMIN.messages.btn_continue );

    $('body').append(template).append($('<div id="ctz-webhook-confirmation-overlay"></div>'));
    $('body').addClass('ctz-webhook-confirmation-opened');

    if ( is_link ) {
      template.find('.btn-confirm').attr('href', onContinue).attr('target', '_blank');
    }

    $('#ctz-webhook-confirmation').fadeIn(500);
    $('#ctz-webhook-confirmation-overlay').fadeIn(200);

    template.find('.btn-cancel').on('click', function(event) {
      event.preventDefault();

      $('#ctz-webhook-confirmation').fadeOut();
      $('#ctz-webhook-confirmation-overlay').fadeOut();
      $('body').removeClass('ctz-webhook-confirmation-opened');

      if (onCancel) {
        onCancel();
      }
    });

    template.find('.btn-confirm').on('click', function(event) {
      if (! is_link) {
        event.preventDefault();
      }

      $('#ctz-webhook-confirmation').fadeOut();
      $('#ctz-webhook-confirmation-overlay').fadeOut();
      $('body').removeClass('ctz-webhook-confirmation-opened');

      if ( ! is_link) {
        onContinue();
      }
    });
  }

  // Select 2
  const select = $( '.ctz-template-select' );
  const templates = {};

  $.getJSON(CTZ_ADMIN.templates_url, function(response, textStatus, xhr) {
    let template_id = 0;
    const hasGroups = Object.keys(response).length > 1;

    for (let key in response) {
      const data = response[key];
      const group = hasGroups ? $('<optgroup>', { label: ( CTZ_ADMIN.groups[ key ] || key ) }) : null;

      for (var i = 0; i < data.length; i++) {
        const value = `template_${template_id}`;

        templates[ value ] = data[i];
        (group || select).append( $('<option>', { value: value, text: data[i].name }) );

        template_id++;
      }

      if (group) {
        select.append(group);
      }
    }
  });

  select.select2({ placeholder: CTZ_ADMIN.messages.choose_template, minimumResultsForSearch: 5 }).on('change', function(event) {
    event.preventDefault();

    const value = $(this).val();
    const template = templates[ value ] || null;

    if (! template) {
      return;
    }

    const has_headers = (template.headers || []).length;

    Confirm(
      ( has_headers ? CTZ_ADMIN.messages.confirm_all : CTZ_ADMIN.messages.confirm_body ),
      function() {
        console.log('triggered');

        $('#ctz-webhook-special_mail_tags').html('').trigger('change');
        $('#ctz-webhook-custom_headers').html( has_headers ? template.headers.join('\n') : '' ).trigger('change');

        const fields = (CTZ_ADMIN_TAGS || []).map((tag) => `[${tag}]`).join(template.separator || ' | ');
        $('#ctz-webhook-custom_body').html(template.body.replace('__VALUES__', fields)).trigger('change');

        if (! (template?.docs || false)) {
          return;
        }

        Confirm(CTZ_ADMIN.messages.open_docs, template.docs);
      },
      function() {
        select.val(null).trigger('change');
      }
    );
  });

  // Accordion
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

  // Preview
  $('#ctz-webhook-special_mail_tags, #ctz-webhook-custom_body').on('change', function(event) {
    $('#ctz-webhook-preview').html(CTZ_ADMIN.messages.save_to_preview);
  });
});
