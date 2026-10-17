document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-product-search]').forEach((form) => {
    const input = form.querySelector('input[name="search"]');
    const list = form.querySelector('.search-suggestions');
    if (!input || !list) return;

    let timer;
    let controller;
    let activeIndex = -1;

    function closeSuggestions() {
      list.hidden = true;
      list.replaceChildren();
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      activeIndex = -1;
    }

    function navigateToProduct(name) {
      const url = new URL(form.action, window.location.href);
      url.searchParams.set('search', name);
      const category = form.querySelector('input[name="category"]');
      if (category && category.value) {
        url.searchParams.set('category', category.value);
      }
      window.location.assign(url.href);
    }

    function renderSuggestions(suggestions) {
      list.replaceChildren();
      activeIndex = -1;

      if (suggestions.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'search-suggestion-empty';
        empty.textContent = 'Không tìm thấy sản phẩm phù hợp.';
        list.append(empty);
      } else {
        suggestions.forEach((suggestion, index) => {
          const option = document.createElement('button');
          option.className = 'search-suggestion';
          option.type = 'button';
          option.id = `${input.id || 'product-search'}-suggestion-${index}`;
          option.setAttribute('role', 'option');
          option.setAttribute('aria-selected', 'false');

          const details = document.createElement('span');
          const name = document.createElement('span');
          name.className = 'search-suggestion-name';
          name.textContent = suggestion.name;
          const category = document.createElement('span');
          category.className = 'search-suggestion-meta';
          category.textContent = suggestion.category;
          details.append(name, category);

          const price = document.createElement('span');
          price.className = 'search-suggestion-price';
          price.textContent = suggestion.price;
          option.append(details, price);
          option.addEventListener('click', () => navigateToProduct(suggestion.name));
          list.append(option);
        });
      }

      list.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    }

    input.addEventListener('input', () => {
      clearTimeout(timer);
      if (controller) controller.abort();

      const query = input.value.trim();
      if ([...query].length < 2) {
        closeSuggestions();
        return;
      }

      timer = setTimeout(async () => {
        controller = new AbortController();
        try {
          const url = new URL('api/product_suggestions.php', window.location.href);
          url.searchParams.set('q', query);
          const response = await fetch(url, { signal: controller.signal });
          if (!response.ok) throw new Error(`Suggestion request failed: ${response.status}`);
          const data = await response.json();
          if (input.value.trim() !== query) return;
          if (!data.success) throw new Error(data.message || 'Unable to load suggestions');
          renderSuggestions(data.suggestions);
        } catch (error) {
          if (error.name === 'AbortError') return;
          console.error('Unable to load product suggestions:', error);
          renderSuggestions([]);
          list.querySelector('.search-suggestion-empty').textContent =
            'Không thể tải gợi ý tìm kiếm lúc này.';
        }
      }, 180);
    });

    input.addEventListener('keydown', (event) => {
      const options = [...list.querySelectorAll('[role="option"]')];
      if (event.key === 'Escape') {
        closeSuggestions();
      } else if (event.key === 'ArrowDown' && options.length > 0) {
        event.preventDefault();
        activeIndex = (activeIndex + 1) % options.length;
      } else if (event.key === 'ArrowUp' && options.length > 0) {
        event.preventDefault();
        activeIndex = activeIndex < 0
          ? options.length - 1
          : (activeIndex - 1 + options.length) % options.length;
      } else if (event.key === 'Enter' && activeIndex >= 0) {
        event.preventDefault();
        options[activeIndex].click();
        return;
      } else {
        return;
      }

      options.forEach((option, index) => {
        const selected = index === activeIndex;
        option.setAttribute('aria-selected', String(selected));
        if (selected) {
          input.setAttribute('aria-activedescendant', option.id);
          option.scrollIntoView({ block: 'nearest' });
        }
      });
    });

    document.addEventListener('click', (event) => {
      if (!form.contains(event.target)) closeSuggestions();
    });
  });
});
