document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('megaMenuToggle');
  const menu = document.getElementById('productMegaMenu');
  const backdrop = document.querySelector('.mega-menu-backdrop');

  if (!toggle || !menu || !backdrop) return;

  const closeButton = menu.querySelector('.mega-menu-close');
  const header = document.querySelector('.header');
  const categories = [...menu.querySelectorAll('.mega-menu-category')];
  const ingredientList = menu.querySelector('.mega-menu-ingredient-list');
  const allIngredientsButton = menu.querySelector('.mega-menu-ingredient-all');
  const productsTitle = menu.querySelector('#megaProductsTitle');
  const productList = menu.querySelector('.mega-menu-product-list');
  const viewAllLink = menu.querySelector('.mega-menu-view-all');
  const viewAllLabel = menu.querySelector('.mega-menu-view-all-label');
  const status = menu.querySelector('.mega-menu-products-status');
  const endpoint = menu.dataset.productsUrl;
  let requestController;
  let requestId = 0;
  let activeCategoryId = null;
  let activeIngredient = '';
  let hoverOpenTimer;

  function closeMenu(returnFocus = false) {
    menu.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    menu.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('mega-menu-open');
    if (returnFocus) toggle.focus();
  }

  function openMenu() {
    if (menu.classList.contains('is-open')) return;

    const headerBottom = header ? header.getBoundingClientRect().bottom : 0;
    menu.style.setProperty('--mega-menu-top', `${Math.max(0, headerBottom)}px`);
    backdrop.style.setProperty('--mega-menu-top', `${Math.max(0, headerBottom)}px`);
    menu.classList.add('is-open');
    backdrop.classList.add('is-open');
    menu.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('mega-menu-open');

    if (activeCategoryId === null) loadCategory(0);
  }

  function showStatus(message, isError = false) {
    status.textContent = message;
    status.classList.toggle('has-error', isError);
  }

  function renderProducts(products) {
    productList.replaceChildren();
    if (!products.length) {
      showStatus('Chưa có sản phẩm phù hợp với lựa chọn này.');
      return;
    }

    showStatus('');
    products.forEach((product) => {
      const card = document.createElement('a');
      card.className = 'mega-menu-product';
      card.href = product.url;

      const imageFrame = document.createElement('span');
      imageFrame.className = 'mega-menu-product-image';
      if (product.image) {
        const image = document.createElement('img');
        image.src = product.image;
        image.alt = '';
        image.loading = 'lazy';
        imageFrame.append(image);
      }

      const details = document.createElement('span');
      details.className = 'mega-menu-product-details';
      const name = document.createElement('span');
      name.className = 'mega-menu-product-name';
      name.textContent = product.name;
      const volume = document.createElement('span');
      volume.className = 'mega-menu-product-volume';
      volume.textContent = product.volume || '';
      const ingredients = document.createElement('span');
      ingredients.className = 'mega-menu-product-ingredients';
      ingredients.textContent = product.ingredients || '';
      const price = document.createElement('span');
      price.className = 'mega-menu-product-price';
      price.textContent = new Intl.NumberFormat('vi-VN').format(product.price) + ' ₫';
      details.append(name);
      if (volume.textContent) details.append(volume);
      if (ingredients.textContent) details.append(ingredients);
      details.append(price);
      card.append(imageFrame, details);
      productList.append(card);
    });
  }

  function selectIngredient(button) {
    const ingredient = button.dataset.ingredient;
    if (!ingredient || ingredient === activeIngredient) return;

    activeIngredient = ingredient;
    menu.querySelectorAll('.mega-menu-ingredient').forEach((item) => {
      const selected = item === button;
      item.classList.toggle('is-active', selected);
      item.setAttribute('aria-pressed', String(selected));
    });
    loadProducts();
  }

  function renderIngredients(ingredients) {
    ingredientList.replaceChildren();
    allIngredientsButton.classList.toggle('is-active', activeIngredient === '');
    allIngredientsButton.setAttribute('aria-pressed', String(activeIngredient === ''));
    if (!ingredients.length) {
      const emptyMessage = document.createElement('span');
      emptyMessage.className = 'mega-menu-ingredient-message';
      emptyMessage.textContent = 'Chưa có nguyên liệu trong danh mục này.';
      ingredientList.append(emptyMessage);
      return;
    }

    ingredients.forEach((ingredient) => {
      const button = document.createElement('button');
      button.className = 'mega-menu-ingredient';
      button.type = 'button';
      button.setAttribute('role', 'listitem');
      button.setAttribute('aria-pressed', 'false');
      button.dataset.ingredient = ingredient;
      button.textContent = ingredient;
      button.addEventListener('pointerenter', () => selectIngredient(button));
      button.addEventListener('focus', () => selectIngredient(button));
      button.addEventListener('click', () => selectIngredient(button));
      ingredientList.append(button);
    });
  }

  function updateCategorySelection(categoryId) {
    categories.forEach((button) => {
      const selected = Number(button.dataset.categoryId) === categoryId;
      button.classList.toggle('is-active', selected);
      button.setAttribute('aria-pressed', String(selected));
    });
  }

  function selectAllIngredients() {
    if (activeIngredient === '') return;

    activeIngredient = '';
    menu.querySelectorAll('.mega-menu-ingredient').forEach((item) => {
      const selected = item === allIngredientsButton;
      item.classList.toggle('is-active', selected);
      item.setAttribute('aria-pressed', String(selected));
    });
    loadProducts();
  }

  async function fetchMenuData(parameters) {
    requestController?.abort();
    requestController = new AbortController();
    const currentRequest = ++requestId;
    const query = new URLSearchParams(parameters);
    productList.replaceChildren();
    showStatus('Đang tải sản phẩm…');

    try {
      const response = await fetch(`${endpoint}?${query}`, {
        headers: { Accept: 'application/json' },
        signal: requestController.signal,
      });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload.error || 'Không thể tải dữ liệu menu.');
      if (currentRequest !== requestId) return null;
      return payload;
    } catch (error) {
      if (error.name === 'AbortError' || currentRequest !== requestId) return null;
      console.error('Không thể tải dữ liệu cho mega menu:', error);
      showStatus(error.message || 'Không thể tải dữ liệu. Vui lòng thử lại.', true);
      return null;
    }
  }

  async function loadCategory(categoryId) {
    if (categoryId === activeCategoryId) return;

    activeCategoryId = categoryId;
    activeIngredient = '';
    updateCategorySelection(categoryId);
    const categoryView = categoryId !== 0;
    menu.classList.toggle('has-category-selection', categoryView);
    ingredientList.closest('.mega-menu-ingredients').hidden = categoryView;
    productsTitle.textContent = categoryView ? 'Sản phẩm trong danh mục' : 'Sản phẩm nổi bật';
    viewAllLink.href = categoryView ? `products.php?category=${categoryId}` : 'products.php';
    viewAllLabel.textContent = 'Tìm hiểu thêm';
    ingredientList.replaceChildren();
    const loadingMessage = document.createElement('span');
    loadingMessage.className = 'mega-menu-ingredient-message';
    loadingMessage.textContent = 'Đang tải nguyên liệu…';
    ingredientList.append(loadingMessage);

    const payload = await fetchMenuData({ category_id: String(categoryId) });
    if (!payload) {
      if (activeCategoryId === categoryId) {
        loadingMessage.textContent = 'Không thể tải nguyên liệu.';
      }
      return;
    }
    renderIngredients(payload.ingredients || []);
    renderProducts(payload.products || []);
  }

  async function loadProducts() {
    const parameters = { category_id: String(activeCategoryId ?? 0), ingredient: activeIngredient };
    const payload = await fetchMenuData(parameters);
    if (payload) renderProducts(payload.products || []);
  }

  toggle.addEventListener('pointerenter', () => {
    clearTimeout(hoverOpenTimer);
    hoverOpenTimer = setTimeout(openMenu, 120);
  });
  toggle.addEventListener('pointerleave', () => clearTimeout(hoverOpenTimer));
  toggle.addEventListener('pointerdown', () => clearTimeout(hoverOpenTimer));
  toggle.addEventListener('click', () => {
    if (menu.classList.contains('is-open')) closeMenu();
    else openMenu();
  });

  categories.forEach((button) => {
    const select = () => {
      const categoryId = Number(button.dataset.categoryId);
      if (categoryId === activeCategoryId) return;
      loadCategory(categoryId);
    };
    button.addEventListener('pointerenter', select);
    button.addEventListener('focus', select);
    button.addEventListener('click', select);
  });

  allIngredientsButton.addEventListener('pointerenter', selectAllIngredients);
  allIngredientsButton.addEventListener('focus', selectAllIngredients);
  allIngredientsButton.addEventListener('click', selectAllIngredients);

  closeButton?.addEventListener('click', () => closeMenu(true));
});
