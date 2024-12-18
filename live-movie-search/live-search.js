
document.getElementById('movie-search-input').addEventListener('keyup', function () {
    let searchQuery = this.value;
    let movieListContainer = document.querySelector('.movie-list');

    fetch(movieSearchAjax.ajax_url + '?action=real_time_movie_search&query=' + searchQuery)
        .then(response => response.text())
        .then(data => {
            movieListContainer.innerHTML = data;
        })
        .catch(error => console.error('Greška pri pretrazi:', error));
});
