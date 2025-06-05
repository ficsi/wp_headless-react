<?php
use Roots\Acorn\Application;

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our theme. We will simply require it into the script here so that we
| don't have to worry about manually loading any of our classes later on.
|
*/

if (! file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    wp_die(__('Error locating autoloader. Please run <code>composer install</code>.', 'sage'));
}

require $composer;

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
|
| The first thing we will do is schedule a new Acorn application container
| to boot when WordPress is finished loading the theme. The application
| serves as the "glue" for all the components of Laravel and is
| the IoC container for the system binding all of the various parts.
|
*/

Application::configure()
    ->withProviders([
        App\Providers\ThemeServiceProvider::class,
    ])
    ->boot();

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

collect(['setup', 'filters'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                /* translators: %s is replaced with the relative file path */
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
            );
        }
    });

/*
|--------------------------------------------------------------------------
| Register Present CPT
|--------------------------------------------------------------------------
*/
function register_presents_post_type(): void
{
    $labels = [
        'name'               => 'Presents',
        'singular_name'      => 'Present',
        'menu_name'          => 'Presents',
        'name_admin_bar'     => 'Present',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Present',
        'new_item'           => 'New Present',
        'edit_item'          => 'Edit Present',
        'view_item'          => 'View Present',
        'all_items'          => 'All Presents',
        'search_items'       => 'Search Presents',
        'parent_item_colon'  => 'Parent Presents:',
        'not_found'          => 'No presents found.',
        'not_found_in_trash' => 'No presents found in Trash.',
    ];

    $args = [
        'public' => true,         // Must be true
        'show_ui' => true,        // Ensures it's shown in the WP admin
        'labels'             => $labels,
        'has_archive'        => true,
        'show_in_rest'       => true, // 🔥 Important for GraphQL and REST API
        'rewrite'            => [ 'slug' => 'presents' ],
        'supports'           => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-gift',
    ];
    register_post_type( 'presents', $args );
}
add_action( 'init', 'register_presents_post_type' );
function add_graphql_to_presents_post_type( $args, $post_type ) {
    if ( 'presents' === $post_type ) {
        $args['show_in_graphql'] = true;
        $args['graphql_single_name'] = 'Present';
        $args['graphql_plural_name'] = 'Presents';
    }
    return $args;
}
add_filter( 'register_post_type_args', 'add_graphql_to_presents_post_type', 10, 2 );
