jQuery(document).ready(function($) {
  function generateShortcode() {
    const title = $('#paywall-title').val();
    const description = $('#paywall-description').val();
    const buttonText = $('#paywall-button-text').val();
    const price = $('#paywall-price').val();
    const currency = $('#paywall-currency').val();
    const duration = $('#paywall-duration').val();
    const theme = $('#paywall-theme').val();

    let shortcode = '[paywall_payment';

    if (title) shortcode += ` title="${title}"`;
    if (description) shortcode += ` description="${description}"`;
    if (buttonText) shortcode += ` button_text="${buttonText}"`;
    if (price) shortcode += ` price="${price}"`;
    if (currency) shortcode += ` currency="${currency}"`;
    if (duration) shortcode += ` duration="${duration}"`;
    if (theme) shortcode += ` theme="${theme}"`;

    shortcode += ']';

    $('#shortcode-output').text(shortcode);
  }

  $('.shortcode-input').on('input change', generateShortcode);
  $('#generate-shortcode').on('click', generateShortcode);

  $('#copy-shortcode').on('click', function() {
    const shortcodeText = $('#shortcode-output').text();
    navigator.clipboard.writeText(shortcodeText).then(function() {
      const $button = $('#copy-shortcode');
      $button.text('Copied!');
      setTimeout(function() {
        $button.text('Copy Shortcode');
      }, 2000);
    });
  });

  // Function to update shortcode preview
  function updateShortcodePreview() {
    const id = $('#shortcode-id').val();
    const shortcode = id
      ? `[paywall_payment id="${id}"]`
      : `[paywall_payment title="${$('#paywall-title').val()}" description="${$('#paywall-description').val()}" button_text="${$('#paywall-button-text').val()}" price="${$('#paywall-price').val()}" currency="${$('#paywall-currency').val()}" duration="${$('#paywall-duration').val()}" theme="${$('#paywall-theme').val()}"]`;

    $('#shortcode-output').text(shortcode);
  }

  // Initialize form handlers
  $('.shortcode-input').on('input change', updateShortcodePreview);

  // Copy shortcode button
  $('#copy-shortcode').on('click', function() {
    const shortcode = $('#shortcode-output').text();
    navigator.clipboard.writeText(shortcode).then(function() {
      $(this).text('Copied!');
      setTimeout(() => $(this).text('Copy Shortcode'), 2000);
    });
  });

  // Save shortcode
  $('#save-shortcode').on('click', function() {
    const data = {
      action: 'save_paywall_shortcode',
      nonce: $('#coinsnap_paywall_nonce').val(),
      id: $('#shortcode-id').val(),
      name: $('#shortcode-name').val(),
      title: $('#paywall-title').val(),
      description: $('#paywall-description').val(),
      button_text: $('#paywall-button-text').val(),
      price: $('#paywall-price').val(),
      currency: $('#paywall-currency').val(),
      duration: $('#paywall-duration').val(),
      theme: $('#paywall-theme').val()
    };

    $.post(ajaxurl, data, function(response) {
      if (response.success) {
        alert('Shortcode saved successfully!');
        location.reload(); // Reload to show updated list
      } else {
        alert('Error saving shortcode');
      }
    });
  });

  // Edit shortcode
  $('.edit-shortcode').on('click', function() {
    const id = $(this).data('id');

    $.post(ajaxurl, {
      action: 'get_paywall_shortcode',
      nonce: $('#coinsnap_paywall_nonce').val(),
      id: id
    }, function(response) {
      if (response.success) {
        const shortcode = response.data;

        $('#shortcode-id').val(shortcode.id);
        $('#shortcode-name').val(shortcode.name);
        $('#paywall-title').val(shortcode.title);
        $('#paywall-description').val(shortcode.description);
        $('#paywall-button-text').val(shortcode.button_text);
        $('#paywall-price').val(shortcode.price);
        $('#paywall-currency').val(shortcode.currency);
        $('#paywall-duration').val(shortcode.duration);
        $('#paywall-theme').val(shortcode.theme);

        updateShortcodePreview();

        // Scroll to form
        $('html, body').animate({
          scrollTop: $('.shortcode-form').offset().top
        }, 500);
      }
    });
  });

  // Delete shortcode
  $('.delete-shortcode').on('click', function() {
    if (!confirm('Are you sure you want to delete this shortcode?')) {
      return;
    }

    const id = $(this).data('id');

    $.post(ajaxurl, {
      action: 'delete_paywall_shortcode',
      nonce: $('#coinsnap_paywall_nonce').val(),
      id: id
    }, function(response) {
      if (response.success) {
        location.reload();
      } else {
        alert('Error deleting shortcode');
      }
    });
  });

  // Reset form
  $('#reset-form').on('click', function() {
    $('#shortcode-id').val('');
    $('.shortcode-form form')[0].reset();
    updateShortcodePreview();
  });
});

(function () {
  // Wait until the DOM is fully loaded
  document.addEventListener('DOMContentLoaded', function () {
    const providerSelector = document.querySelector('#provider');
    const coinsnapWrapper = document.getElementById('coinsnap-settings-wrapper');
    const btcpayWrapper = document.getElementById('btcpay-settings-wrapper');

    // Function to toggle visibility based on selected provider
    function toggleProviderSettings() {
      const selectedProvider = providerSelector.value;
      coinsnapWrapper.style.display = selectedProvider === 'coinsnap' ? 'block' : 'none';
      btcpayWrapper.style.display = selectedProvider === 'btcpay' ? 'block' : 'none';
    }

    // Initial toggle on page load
    toggleProviderSettings();

    // Listen for changes to the provider dropdown
    providerSelector.addEventListener('change', toggleProviderSettings);
  });
})();
