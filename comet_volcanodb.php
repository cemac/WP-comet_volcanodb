<?php

  /*
   * Plugin Name:       COMET VolcanoDB
   * Plugin URI:        https://github.com/cemac/WP-comet_volcanodb
   * Description:       Plugin to enable embedding of the COMET Volcano Deformation Database in a WordPress site.
   * Version:           0.0.2
   * Author:            CEMAC
   * License:           GNU General Public License v3
   * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
   * GitHub Plugin URI: https://github.com/cemac/WP-comet_volcanodb
   */


  /*
    plugin activation

    comet_volcanodb_activate function is run on plugin activation:
   */
  function comet_volcanodb_activate() {
    /* add options for this plugin: */
    add_option('comet_volcanodb_base_path', 'volcanodb');
    add_option('comet_volcanodb_remote_site', 'https://comet-volcanodb.org');
    add_option('comet_volcanodb_ssl_verify', true);
  };
  register_activation_hook(__FILE__, 'comet_volcanodb_activate');


  /*
    plugin removal

    comet_volcanodb_remove function is run on plugin removal:
   */
  function comet_volcanodb_remove() {
    /* remove options for this plugin: */
    delete_option('comet_volcanodb_base_path');
    delete_option('comet_volcanodb_remote_site');
    delete_option('comet_volcanodb_ssl_verify');
  };
  register_uninstall_hook(__FILE__, 'comet_volcanodb_remove');


  /*
    settings menus

    add bits for setting options via admin menus:
    * comet_volcanodb_settings_html function creates the html for the menu
    * comet_volcanodb_settings_submit handles settings form submission
    * comet_volcanodb_settings registers the menu
   */
  function comet_volcanodb_settings_html() {
    /* check ssl verify option: */
    if (get_option('comet_volcanodb_ssl_verify') == true) {
      $ssl_verify = ' checked';
    } else {
      $ssl_verify = '';
    };
    /* page header: */
    echo '<h2>COMET VolcanoDB options</h2>';
    /* create html form: */
    echo '<form id="comet_volcanodb_form" method="post">';
    /* base path option: */
    echo '<label style="font-weight: bold; display: inline-block; min-width: 8em;">Base path : </label>';
    echo '<input type="text" name="comet_volcanodb_base_path" value="' .
         get_option('comet_volcanodb_base_path') .
         '" style="margin: 2px;"><br>';
    /* remote site option: */
    echo '<label style="font-weight: bold; display: inline-block; min-width: 8em;">Remote site : </label>';
    echo '<input type="text" name="comet_volcanodb_remote_site" value="' .
         get_option('comet_volcanodb_remote_site') .
         '" style="margin: 2px;"><br>';
    /* ssl verify option: */
    echo '<label style="font-weight: bold; display: inline-block; min-width: 8em;">SSL verify : </label>';
    echo '<input type="checkbox" name="comet_volcanodb_ssl_verify"' .
         $ssl_verify .
         ' style="margin: 2px;"><br>';
    /* submit button: */
    echo '<label style="font-weight: bold; display: inline-block; min-width: 8em;"></label>';
    echo '<input type="submit" value="Submit" style="margin: 2px;">';
    /* end html form: */
    echo '</form>';

  };

  function comet_volcanodb_settings_submit() {
    /* check for submitted options: */
    if ((array_key_exists('comet_volcanodb_base_path', $_REQUEST)) &&
        (array_key_exists('comet_volcanodb_remote_site', $_REQUEST))) {
      /* update submitted options: */
      update_option('comet_volcanodb_base_path', $_REQUEST['comet_volcanodb_base_path']);
      update_option('comet_volcanodb_remote_site', $_REQUEST['comet_volcanodb_remote_site']);
      if (array_key_exists('comet_volcanodb_ssl_verify', $_REQUEST)) {
        update_option('comet_volcanodb_ssl_verify', true);
      } else {
        update_option('comet_volcanodb_ssl_verify', false);
      };
    };
  };

  function comet_volcanodb_settings() {
    $hook_name = add_submenu_page(
      'options-general.php',
      'COMET Volcano DB',
      'COMET Volcano DB',
      'manage_options',
      'comet_volcanodb',
      'comet_volcanodb_settings_html'
    );
    add_action( 'load-' . $hook_name, 'comet_volcanodb_settings_submit' );
  };
  add_action('admin_menu', 'comet_volcanodb_settings');


  /*
    function to provide error message if remote content load fails
   */
  function remote_error_html() {
    $error_html = '<!DOCTYPE html>
                   <html>
                     <body>
                       Failed to load content.
                       Return to <a href="' . get_site_url() . '">' .
                       get_site_url() . '</a>
                     </body>
                   </html>';
    return $error_html;
  };


  /*
    function to include remote content

    if the request path matches the configured path, remote content is retrieved
    from the configured site, adjusted as required, and returned:
   */
  function comet_volcanodb_include_content() {
    /* get plugin options ... base patht to look for: */
    $base_path = get_option('comet_volcanodb_base_path');
    /* remote site from which content will be retrieved: */
    $remote_site = get_option('comet_volcanodb_remote_site');
    /* whether to verify certificates in remote rquest: */
    $ssl_verify = get_option('comet_volcanodb_ssl_verify');

    /* check the request uri: */
    $request_uri = $_SERVER['REQUEST_URI'];

    /* check if the request matches the configured path for the plugin: */
    $base_path_len = strlen($base_path);
    if (strncmp($request_uri . '/', '/' . $base_path . '/', $base_path_len + 1) == 0) {

      /* uri path matches ... get the domain name of the remote site: */
      preg_match('/^(https?:\/\/)([^\/]+)/i', $remote_site, $matches);
      $remote_domain = $matches[2];

      /* get the domain of the local site: */
      $local_site = get_site_url();
      preg_match('/^(https?:\/\/)([^\/]+)/i', $local_site, $matches);
      $local_domain = $matches[2];

      /* parse the uri: */
      $request_url = parse_url($request_uri);

      /* url path: */
      $request_path = $request_url['path'];
      /* remove plugin base path: */
      $search = '/^\/' . $base_path . '/';
      $request_path = preg_replace($search, '', $request_path);
      /* query string: */
      isset($request_url['query']) && $request_query = $request_url['query'];
      /* fragment: */
      isset($request_url['fragment']) && $request_fragment = $request_url['fragment'];

      /* build remote path: */
      $remote_path = '';
      !empty($request_path) && $remote_path = $remote_path . $request_path;
      isset($request_query) && $remote_path = $remote_path . '?' . $request_query;
      isset($request_fragment) && $remote_path = $remote_path . '#' . $request_fragment;

      /* check for POST: */
      if (is_array($_POST) && count($_POST) > 0) {
        $request_type = 'POST';
        $request_body = $_POST;
      } else {
        $request_type = 'GET';
        $request_body = array();
      };

      /* check for session cookie ... init request_cookie as empty array: */
      $request_cookie = array();
      if (is_array($_COOKIE) && count($_COOKIE) > 0) {
        /* spin through cookies ... */
        foreach ($_COOKIE as $cookie => $value) {
          /* if this is the session cookie: */
          if ($cookie == 'session') {
            /* send this cookie to remote site: */
            $request_cookie[$cookie] = $value;
          };
        };
      };

      /* request arguments: */
      $request_args = array(
        'sslverify' => $ssl_verify,
        'body' => $request_body,
        'cookies' => $request_cookie
      );

      /* make the request: */
      if ($request_type == 'POST') {
        $request = wp_remote_post($remote_site . $remote_path, $request_args);
      } else {
        $request = wp_remote_request($remote_site . $remote_path, $request_args);
      };

      /* check for request errors: */
      if (is_wp_error($request)) {
        $content = remote_error_html();
      } else {
        $content = $request['body'];
      };

      /* check for returned cookies: */
      if (!is_wp_error($request) &&
          is_array($request['cookies']) &&
          count($request['cookies']) > 0) {
        /* spin through cookies ... */
        foreach ($request['cookies'] as $cookie) {
          /* if remote domain matches: */
          if ($cookie->domain == $remote_domain) {
            /* set the cookie: */
            setcookie(
              $cookie->name,
              $cookie->value,
              $cookie->expires,
              '/' . $base_path,
              $local_domain
            );
          };
        };
      };

      /* update relative links: */
      $search = '/(<link[^>]+href=[\"\']?)\//';
      $replace = '${1}' . $remote_site . '/';
      $content = preg_replace($search, $replace, $content);

      /* update relative images: */
      $search = '/(src=[\"\']?)\//';
      $replace = '${1}' . $remote_site . '/';
      $content = preg_replace($search, $replace, $content);
      /* no leading slash ... : */
      $search = '/(src=[\"\']?)(\w+\/)/';
      $replace = '${1}' . $remote_site . '/${2}';
      $content = preg_replace($search, $replace, $content);

      /* prefixes used in javascript plotting ... : */
      $search = '/(_prefix = \')\//';
      $replace = '${1}' . $remote_site . '/';
      $content = preg_replace($search, $replace, $content);

      /* update forms: */
      $search = '/(action=[\"\']?)\//';
      $replace = '${1}/' . $base_path . '/';
      $content = preg_replace($search, $replace, $content);

      /* update links: */
      $search = '/(<a[^>]+href=[\"\']?)\//';
      $replace = '${1}/' . $base_path . '/';
      $content = preg_replace($search, $replace, $content);

      /* update csv downloads: */
      $search = '/(<a href=")\/' . $base_path . '(\/volcano-index\/.*\/download)/';
      $replace = '${1}' . $remote_site . '${2}';
      $content = preg_replace($search, $replace, $content);

      /* return the content: */
      echo $content;

      /* exit: */
      exit;
    };
  };
  add_action('init', 'comet_volcanodb_include_content');

?>
