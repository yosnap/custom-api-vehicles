<?php
function get_author_data($author_id) {
    $author = get_user_by('ID', $author_id);
    if (!$author) {
        return null;
    }
    return [
        'id' => $author->ID,
        'name' => $author->display_name,
        'email' => $author->user_email,
    ];
}

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/author/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_author_data',
    ]);
});