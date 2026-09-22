// assets/js/main.js

// ==================== GOOGLE ANALYTICS 4 ====================
// Инициализация GA4 (должна быть первой)
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-XXXXXXXXXX'); // Замените на ваш реальный ID потока данных GA4

// Функции отслеживания событий для电子商务
function trackAddToCart(productName, price, category) {
    gtag('event', 'add_to_cart', {
        'items': [{
            'name': productName,
            'price': price,
            'category': category
        }]
    });
    console.log('GA4 Event: add_to_cart', productName, price, category);
}

function trackPurchase(total, orderId) {
    gtag('event', 'purchase', {
        'transaction_id': orderId,
        'value': total,
        'currency': 'RUB'
    });
    console.log('GA4 Event: purchase', orderId, total);
}

function trackViewItem(productName, price, category) {
    gtag('event', 'view_item', {
        'items': [{
            'name': productName,
            'price': price,
            'category': category
        }]
    });
}

function trackBeginCheckout(items, total) {
    gtag('event', 'begin_checkout', {
        'items': items,
        'value': total,
        'currency': 'RUB'
    });
}

function trackSearch(searchQuery) {
    gtag('event', 'search', {
        'search_term': searchQuery
    });
}

// ==================== AJAX ДЛЯ ПОЛЬЗОВАТЕЛЕЙ ====================
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('.search-box form');
    const searchInput = document.querySelector('input[name="search"]');
    
    // Функция загрузки пользователей через AJAX
    function loadUsers(page, searchQuery) {
        let url = 'ajax_get_users.php?page=' + page;
        if (searchQuery && searchQuery.trim() !== '') {
            url += '&search=' + encodeURIComponent(searchQuery.trim());
            // Отслеживаем поиск через GA4
            trackSearch(searchQuery.trim());
        }
        
        console.log('Загрузка: ' + url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('.users-table tbody');
                if (tbody) {
                    tbody.innerHTML = data.html;
                }
                
                const paginationDiv = document.querySelector('.pagination');
                if (paginationDiv && data.pagination) {
                    paginationDiv.innerHTML = data.pagination;
                    attachPaginationHandlers();
                }
                
                const resultCount = document.querySelector('.result-count');
                if (resultCount) {
                    resultCount.textContent = 'Найдено: ' + data.total;
                }
            } else {
                console.error('Ошибка загрузки:', data.error);
                alert('Ошибка: ' + data.error);
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            alert('Ошибка соединения с сервером');
        });
    }
    
    function attachPaginationHandlers() {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page') || this.textContent;
                const searchValue = searchInput ? searchInput.value : '';
                loadUsers(page, searchValue);
                
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                if (searchValue) {
                    url.searchParams.set('search', searchValue);
                } else {
                    url.searchParams.delete('search');
                }
                window.history.pushState({}, '', url);
            });
        });
    }
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchValue = searchInput ? searchInput.value : '';
            loadUsers(1, searchValue);
            
            const url = new URL(window.location.href);
            if (searchValue) {
                url.searchParams.set('search', searchValue);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.set('page', '1');
            window.history.pushState({}, '', url);
        });
    }
    
    const resetLink = document.querySelector('.search-box a[href="index.php"]');
    if (resetLink) {
        resetLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (searchInput) searchInput.value = '';
            loadUsers(1, '');
            window.history.pushState({}, '', 'index.php?page=1');
        });
    }
    
    attachPaginationHandlers();
});

// ==================== ОТСЛЕЖИВАНИЕ КАРТОЧЕК ТОВАРОВ ====================
// Функция для привязки отслеживания к карточкам товаров (вызывать после загрузки товаров)
function attachProductTracking() {
    // Отслеживание просмотра товара при клике на карточку
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Если клик не по кнопке добавления в корзину и не по кнопке редактирования
            if (!e.target.closest('.add-to-cart-btn') && !e.target.closest('.delete-btn') && !e.target.closest('.btn-primary')) {
                const productName = this.querySelector('h3')?.innerText || 'Неизвестный товар';
                const priceText = this.querySelector('.price')?.innerText || '0';
                const price = parseFloat(priceText.replace(/[^0-9.-]+/g, '')) || 0;
                const category = this.querySelector('.category')?.innerText.replace('📁 ', '') || 'Без категории';
                trackViewItem(productName, price, category);
            }
        });
    });
    
    // Отслеживание добавления в корзину
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const productCard = this.closest('.product-card');
            if (productCard) {
                const productName = productCard.querySelector('h3')?.innerText || 'Неизвестный товар';
                const priceText = productCard.querySelector('.price')?.innerText || '0';
                const price = parseFloat(priceText.replace(/[^0-9.-]+/g, '')) || 0;
                const category = productCard.querySelector('.category')?.innerText.replace('📁 ', '') || 'Без категории';
                trackAddToCart(productName, price, category);
            }
        });
    });
}

// Вызываем после загрузки страницы и после AJAX-обновлений
document.addEventListener('DOMContentLoaded', attachProductTracking);

// Для AJAX-обновлений списка товаров (если есть)
function reattachProductTracking() {
    attachProductTracking();
}