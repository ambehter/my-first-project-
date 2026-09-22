// assets/js/jquery_users.js
$(document).ready(function() {
    var searchInput = $('input[name="search"]');
    
    // Функция загрузки пользователей
    function loadUsers(page, searchQuery) {
        // Показываем индикатор загрузки
        $('.users-table').addClass('loading');
        
        $.ajax({
            url: 'ajax_get_users.php',
            method: 'GET',
            data: {
                page: page,
                search: searchQuery
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Обновляем таблицу
                    $('.users-table tbody').html(response.html);
                    
                    // Обновляем пагинацию
                    $('.pagination').html(response.pagination);
                    
                    // Обновляем заголовок с количеством (опционально, если есть элемент)
                    if ($('.total-count').length) {
                        $('.total-count').text('Всего пользователей: ' + response.total);
                    }
                    
                    // Перепривязываем обработчики к новым ссылкам
                    attachPaginationHandlers();
                } else {
                    alert('Ошибка: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Ошибка соединения с сервером');
            },
            complete: function() {
                $('.users-table').removeClass('loading');
            }
        });
    }
    
    // Обработчик кликов по пагинации
    function attachPaginationHandlers() {
        $('.pagination a').off('click').on('click', function(e) {
            e.preventDefault();
            var page = $(this).data('page') || $(this).text();
            var searchValue = searchInput.val();
            loadUsers(page, searchValue);
            
            // Обновляем URL
            var url = new URL(window.location.href);
            url.searchParams.set('page', page);
            if (searchValue) {
                url.searchParams.set('search', searchValue);
            } else {
                url.searchParams.delete('search');
            }
            window.history.pushState({}, '', url);
        });
    }
    
    // Отправка формы поиска
    $('.search-box form').on('submit', function(e) {
        e.preventDefault();
        var searchValue = searchInput.val();
        loadUsers(1, searchValue);
        
        // Обновляем URL
        var url = new URL(window.location.href);
        if (searchValue) {
            url.searchParams.set('search', searchValue);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.set('page', '1');
        window.history.pushState({}, '', url);
    });
    
    // Кнопка сброса
    $('.search-box a[href="index.php"]').on('click', function(e) {
        e.preventDefault();
        searchInput.val('');
        loadUsers(1, '');
        window.history.pushState({}, '', 'index.php?page=1');
    });
    
    // Инициализация
    attachPaginationHandlers();
    
    // Добавляем стиль для индикатора загрузки
    $('<style>')
        .prop('type', 'text/css')
        .html('.users-table.loading { opacity: 0.5; transition: opacity 0.3s; }')
        .appendTo('head');
});