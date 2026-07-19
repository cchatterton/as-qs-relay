(function () {
  const list = document.querySelector("[data-asqr-key-list]");
  const addButton = document.querySelector("[data-asqr-add-key]");
  const template = document.querySelector("[data-asqr-key-template]");

  if (!list || !addButton || !template) {
    return;
  }

  function bindRemove(button) {
    button.addEventListener("click", () => {
      const rows = list.querySelectorAll("tr");
      const row = button.closest("tr");

      if (rows.length <= 1) {
        const input = row ? row.querySelector("input") : null;
        if (input) {
          input.value = "";
        }
        return;
      }

      if (row) {
        row.remove();
      }
    });
  }

  addButton.addEventListener("click", () => {
    const fragment = template.content.cloneNode(true);
    const row = fragment.querySelector("tr");
    const remove = fragment.querySelector("[data-asqr-remove-key]");

    if (remove) {
      bindRemove(remove);
    }

    list.appendChild(fragment);

    if (row) {
      const input = row.querySelector("input");
      if (input) {
        input.focus();
      }
    }
  });

  list.querySelectorAll("[data-asqr-remove-key]").forEach(bindRemove);
})();
