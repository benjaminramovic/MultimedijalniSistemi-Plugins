<?php
/*
Plugin Name: Live Movie Search
Description: Dodaje funkcionalnost pretrage filmova uživo u WordPress.
Version: 1.0
Author: TvojIme
*/

if (!defined('ABSPATH')) {
    exit; // Zaštita od direktnog pristupa
}

// Učitaj skripte i stilove

function lms_enqueue_scripts() {
    // Učitaj jQuery ako nije već učitan
    wp_enqueue_script('jquery');

    // Učitaj tvoju skriptu
    wp_enqueue_script('live-search', plugins_url('js/live-search.js', __FILE__), ['jquery'], null, true);

    // Lokalizuj skriptu
    wp_localize_script('live-search', 'lms_ajax', [
        'url' => admin_url('admin-ajax.php')
    ]);

    // Učitaj stilove
    wp_enqueue_style('lms-live-search-style', plugins_url('css/live-search.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'lms_enqueue_scripts');

function test_function(){
    echo "Test funkcija je pozvana!";
}
add_action('wp_footer', 'test_function');

// AJAX handler za pretragu
function lms_live_search_handler() {
    $query = sanitize_text_field($_POST['query']); // Unos korisnika
    $args = [
        'post_type' => 'movies', // Post type za pretragu
        's' => $query,
        'posts_per_page' => 10,
    ];
    $search_query = new WP_Query($args);

    $results = [];
    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $results[] = [
                'title' => get_the_title(),
                'permalink' => get_permalink(),
            ];
        }
    }
    wp_reset_postdata();

    wp_send_json($results); // Vrati rezultate u JSON formatu
}
add_action('wp_ajax_lms_live_search', 'lms_live_search_handler');
add_action('wp_ajax_nopriv_lms_live_search', 'lms_live_search_handler');

function lms_render_live_search() {
    ?>
    <script>
    alert("Live search script loaded!");

jQuery(document).ready(function ($) {
    const searchInput = $('#live-search');
    const resultsDiv = $('#search-results');

    searchInput.on('input', function () {
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

    </script>
    <div class="movie-search">
        <input type="text" id="live-search" placeholder="Pretraži filmove..." autocomplete="off">
        <div id="search-results"></div>
    </div>
    <?php
}

