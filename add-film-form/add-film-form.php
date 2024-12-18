<?php
/**
 * Plugin Name: Film Submission Form
 * Description: Plugin za unos novih filmova koristeći custom post type "films".
 * Version: 1.0
 * Author: Benjamin Ramovic
 * Author URI: http://URI_Of_The_Plugin_Author
 * Text Domain: add-film-form
 */

if (!defined('ABSPATH')) {
    echo "What are you trying to do?";
    exit;
}
/* if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
}
 */

class AddfilmForm
{
    public function __construct()
    {
        add_action('init', array($this, 'create_custom_post_type'));  // Dodavanje custom post type-a
        add_action('wp_enqueue_scripts', array($this, 'load_assets'));
        add_shortcode('film-form', array($this, 'load_shortcode'));
        add_action('wp_footer', array($this, 'load_scripts')); // Dodaj JavaScript za AJAX
        add_action('rest_api_init', array($this, 'register_rest_api'));
        add_filter('the_content', 'display_average_rating');
   
    }

    public function create_custom_post_type(){
        $args = array(
            'labels' => array(
                'name' => 'Dodaj film',
                'singular_name' => 'Film',
                'add_new' => 'Dodaj novi film',
                'add_new_item' => 'Dodaj novi film',
                'edit_item' => 'Izmeni film',
                'new_item' => 'Novi film',
                'view_item' => 'Pogledaj film',
                'search_items' => 'Pretraži filmove',
                'not_found' => 'Nema filmova',
                'not_found_in_trash' => 'Nema filmova u kanti',
                'all_items' => 'Svi filmovi',
                'archives' => 'Arhiva filmova',
                'taxonomies' => array('zanr', 'tag'), // Dodato za taksonomije
            ),
            'public' => true,
            'has_archive' => true, // Omogućava arhivu
            'rewrite' => array('slug' => 'filmovi'), // URL slug za arhivu filmova
            'supports' => array('title', 'editor', 'genre', 'author', 'thumbnail', 'comments'), // Podrška za naslov, sadržaj, sliku i izvod
        
        );
    
        register_post_type('movies', $args); // Kreiraj post tip 'films'
    }

    public function load_assets()
    {
        wp_enqueue_style(
            'add-film-form',
            plugin_dir_url(__FILE__) . 'css/add-film-form.css',
            array(),
            1,
            'all'
        );

        wp_enqueue_script(
            'add-film-form',
            plugin_dir_url(__FILE__) . 'js/add-film-form.js',
            array('jquery'),
            1,
            true
        );
    }

    public function load_shortcode()
    { ?>
        <div class="movie-form">
            <div id="message" class="hide">
                Uspešno dodavanje novog filma!
            </div>
            <h1>Dodaj novi film</h1>
            <form id="movie-form__form" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Naziv filma" required>
                <input type="text" name="_movie_year" placeholder="Godina" required>
                <textarea name="_movie_plot" cols="30" rows="5" placeholder="Radnja filma" required></textarea>
                <select style="background-color:white;" name="zanr" required>
                    <option value="">Izaberite žanr</option>
                    <?php
                    $terms = get_terms(array(
                        'taxonomy' => 'zanr',
                        'hide_empty' => false,
                    ));
                    foreach ($terms as $term) {
                        echo '<option value="' . $term->term_id . '">' . $term->name . '</option>';
                    }
                    ?>
                </select>
                <input type="file" name="_movie_image" id="_movie_image" accept="image/*" required>
                <input type="url" name="_movie_trailer_url" placeholder="URL trejlera" required>


                <button type="submit">Dodaj</button>
            </form>

        </div>
    <?php }

    public function load_scripts()
    { ?>
        <script>
           
            var nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
            (function ($) {
    $('#movie-form__form').submit(function (event) {
        event.preventDefault();

        // Kreirajte FormData objekat da biste obradili fajlove
        var formData = new FormData(this);  // 'this' je forma

        // Dodajte nonce za sigurnost
        formData.append('nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');


        // Pošaljite AJAX zahtev
        $.ajax({
            method: 'POST',
            url: '<?php echo get_rest_url(null, 'add-film-form/v1/submit-film'); ?>',
            data: formData,
            processData: false,  // Ne obradjujemo podatke (ne želimo da jQuery menja FormData)
            contentType: false,  // Ne postavljamo content-type, jer ga browser automatski postavlja za FormData
            success: function (response) {
                console.log(response); // Ispisuje ceo odgovor
                //alert(response.message);
                document.getElementById("message").classList.toggle('hide');                    
                setTimeout(() => {
                    document.getElementById("message").classList.toggle('hide');                    
                }, 1500);
                $('#movie-form__form')[0].reset(); // Resetujte formu
            },
            error: function (error) {
                console.log("Error: " + error.responseText);
                console.log("Status: " + error.status);
                console.log(nonce)
                console.log(error);
            }
        });
    });
})(jQuery);

        </script>
    <?php }

