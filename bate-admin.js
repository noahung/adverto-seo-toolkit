jQuery(document).ready(function($) {
    function loadImages(page) {
        $.ajax({
            url: bateAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'bate_load_images',
                page: page
            },
            success: function(response) {
                if (response.success) {
                    var tbody = $('#bate-table tbody');
                    tbody.empty();
                    $.each(response.data.images, function(index, image) {
                        var row = $('<tr>');
                        row.append($('<td class="column-image">').html('<a href="' + image.src + '" target="_blank"><img src="' + image.src + '" /></a>'));
                        row.append($('<td class="column-alt-text">').html('<input type="text" name="images[' + index + '][alt]" value="' + image.alt + '" /><input type="hidden" name="images[' + index + '][id]" value="' + image.id + '" />'));
                        tbody.append(row);
                    });

                    var pagination = $('#bate-pagination');
                    pagination.empty();
                    for (var i = 1; i <= response.data.total_pages; i++) {
                        var link = $('<a class="page-link">').text(i).attr('data-page', i);
                        if (i == page) {
                            link.css('background', '#005177');
                        }
                        pagination.append(link);
                    }
                } else {
                    alert('Failed to load images.');
                }
            }
        });
    }

    $('#bate-load-images').on('click', function() {
        var page = $(this).data('page');
        loadImages(page);
    });

    $(document).on('click', '.page-link', function() {
        var page = $(this).data('page');
        loadImages(page);
    });

    $('#bate-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: bateAjax.ajaxurl,
            method: 'POST',
            data: $(this).serialize() + '&action=bate_save_alt_texts',
            success: function(response) {
                if (response.success) {
                    alert('Alt texts updated.');
                } else {
                    alert('Failed to save alt texts.');
                }
            }
        });
    });
});
