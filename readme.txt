# NMR jsGrid

Contributors: mirceatm
Donate link: https://paypal.me/mirceatm
Tags: JSGrid table jquery javascript ajax pagination
Requires at least: 5.2
Tested up to: 6.1.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: <https://www.gnu.org/licenses/gpl-2.0.html>

== Description ==

Add jsGrid http://js-grid.com tables to your website using the shortcode: `[nmr_jsgrid id='your-grid-name']`.

Data grids or tables have configurable number of columns, filters, edit, update and delete functionalities.
Configuration of grids is performed on the admin back-end.
Tables can be paginated and need back-end pagination. Ajax calls help to avoid entire page reload.

Example of a toy back-end pagination:

`
add_action('wp_ajax_nopriv_sample_pagination', function () {
    // assume HTTP GET for this example
    $data = $_GET;
    global $wpdb;
    $sql_base = $wpdb->prepare("SELECT umeta_id, meta_key FROM {$wpdb->prefix}usermeta");
    $sql = $wpdb->prepare("SELECT COUNT(*) FROM ({$sql_base}) t");
    $itemsCount = $wpdb->get_var($sql);
    $pageIndex = intval($data['pageIndex']);
    if ($pageIndex < 1) {
        $pageIndex = 1;
    }
    $pageSize = intval($data['pageSize']);
    if ($pageSize < 1) {
        $pageSize = 20;
    }
    $endIndex = $pageIndex * $pageSize;
    $startIndex = $endIndex - $pageSize;
    $limit = "LIMIT {$pageSize} OFFSET {$startIndex}";
    $sql = "SELECT t.* FROM ({$sql_base}) t {$limit}";
    wp_send_json(json_encode(['data' => $wpdb->get_results($sql, ARRAY_A), 'itemsCount' => $itemsCount]));
});
`


Having prepared the back-end to provide paginated data, the front-end data-grid cand be built in the Admin->NMR jsGrids interface.
Add a row and set the column values accordingly.
Please consult [the documentation](http://js-grid.com/docs/) for more details:

* `Name` will be used as DOM id for the data-grid
* `URL` back-end endpoint, usually https://your-website.com/wp-admin/admin-ajax.php
* `Action` should be sincronized with back-end. In our example `Action=sample_pagination` taken from: `wp_ajax_nopriv_sample_pagination`
* `Fields` JSON array of columns as text. In our example could be: 
    [{"name": "umeta_id","title": "Id","type": "number"},{"name": "meta_key","title": "Key","type": "text"}]
    For a complete list of possible columns check [the documentation](http://js-grid.com/docs/#grid-fields)
* `Height` could be `auto`, a percent like `80%`, a number: `400`
* `Width` same as above
* `Paging`, `Editing`, `Sorting`, `Autoload`, `Deleting`, `Filtering`, `Inserting`, `Page size` are self explanatory: allow pagination, editing, sorting, auto-loading, deleting, filtering and inserting of data. Page size determines the number of rows in one page.
* `Pager format` can have a value of: *Rows: {itemCount} - Pages: {first} {prev} {pages} {next} {last} &nbsp;&nbsp; {pageIndex} of {pageCount}*
* `Page buttons` 5 is a good choice

Plugin will enqueue `nmr-jsgrid.js` javascript file.
To use the plugin on the Admin module one should add the Admin slug/hook in the filter: `nmr_jsgrid_admin_enqueue_scripts`
Ex:

`
add_filter('nmr_jsgrid_admin_enqueue_scripts', function ($admin_php_files) {
    // NMR jsGrid will be available in Admin->My page->My subpage
    $admin_php_files[] = 'admin-my_page_admin-my_subpage';
    return $admin_php_files;
});
`
If you enjoy using *NMR JSGrid* and find it useful, please consider [__making a donation__](https://paypal.me/mirceatm). Your donation will help encourage and support the plugin's continued development and better user support.

= Privacy Notices =


== Installation ==

1. Upload the entire `nmr-jsgrid` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

== Screenshots ==

![NMR jsGrid 1](screenshot-1.jpg)
![NMR jsGrid 2](screenshot-2.jpg)

== Changelog ==

= 1.0.0 =

* Initial version.
