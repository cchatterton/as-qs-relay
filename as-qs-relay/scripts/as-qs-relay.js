(function () {
  window.ASQR_QS_RELAY = window.ASQR_QS_RELAY || {};

  document.dispatchEvent(
    new CustomEvent("asqr:ready", {
      detail: window.ASQR_QS_RELAY,
    })
  );
})();
