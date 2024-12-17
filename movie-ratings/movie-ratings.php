<?php
/*
Plugin Name: Movie Ratings
Description: Dodavanje i prikaz prosečnih ocena filmova
Version: 1.0
Author: Vaše ime
*/

// Funkcija za dodavanje ocena
function dodaj_ocenu($post_id, $ocena) {
    $ocene = get_post_meta($post_id, '_ocene', true);

    if (!$ocene) {
        $ocene = []; // Ako ocene ne postoje, kreiraj prazan niz
    }

    $ocene[] = $ocena;
    update_post_meta($post_id, '_ocene', $ocene);
}

// Funkcija za izračunavanje prosečne ocene
function izracunaj_prosecnu_ocenu($post_id) {
    $ocene = get_post_meta($post_id, '_ocene', true);

    if (!$ocene || count($ocene) === 0) {
        return "Još nema ocena.";
    }

    $prosek = array_sum($ocene) / count($ocene);
    return round($prosek, 2); // Zaokružuje na jednu decimalu
}

// AJAX funkcija za dodavanje ocene
function ajax_dodaj_ocenu() {
    // Provera da li su postavljeni parametri
    if (isset($_POST['ocena']) && isset($_POST['post_id'])) {
        $ocena = intval($_POST['ocena']);
        $post_id = intval($_POST['post_id']);

        // Provera da li je ocena u validnom opsegu
        if ($ocena >= 1 && $ocena <= 10) {
            dodaj_ocenu($post_id, $ocena);
            wp_send_json_success('Ocena je uspešno dodata.');
        } else {
            wp_send_json_error('Ocena mora biti između 1 i 10.');
        }
    }

    wp_die(); // Zatvori AJAX zahtev
}

// Registracija AJAX akcija za ulogovane i nelogovane korisnike
add_action('wp_ajax_dodaj_ocenu', 'ajax_dodaj_ocenu');
add_action('wp_ajax_nopriv_dodaj_ocenu', 'ajax_dodaj_ocenu');

// Učitavanje JavaScript-a za AJAX
function movie_ratings_ajax_script() {
    ?>
    <script>
        document.getElementById("btn1").addEventListener("click", function (e) {
            e.preventDefault();

            const ocena = document.getElementById("value").innerText;
            const postId = <?php echo get_the_ID(); ?>;

            if (ocena) {
                fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: `action=dodaj_ocenu&ocena=${ocena}&post_id=${postId}`,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Hvala na oceni!");
                        location.reload(); // Osvježavanje stranice za prikaz nove prosečne ocene
                    } else {
                        alert(data.data); // Prikazuje grešku ako nije uspešno
                    }
                })
                .catch(error => console.error("Greška:", error));
            }
        });
    </script>
    <?php
}
add_action('wp_footer', 'movie_ratings_ajax_script');
