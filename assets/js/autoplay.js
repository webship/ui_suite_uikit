/**
 * @file
 * Pause and play control of the autoplaying sliders and slideshows.
 *
 * WCAG 2.2.2: moving content that starts on its own can be paused. With the
 * reduced motion preference, the slides start paused.
 */
((Drupal, once, UIkit) => {
  Drupal.behaviors.uiSuiteUikitAutoplay = {
    attach(context) {
      once(
        'ui-suite-uikit-autoplay',
        '[data-ui-suite-uikit-autoplay]',
        context,
      ).forEach((button) => {
        const root = button.closest('[uk-slideshow], [uk-slider]');
        if (!root || !UIkit) {
          return;
        }
        const name = root.hasAttribute('uk-slideshow') ? 'slideshow' : 'slider';
        const set = (paused) => {
          const component = UIkit[name](root);
          button.setAttribute('aria-pressed', paused ? 'true' : 'false');
          if (paused) {
            component.stopAutoplay();
          } else {
            component.startAutoplay();
          }
        };
        button.addEventListener('click', () => {
          set(button.getAttribute('aria-pressed') !== 'true');
        });
        // UIkit starts the autoplay again when the browser tab is shown.
        document.addEventListener('visibilitychange', () => {
          if (button.getAttribute('aria-pressed') === 'true') {
            setTimeout(() => UIkit[name](root).stopAutoplay());
          }
        });
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          set(true);
        }
      });
    },
  };
})(Drupal, once, window.UIkit);
