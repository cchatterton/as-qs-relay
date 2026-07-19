(function () {
  window.ASQR_QS_RELAY = window.ASQR_QS_RELAY || { version: 1, updated_at: "", touches: [] };

  document.dispatchEvent(
    new CustomEvent("asqr:ready", {
      detail: window.ASQR_QS_RELAY,
    })
  );
})();
