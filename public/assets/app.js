const MENU_I18N = window.menuI18n || {};
const t = (key, fallback) => MENU_I18N[key] || fallback;
const getSearchInput = () => document.querySelector('[data-menu-search]');

function scrollActiveChipIntoView(activeLink) {
  const strip = activeLink.closest('[data-category-strip]');
  if (!strip) return;

  const targetLeft = activeLink.offsetLeft + activeLink.offsetWidth / 2 - strip.clientWidth / 2;
  const maxLeft = strip.scrollWidth - strip.clientWidth;
  strip.scrollLeft = Math.max(0, Math.min(targetLeft, maxLeft));
}

function getSectionScrollOffset() {
  const strip = document.querySelector('[data-category-strip]');
  return (strip ? strip.getBoundingClientRect().height : 0) + 24;
}

function scrollToMenuSection(section, behavior = 'smooth') {
  const top = Math.max(0, section.getBoundingClientRect().top + window.scrollY - getSectionScrollOffset());

  if (behavior === 'auto') {
    const root = document.documentElement;
    const previousScrollBehavior = root.style.scrollBehavior;
    root.style.scrollBehavior = 'auto';
    window.scrollTo(0, top);
    root.style.scrollBehavior = previousScrollBehavior;
    return;
  }

  window.scrollTo({ top, behavior });
}

function setActiveCategory(sectionId, shouldScroll = true, forceScroll = false) {
  if (!sectionId) return;

  const links = document.querySelectorAll('[data-category-link]');
  const activeId = String(sectionId);
  let activeLink = null;
  let didChange = false;

  links.forEach((link) => {
    const isActive = link.dataset.sectionId === activeId;
    if (link.classList.contains('is-active') !== isActive) {
      didChange = true;
    }
    link.classList.toggle('is-active', isActive);
    if (isActive) activeLink = link;
  });

  if (activeLink && shouldScroll && (didChange || forceScroll)) {
    scrollActiveChipIntoView(activeLink);
  }
}

function updateActiveCategoryFromScroll() {
  const input = getSearchInput();
  if (input && input.value.trim() !== '') return;

  const strip = document.querySelector('[data-category-strip]');
  const sections = Array.from(document.querySelectorAll('[data-menu-section]')).filter((section) => !section.hidden);
  if (!strip || sections.length === 0) return;

  const stripBottom = strip.getBoundingClientRect().bottom;
  const marker = window.scrollY + stripBottom + 24;
  let active = sections[0];

  sections.forEach((section) => {
    if (section.offsetTop <= marker) {
      active = section;
    }
  });

  setActiveCategory(active.dataset.sectionId);
}

let scrollSpyFrame = 0;
function scheduleScrollSpy() {
  if (scrollSpyFrame) return;
  scrollSpyFrame = window.requestAnimationFrame(() => {
    scrollSpyFrame = 0;
    updateActiveCategoryFromScroll();
  });
}

document.addEventListener('input', (event) => {
  const input = event.target.closest('[data-menu-search]');
  if (!input) return;

  const query = input.value.trim().toLowerCase();
  const cards = document.querySelectorAll('[data-menu-card]');

  cards.forEach((card) => {
    const text = card.textContent.toLowerCase();
    card.hidden = query !== '' && !text.includes(query);
  });

  document.querySelectorAll('[data-menu-section]').forEach((section) => {
    const sectionCards = Array.from(section.querySelectorAll('[data-menu-card]'));
    const hasVisibleCards = sectionCards.some((card) => !card.hidden);
    section.hidden = query !== '' && sectionCards.length > 0 && !hasVisibleCards;
  });

  if (query === '') {
    updateActiveCategoryFromScroll();
  }
});

document.addEventListener('click', (event) => {
  const link = event.target.closest('[data-category-link]');
  if (!link) return;

  const href = link.getAttribute('href') || '';
  if (!href.startsWith('#')) return;

  const target = document.querySelector(href);
  if (!target) return;

  event.preventDefault();
  setActiveCategory(link.dataset.sectionId, true, true);
  scrollToMenuSection(target, 'smooth');
  window.history.replaceState(null, '', href);
});

document.addEventListener('change', (event) => {
  const input = event.target.closest('[data-image-preview-input]');
  if (!input || !input.files || !input.files[0]) return;

  const form = input.closest('form');
  const preview = form ? form.querySelector('[data-image-preview]') : document.querySelector('[data-image-preview]');
  if (!preview) return;

  preview.src = URL.createObjectURL(input.files[0]);
  preview.hidden = false;
});

document.addEventListener('submit', (event) => {
  const form = event.target.closest('[data-confirm]');
  if (!form) return;

  if (!window.confirm(form.dataset.confirm || 'Продолжить?')) {
    event.preventDefault();
  }
});

