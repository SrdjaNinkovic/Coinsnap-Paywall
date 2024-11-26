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
