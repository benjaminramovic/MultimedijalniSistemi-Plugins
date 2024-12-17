alert("Live search script loaded!");

jQuery(document).ready(function ($) {
    const searchInput = $('#live-search');
    const resultsDiv = $('#search-results');

    searchInput.on('input', function () {
        alert("Hello")
        const query = $(this).val();

        if (query.length > 2) {
            $.ajax({
                url: lms_ajax.url,
                type: 'POST',
                data: {
                    action: 'lms_live_search',
                    query: query,
                },
                success: function (data) {
                    resultsDiv.html('');
                    if (data.length > 0) {
                        data.forEach(movie => {
                            resultsDiv.append(`
                                <div class="search-result-item">
                                    <a href="${movie.permalink}">
                                        <strong>${movie.title}</strong>
                                    </a>
                                </div>
                            `);
                        });
                    } else {
                        resultsDiv.html('<p>Nema rezultata.</p>');
                    }
                },
            });
        } else {
            resultsDiv.html('');
        }
    });
});
