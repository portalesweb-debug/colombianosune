(function (Drupal, once) {
  Drupal.behaviors.accessibilityControls = {
    attach(context) {
      once('a11y-controls', 'body', context).forEach(() => {
        // No ejecutar en el admin de Drupal
        if (document.body.classList.contains('path-admin')) {
          return;
        }

        /* ===============================
         * Create toolbar (si no existe)
         * =============================== */
        let toolbar = document.getElementById('a11y-toolbar');
        if (!toolbar) {
          toolbar = document.createElement('div');
          toolbar.id = 'a11y-toolbar';
          toolbar.innerHTML = `
            <button id="a11y-increase" aria-label="Aumentar tamaño del texto">A+</button>
            <button id="a11y-decrease" aria-label="Disminuir tamaño del texto">A−</button>
            <button id="a11y-contrast" aria-label="Activar alto contraste" aria-pressed="false">◐</button>
            <button id="a11y-reset" aria-label="Restablecer accesibilidad">⟳</button>
          `;
          document.body.appendChild(toolbar);
        }

        /* ===============================
         * Helper: ensure wrapper real
         * - envuelve TODO el contenido visible
         * - deja toolbar fuera
         * - evita mover SCRIPT/STYLE
         * - coloca el wrapper antes del toolbar
         * =============================== */
        function ensureA11yWrapper() {
          let wrapper = document.getElementById('a11y-page-wrapper');
          if (wrapper) {
            return wrapper;
          }

          wrapper = document.createElement('div');
          wrapper.id = 'a11y-page-wrapper';

          // IMPORTANTE: usar childNodes para no dejar nada "suelto"
          const nodes = Array.from(document.body.childNodes);

          nodes.forEach((node) => {
            // Dejar el toolbar fuera
            if (node.nodeType === 1 && node.id === 'a11y-toolbar') {
              return;
            }

            // No mover scripts/estilos (suelen estar al final del body)
            if (node.nodeType === 1) {
              const tag = node.tagName;
              if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'LINK') {
                return;
              }

              // No mover modales / overlays
              if (
                node.classList.contains('ui-dialog') ||
                node.classList.contains('drupal-modal')
              ) {
                return;
              }
            }

            wrapper.appendChild(node);
          });

          // Insertar el wrapper antes del toolbar para mantener el orden visual
          const tb = document.getElementById('a11y-toolbar');
          if (tb && tb.parentNode === document.body) {
            document.body.insertBefore(wrapper, tb);
          } else {
            document.body.appendChild(wrapper);
          }

          return wrapper;
        }

        /* ===============================
         * Restore saved state
         * =============================== */
        let fontSize = localStorage.getItem('a11y-font-size');
        if (!fontSize) fontSize = 100;
        document.documentElement.style.fontSize = fontSize + '%';

        const contrastEnabled = localStorage.getItem('a11y-contrast') === 'true';
        if (contrastEnabled) {
          ensureA11yWrapper();
          document.body.classList.add('a11y-contrast');
          const btn = document.getElementById('a11y-contrast');
          if (btn) btn.setAttribute('aria-pressed', 'true');
        }

        /* ===============================
         * Events
         * =============================== */

        // Increase font
        document.getElementById('a11y-increase').addEventListener('click', () => {
          if (fontSize < 160) {
            fontSize = parseInt(fontSize, 10) + 10;
            document.documentElement.style.fontSize = fontSize + '%';
            localStorage.setItem('a11y-font-size', fontSize);
          }
        });

        // Decrease font
        document.getElementById('a11y-decrease').addEventListener('click', () => {
          if (fontSize > 70) {
            fontSize = parseInt(fontSize, 10) - 10;
            document.documentElement.style.fontSize = fontSize + '%';
            localStorage.setItem('a11y-font-size', fontSize);
          }
        });

        // Toggle contrast
        document.getElementById('a11y-contrast').addEventListener('click', () => {
          ensureA11yWrapper();

          const enabled = document.body.classList.toggle('a11y-contrast');
          localStorage.setItem('a11y-contrast', enabled);

          document
            .getElementById('a11y-contrast')
            .setAttribute('aria-pressed', enabled ? 'true' : 'false');
        });

        // Reset accessibility
        document.getElementById('a11y-reset').addEventListener('click', () => {
          localStorage.removeItem('a11y-contrast');
          localStorage.removeItem('a11y-font-size');

          document.body.classList.remove('a11y-contrast');
          document.documentElement.style.fontSize = '100%';
          fontSize = 100;

          document
            .getElementById('a11y-contrast')
            .setAttribute('aria-pressed', 'false');
        });
      });
    },
  };
})(Drupal, once);
