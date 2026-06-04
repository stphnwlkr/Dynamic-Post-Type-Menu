(() => {
  const trigger = document.querySelector('#wp-admin-bar-post-types > .ab-item');
  const panel = document.querySelector('[data-dptm-panel]');

  if (!trigger || !panel) return;

  const typeButtons = [...panel.querySelectorAll('[data-dptm-type]')];
  const actionPanels = [...panel.querySelectorAll('[data-dptm-actions]')];

  trigger.setAttribute('aria-haspopup', 'dialog');
  trigger.setAttribute('aria-controls', 'dptm-content-types-panel');
  trigger.setAttribute('aria-expanded', 'false');

  const getTriggerRect = () => trigger.getBoundingClientRect();

  const positionPanel = () => {
    const rect = getTriggerRect();
    const offset = 8;
    const width = panel.offsetWidth || 320;
    const left = Math.min(Math.max(8, rect.left), Math.max(8, window.innerWidth - width - 8));

    panel.style.setProperty('--dptm-panel-top', `${rect.bottom + offset}px`);
    panel.style.setProperty('--dptm-panel-left', `${left}px`);
  };

  const clearActiveType = () => {
    typeButtons.forEach((button) => {
      button.classList.remove('is-active');
      button.setAttribute('aria-selected', 'false');
    });

    actionPanels.forEach((actions) => {
      actions.classList.remove('is-active');
      actions.hidden = true;
    });

    panel.classList.remove('has-active');
    panel.style.setProperty('--dptm-actions-offset', '0px');
  };

  const setActiveType = (id) => {
    panel.classList.add('has-active');

    typeButtons.forEach((button) => {
      const isActive = button.dataset.dptmType === id;
      button.classList.toggle('is-active', isActive);
      button.setAttribute('aria-selected', isActive ? 'true' : 'false');

      if (isActive) {
        panel.style.setProperty('--dptm-actions-offset', `${button.offsetTop}px`);
      }
    });

    actionPanels.forEach((actions) => {
      const isActive = actions.dataset.dptmActions === id;
      actions.classList.toggle('is-active', isActive);
      actions.hidden = !isActive;
    });

    positionPanel();
  };

  const openPanel = () => {
    clearActiveType();
    positionPanel();
    panel.hidden = false;
    panel.classList.add('is-open');
    trigger.setAttribute('aria-expanded', 'true');
    document.body.classList.add('dptm-panel-open');
  };

  const closePanel = () => {
    panel.classList.remove('is-open');
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('dptm-panel-open');
  };

  const togglePanel = (event) => {
    event.preventDefault();
    event.stopPropagation();
    panel.hidden ? openPanel() : closePanel();
  };

  trigger.addEventListener('click', togglePanel);

  panel.addEventListener('transitionend', () => {
    if (!panel.hidden) positionPanel();
  });

  typeButtons.forEach((button) => {
    button.addEventListener('click', () => setActiveType(button.dataset.dptmType));
    button.addEventListener('keydown', (event) => {
      const index = typeButtons.indexOf(button);
      let nextIndex = index;

      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        setActiveType(button.dataset.dptmType);
        return;
      }

      if (event.key === 'ArrowDown') nextIndex = Math.min(typeButtons.length - 1, index + 1);
      if (event.key === 'ArrowUp') nextIndex = Math.max(0, index - 1);
      if (event.key === 'Home') nextIndex = 0;
      if (event.key === 'End') nextIndex = typeButtons.length - 1;

      if (nextIndex !== index) {
        event.preventDefault();
        typeButtons[nextIndex].focus();
      }
    });
  });

  document.addEventListener('click', (event) => {
    if (panel.hidden) return;
    if (panel.contains(event.target) || trigger.contains(event.target)) return;
    closePanel();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !panel.hidden) {
      closePanel();
      trigger.focus();
    }
  });

  window.addEventListener('resize', () => {
    if (!panel.hidden) positionPanel();
  });
})();