window.addEventListener('scroll', scheduleScrollSpy, { passive: true });
window.addEventListener('resize', scheduleScrollSpy);

function initializeMenuScroll() {
  const strip = document.querySelector('[data-category-strip]');
  const initialSectionId = strip ? strip.dataset.initialSectionId : '';
  const firstSectionId = strip ? strip.querySelector('[data-category-link]')?.dataset.sectionId || '' : '';
  const hashTarget = window.location.hash ? document.querySelector(window.location.hash) : null;


  if (hashTarget) {
    scrollToMenuSection(hashTarget, 'auto');
    const sectionId = hashTarget.dataset.sectionId || window.location.hash.replace('#category-', '');
    setActiveCategory(sectionId, true, true);
    return;
  }

  if (initialSectionId) {
    const initialSection = document.querySelector(`[data-menu-section][data-section-id="${initialSectionId}"]`);
    if (initialSection && initialSectionId !== firstSectionId) {
      scrollToMenuSection(initialSection, 'auto');
      setActiveCategory(initialSectionId, true, true);
      return;
    }

    setActiveCategory(initialSectionId, true, true);
  }

  updateActiveCategoryFromScroll();
}

window.setTimeout(initializeMenuScroll, 0);
const CART_STORAGE_KEY = `onlineMenuCart:${MENU_I18N.locale || 'ru'}`;
let cartState = readCartState();

