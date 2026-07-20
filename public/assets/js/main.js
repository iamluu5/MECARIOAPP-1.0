document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('submit', (event) => {
      const message = element.getAttribute('data-confirm') || '¿Desea continuar?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });

  document.querySelectorAll('.flash-close').forEach((button) => {
    button.addEventListener('click', () => {
      const flash = button.closest('.flash');
      if (flash) flash.remove();
    });
  });
});
