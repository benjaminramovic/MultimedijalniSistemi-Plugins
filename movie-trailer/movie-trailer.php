<?php
/**
 * Plugin Name: Movie Trailer
 * Plugin URI: http://example.com
 * Description: Plugin za dodavanje trejlera filma na stranicu za pojedinačni film.
 * Version: 1.0
 * Author: Tvoje ime
 * Author URI: http://example.com
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Funkcija za prikazivanje trejlera
function movie_trailer_display( $content ) {
    if ( is_singular( 'movie' ) ) { // Proverava da li je stranica za pojedinačni film
        $trailer_url = get_post_meta( get_the_ID(), '_movie_trailer_url', true ); // Uzima URL trejlera iz custom field-a
        if ( $trailer_url ) {
            $content .= '<div class="movie-trailer">';
            $content .= '<h3>Trejler</h3>';
            $content .= '<iframe width="560" height="315" src="' . esc_url( $trailer_url ) . '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
            $content .= '</div>';
        }
    }
    return $content;
}
add_filter( 'the_content', 'movie_trailer_display' );

// Funkcija za dodavanje polja za unos URL-a trejlera
function movie_trailer_meta_box() {
    add_meta_box(
        'movie_trailer_meta_box', // ID
        'Movie Trailer URL', // Naslov
        'movie_trailer_meta_box_callback', // Callback funkcija
        'movie', // Post type
        'normal', // Gde se prikazuje (normal)
        'high' // Prioritet
    );
}
add_action( 'add_meta_boxes', 'movie_trailer_meta_box' );

// Callback funkcija za prikazivanje input polja
function movie_trailer_meta_box_callback( $post ) {
    wp_nonce_field( 'movie_trailer_save', 'movie_trailer_nonce' );
    $value = get_post_meta( $post->ID, '_movie_trailer_url', true );
    echo '<label for="movie_trailer_url">URL Trejlera: </label>';
    echo '<input type="text" id="movie_trailer_url" name="movie_trailer_url" value="' . esc_attr( $value ) . '" size="25" />';
}

// Funkcija za snimanje URL-a trejlera
function movie_trailer_save_post( $post_id ) {
    if ( ! isset( $_POST['movie_trailer_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['movie_trailer_nonce'], 'movie_trailer_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( isset( $_POST['movie_trailer_url'] ) ) {
        update_post_meta( $post_id, '_movie_trailer_url', sanitize_text_field( $_POST['movie_trailer_url'] ) );
    }
}
add_action( 'save_post', 'movie_trailer_save_post' );
