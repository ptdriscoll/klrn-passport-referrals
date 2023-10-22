const tabs = document.querySelectorAll('#table-tabs h2');

/**
 * Clears current CSS 'active' class from elements tabs and tables,
 * then adds 'active' tab clicked and its table.
 * @param {PointerEvent} e.
 */
function switchTableTabs(e) {
  if (!e.target.classList.contains('active')) {
    const index = e.target.getAttribute('data-tab-index');
    const tables = document.querySelectorAll('.table');

    for (let i = 0; i < tabs.length; i++) {
      tabs[i].classList.remove('active');
      tables[i].classList.remove('active');
    }

    tabs[index].classList.add('active');
    tables[index].classList.add('active');
  }
}

for (let tab of tabs) tab.addEventListener('click', switchTableTabs);