function readCartState() {
  try {
    const parsed = JSON.parse(window.localStorage.getItem(CART_STORAGE_KEY) || '{}');
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch (error) {
    return {};
  }
}

function saveCartState() {
  try {
    window.localStorage?.setItem(CART_STORAGE_KEY, JSON.stringify(cartState));
  } catch (error) {
    // The cart still works for the current page when persistent storage is unavailable.
  }
}

function formatCartMoney(amount) {
  return `${new Intl.NumberFormat(MENU_I18N.numberLocale || 'ru-RU').format(amount)} ₸`;
}

function getCartItems() {
  return Object.values(cartState).filter((item) => item && item.qty > 0);
}

function getCartTotal() {
  return getCartItems().reduce((sum, item) => sum + item.priceAmount * item.qty, 0);
}

function getCartCount() {
  return getCartItems().reduce((sum, item) => sum + item.qty, 0);
}

function getItemFromCard(card) {
  return {
    id: card.dataset.cartId,
    title: card.dataset.cartTitle || '',
    priceLabel: card.dataset.cartPriceLabel || '',
    priceAmount: Number(card.dataset.cartPriceAmount || 0),
    image: card.dataset.cartImage || '',
    qty: 0,
  };
}

function addCartItem(item) {
  if (!item || !item.id) return;

  const current = cartState[item.id] || item;
  cartState[item.id] = {
    ...current,
    ...item,
    qty: (current.qty || 0) + 1,
  };

  saveCartState();
  renderCart();
}

function removeCartItem(id) {
  if (!id || !cartState[id]) return;

  cartState[id].qty -= 1;
  if (cartState[id].qty <= 0) {
    delete cartState[id];
  }

  saveCartState();
  renderCart();
}

function renderCartRows() {
  const list = document.querySelector('[data-cart-list]');
  if (!list) return;

  list.replaceChildren();

  getCartItems().forEach((item) => {
    const row = document.createElement('div');
    row.className = 'cart-sheet__item';

    const main = document.createElement('div');
    main.className = 'cart-sheet__item-main';

    const title = document.createElement('span');
    title.className = 'cart-sheet__item-title';
    title.textContent = item.title;

    const price = document.createElement('span');
    price.className = 'cart-sheet__item-price';
    price.textContent = item.priceLabel;

    main.append(title, price);

    const controls = document.createElement('div');
    controls.className = 'cart-sheet__item-controls';

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.dataset.cartSheetRemove = item.id;
    remove.setAttribute('aria-label', t('remove', 'Убрать'));
    remove.textContent = '-';

    const qty = document.createElement('span');
    qty.textContent = String(item.qty);

    const add = document.createElement('button');
    add.type = 'button';
    add.dataset.cartSheetAdd = item.id;
    add.setAttribute('aria-label', t('add', 'Добавить'));
    add.textContent = '+';

    const lineTotal = document.createElement('strong');
    lineTotal.className = 'cart-sheet__line-total';
    lineTotal.textContent = item.priceAmount > 0 ? formatCartMoney(item.priceAmount * item.qty) : item.priceLabel;

    controls.append(remove, qty, add, lineTotal);
    row.append(main, controls);
    list.append(row);
  });
}

function renderCart() {
  document.querySelectorAll('[data-cart-item]').forEach((card) => {
    const item = cartState[card.dataset.cartId];
    const qty = item ? item.qty : 0;
    const controls = card.querySelector('[data-cart-controls]');
    const remove = card.querySelector('[data-cart-remove]');
    const qtyLabel = card.querySelector('[data-cart-qty]');

    controls?.classList.toggle('is-active', qty > 0);
    if (remove) remove.hidden = qty <= 0;
    if (qtyLabel) {
      qtyLabel.hidden = qty <= 0;
      qtyLabel.textContent = String(qty);
    }
  });

  const total = getCartTotal();
  const count = getCartCount();
  const summary = document.querySelector('[data-cart-summary]');
  const summaryTotal = document.querySelector('[data-cart-summary-total]');
  const sheet = document.querySelector('[data-cart-sheet]');
  const sheetTotal = document.querySelector('[data-cart-total]');

  if (summary) summary.hidden = count === 0;
  if (summaryTotal) summaryTotal.textContent = formatCartMoney(total);
  if (sheetTotal) sheetTotal.textContent = formatCartMoney(total);

  renderCartRows();

  if (count === 0 && sheet && !sheet.hidden) {
    closeCartSheet();
  }
}

function openCartSheet() {
  const sheet = document.querySelector('[data-cart-sheet]');
  if (!sheet || getCartCount() === 0) return;

  sheet.hidden = false;
  document.body.classList.add('is-cart-open');
}

function closeCartSheet() {
  const sheet = document.querySelector('[data-cart-sheet]');
  if (!sheet) return;

  sheet.hidden = true;
  document.body.classList.remove('is-cart-open');
}

function updateScrollTopButton() {
  const button = document.querySelector('[data-scroll-top]');
  if (!button) return;

  button.hidden = window.scrollY < 520;
}

document.addEventListener('click', (event) => {
  const addButton = event.target.closest('[data-cart-add]');
  if (addButton) {
    event.preventDefault();
    event.stopPropagation();
    const card = addButton.closest('[data-cart-item]');
    addCartItem(getItemFromCard(card));
    return;
  }

  const removeButton = event.target.closest('[data-cart-remove]');
  if (removeButton) {
    event.preventDefault();
    event.stopPropagation();
    const card = removeButton.closest('[data-cart-item]');
    removeCartItem(card?.dataset.cartId);
    return;
  }

  const sheetAdd = event.target.closest('[data-cart-sheet-add]');
  if (sheetAdd) {
    const id = sheetAdd.dataset.cartSheetAdd;
    const card = document.querySelector(`[data-cart-item][data-cart-id="${id}"]`);
    addCartItem(card ? getItemFromCard(card) : cartState[id]);
    return;
  }

  const sheetRemove = event.target.closest('[data-cart-sheet-remove]');
  if (sheetRemove) {
    removeCartItem(sheetRemove.dataset.cartSheetRemove);
    return;
  }

  if (event.target.closest('[data-cart-summary]')) {
    openCartSheet();
    return;
  }

  if (event.target.closest('[data-cart-close]')) {
    closeCartSheet();
    return;
  }

  if (event.target.closest('[data-scroll-top]')) {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    closeCartSheet();
  }
});

window.addEventListener('scroll', updateScrollTopButton, { passive: true });
window.setTimeout(() => {
  renderCart();
  updateScrollTopButton();
}, 0);

function initializePasswordToggles() {
  document.querySelectorAll('input[type="password"]').forEach((input) => {
    if (input.dataset.passwordToggleReady === '1') return;

    const wrapper = document.createElement('span');
    wrapper.className = 'password-field';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.append(input);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-toggle';
    button.setAttribute('aria-label', 'Показать пароль');
    button.setAttribute('title', 'Показать пароль');
    button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
    wrapper.append(button);
    input.dataset.passwordToggleReady = '1';

    button.addEventListener('click', () => {
      const isVisible = input.type === 'text';
      input.type = isVisible ? 'password' : 'text';
      button.classList.toggle('is-active', !isVisible);
      button.setAttribute('aria-label', isVisible ? 'Показать пароль' : 'Скрыть пароль');
      button.setAttribute('title', isVisible ? 'Показать пароль' : 'Скрыть пароль');
    });
  });
}

initializePasswordToggles();
function initializeFileInputs() {
  document.querySelectorAll('.admin-page input[type="file"]').forEach((input) => {
    if (input.dataset.fileInputReady === '1') return;

    const wrapper = document.createElement('span');
    wrapper.className = 'file-field';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.append(input);

    const button = document.createElement('span');
    button.className = 'file-field__button';
    button.textContent = 'Выбрать файл';

    const label = document.createElement('span');
    label.className = 'file-field__name';
    label.textContent = 'Файл не выбран';

    wrapper.append(button, label);
    input.dataset.fileInputReady = '1';

    input.addEventListener('change', () => {
      label.textContent = input.files && input.files[0] ? input.files[0].name : 'Файл не выбран';
    });
  });
}

initializeFileInputs();