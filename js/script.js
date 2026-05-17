// script.js içine eklenebilir
$(document).ready(function() {
    // Sayfa yüklenince honeypot alanını gizle
    $('.b0tdenetle').hide();

    // Star rating text update
    const ratingTexts = {
        '5': '5 - Harika',
        '4': '4 - Çok iyi',
        '3': '3 - İyi',
        '2': '2 - Orta',
        '1': '1 - Zayıf'
    };

    $('.star-rating input').on('change', function() {
        $('#rating-text').text(ratingTexts[$(this).val()]);
    });

    $('.star-rating label').on('mouseenter', function() {
        const rating = $(this).prev('input').val();
        if (rating) {
            $('#rating-text').text(ratingTexts[rating]);
        }
    });

    $('.star-rating').on('mouseleave', function() {
        const checkedRating = $('.star-rating input:checked').val();
        $('#rating-text').text(checkedRating ? ratingTexts[checkedRating] : 'Puan seçin');
    });
});