    public function register_rest_api()
    {
        register_rest_route('add-film-form/v1', '/submit-film', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_film_submission'),
           // Proverite da li korisnik ima prava
        ));
    }

    public function handle_film_submission($data)
    {
   
        // Preuzimanje i sanitizacija podataka iz zahteva
        $title = sanitize_text_field($data['title']);
        $year = sanitize_text_field($data['_movie_year']);
        $description = sanitize_textarea_field($data['_movie_plot']);
        $trailer_url = esc_url_raw($data['_movie_trailer_url']); // URL trejlera

        
        // Validacija obaveznih polja
        if (empty($title) || empty($year) || empty($description)) {
            return new WP_REST_Response(array('message' => 'All fields are required.'), 400);
        }
        
        // Kreiranje novog posta
        $post_id = wp_insert_post(array(
            'post_type' => 'movies',
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'meta_input' => array(
                '_movie_year' => $year,
                '_movie_trailer_url' => $trailer_url, // Čuvanje URL-a trejlera
            ),
        ));

        // Provera da li je post uspešno kreiran
        if (!$post_id) {
            return new WP_REST_Response(array('message' => 'Failed to submit film.'), 500);
        }
        

        if (isset($_FILES['_movie_image']) && !empty($_FILES['_movie_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
    
            $uploaded_image = media_handle_upload('_movie_image', $post_id);
    
            if (is_wp_error($uploaded_image)) {
                $error_message = $uploaded_image->get_error_message();
                return new WP_REST_Response(array('message' => 'Failed to upload image: ' . $error_message), 500);
            }
    
            // Postavljanje slike kao featured image
            set_post_thumbnail($post_id, $uploaded_image);
        } else {
            return new WP_REST_Response(array('message' => 'No image uploaded.'), 400);
        }
            
        


        // Dodavanje dodatnih meta-podataka ako su prisutni
        if (isset($data['_movie_plot'])) {
            update_post_meta($post_id, '_movie_plot', sanitize_textarea_field($data['_movie_plot']));
        }
        if (isset($data['_movie_cast'])) {
            update_post_meta($post_id, '_movie_cast', sanitize_text_field($data['_movie_cast']));
        }
        /* if (isset($data['_movie_rating'])) {
            update_post_meta($post_id, '_movie_rating', sanitize_text_field($data['_movie_rating']));
        } */
        if (isset($data['_movie_rating'])) {
            $rating = floatval(sanitize_text_field($data['_movie_rating']));
            $existing_ratings = get_post_meta($post_id, '_movie_ratings', true);
        
            if (!$existing_ratings) {
                $existing_ratings = [];
            } else {
                $existing_ratings = maybe_unserialize($existing_ratings);
            }
        
            $existing_ratings[] = $rating;
        
            update_post_meta($post_id, '_movie_ratings', maybe_serialize($existing_ratings));
        }
        

        // Dodavanje žanra
        $zanr = intval($data['zanr']);
        if (!term_exists($zanr, 'zanr')) {
            return new WP_REST_Response(array('message' => 'Izabrani žanr ne postoji.'), 400);
        }

        wp_set_post_terms($post_id, array($zanr), 'zanr');

        // Uspešan odgovor
        return new WP_REST_Response(array('message' => 'Film submitted successfully!'), 200);
    }

    
    
}

// Pokreće klasu
new AddfilmForm();

function display_average_rating($content) {
    if (is_singular('movies')) {
        global $post;

        $ratings = get_post_meta($post->ID, '_movie_ratings', true);

        if ($ratings) {
            $ratings = maybe_unserialize($ratings);

            if (is_array($ratings) && !empty($ratings)) {
                $average_rating = array_sum($ratings) / count($ratings);
                $average_rating = number_format($average_rating, 1);

                $content .= '<div class="average-rating">';
                $content .= '<h3>Prosečna ocena: ' . $average_rating . ' / 10</h3>';
                $content .= '</div>';
            }
        }
    }

    return $content;
} 

