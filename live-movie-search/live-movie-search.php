<?php
/*
Plugin Name: Live Movie Search
Description: AJAX real-time pretraga za custom post type "movies".
Version: 1.0
Author: Vaše Ime
*/

// Osigurajte da se kod ne izvršava direktno
if (!defined('ABSPATH')) {
    exit;
}


// Enqueue JavaScript fajla i lokalizacija za AJAX
function rtm_enqueue_scripts() {
    wp_enqueue_script('rtm-real-time-search', plugin_dir_url(__FILE__) . 'live-search.js', ['jquery'], null, true);
    wp_localize_script('rtm-real-time-search', 'movieSearchAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'rtm_enqueue_scripts');

// AJAX funkcija za pretragu
function rtm_real_time_movie_search() {
    $query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';

    $args = [
        'post_type' => 'movies',
        'posts_per_page' => -1,
        's' => $query,
    ];

    $search_query = new WP_Query($args);

    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            
            $image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
            if (!$image_url) {
                $movie_image = get_post_meta(get_the_ID(), '_movie_image', true);
                $image_url = $movie_image ? $movie_image : wp_upload_dir()['baseurl'] . '/2024/11/gvh.jpg';
            }
            ?>
            <div class="list-item" style="background:url('<?php echo esc_url($image_url); ?>'); background-position: center; background-size: cover;">
                <div class="list-item-details">
                    <p><?php the_title(); ?></p>
                    <?php $movie_rating = get_post_meta(get_the_ID(), '_movie_rating', true); ?> 
                    <p>⭐ <?php echo $movie_rating; ?></p>
                    <button><a id="movie-title" href="<?php the_permalink(); ?>">Pogledaj detalje</a></button>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<p>Nema rezultata za pretragu.</p>';
    }

    wp_reset_postdata();
    die();
}
add_action('wp_ajax_real_time_movie_search', 'rtm_real_time_movie_search');
add_action('wp_ajax_nopriv_real_time_movie_search', 'rtm_real_time_movie_search');
