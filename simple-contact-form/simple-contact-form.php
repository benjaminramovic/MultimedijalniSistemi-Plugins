<?php
/**
 * Plugin Name: Simple contact form
 * Description: Plugin uvod
 * Version:1.0
 * Author: Benjamin Ramovic
 * Author URI: http://URI_Of_The_Plugin_Author
 * Text Domain: simple-contact-form
 */
?>

<?php

if(!defined('ABSPATH')){
    echo "What are you trying to do ?";
    exit;
}

class SimpleContactForm 
{
    public function __construct(){
        add_action('init',array($this, 'create_custom_post_type'));
        add_action('wp_enqueue_scripts',array($this, 'load_assets'));
        add_shortcode('contact-form',array($this,'load_shortcode'));
        add_action('wp_footer',array($this,'load_scripts')); //jquery
        add_action('rest_api_init',array($this,'register_rest_api'));
    }
    public function create_custom_post_type(){
        $args = array(
            'public' => true,
            'has_archive' => 'true',
            'supports' => array('title'), //polja koja ce biti u formi
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capability' => 'manage_options',
            'labels' => array(
                'name' => 'Contact form',
                'singular_name' => 'Basic contact form using plugins'
            ),
            'menu_icon' => 'dashicons-media-text'
        );
        register_post_type('simple-contact-form',$args);
    }
    public function load_assets(){
        wp_enqueue_style(
            'simple-contact-form',
            plugin_dir_url( __FILE__ ) . 'css/simple-contact-form.css',
            array(),
            1,
            'all'
        );
        wp_enqueue_script(
            'simple-contact-form',
            plugin_dir_url( __FILE__ ) . 'js/simple-contact-form.js',
            array('jquery'),
            1,
            true
        );
    }
    public function load_shortcode()
    {?>
        <div class="contact-form">
            <h1>Add new contact</h1>
            <form id="contact-form__form">
                <input type="text" name="name" placeholder="Ime">
                <input type="email" name="email" placeholder="Email">
                <input type="tel" name="tel" placeholder="Phone">
                <textarea name="message" cols="30" rows="10" placeholder="Type a message..."></textarea>
                <button type="submit">Add contact</button>
            </form>
        </div>
    <?php }

    public function load_scripts()
    {?>
        <script>
            var nonce = '<?php echo wp_create_nonce('wp_rest');?>';
           // alert("AAAAAAAA")
            (function($) {
                $('#contact-form__form').submit( function(event) {
                    event.preventDefault();
                   
                    //serialize
                    var form = $(this).serialize();
                    console.log(form)

                    //send data to backend
                    console.log('Form submitted');
                    console.log(nonce);
            


                    $.ajax({
                        method:'post',
                        url: '<?php echo get_rest_url(null, 'simple-contact-form/v1/send-email'); ?>',
                        headers: {'X-WP-Nonce':nonce },
                        data: form
                    })
                });
            })(jQuery)
            
        </script>
    <?php }

    public function register_rest_api(){

        register_rest_route('simple-contact-form/v1','/send-email',array(
                'methods' => 'POST',
                'callback' => array($this,'handle_contact_form')
            )
        );

    }
    
    public function handle_contact_form($data){
        echo "This endpoint is working!";

        $post_id = wp_insert_post([
            'post_type' => 'simple-contact-form',
            'post_title' => 'Contact enquiry',
            'post_status' => 'publish'
        ]);
        if($post_id) {
            return new WP_REST_Response('Thank you for your email!');
        }
    }
}
new SimpleContactForm();

?>