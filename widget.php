<?php

namespace ARCHIVE_FRESHENER;

add_action('admin_init', __NAMESPACE__ . '\process_query_args');
function process_query_args() {
  if (isset($_GET['lfaf_archive_action']) && isset($_GET['lfaf_post_id'])) {
    $action   = sanitize_key($_GET['lfaf_archive_action']);
    $post_id  = absint(intval($_GET['lfaf_post_id']));

    switch ($action) {
      case 'skip' :
        skip_post($post_id);
        break;
      case 'ignore' :
        ignore_post($post_id);
        break;
    }
  }
}


add_action('wp_dashboard_setup', __NAMESPACE__ . '\add_widget');
function add_widget() {
  wp_add_dashboard_widget(
    'archive_freshener_widget',
    'Freshen Up Your Archives <a href="' . get_admin_url('', 'options-writing.php') . '">' . __('Options', 'little-free-archive-freshener') . '</a>',
    __NAMESPACE__ . '\widget',
    null,
    null,
    'normal',
    'high',
  );
}


function widget() {
  /**
   * Checks to see if the lfaf_clear_ignored option has come true and if so,
   * deletes the wp_archive_updater_ignored and lfaf_clear_ignored options,
   * clearing the list of ignored posts.
   */
  if (get_option('lfaf_clear_ignored')) {
    delete_option('wp_archive_updater_ignored');
    delete_option('lfaf_clear_ignored');
  }

  /**
   * Checks for today's post ID, or else gets a new post ID.
   */
  if (get_transient('wp_archive_updater_todays_page')) {
    $post_id = get_transient('wp_archive_updater_todays_page');
    /**
     * Checks to see whether the currently stored post ID has been updated more
     * recently than the expiration date option and gets a new one if so.
     */
    $current_date           = new \DateTime(strtotime(date(get_option('Y-m-d'))));
    $last_update_date       = new \DateTime(get_the_modified_date('Y-m-d', $post_id));
    $days_since_last_update = $last_update_date->diff($current_date)->days;
    $expiration_date        = get_option('lfaf_expiration_date');

    if ($days_since_last_update <= $expiration_date) {
      delete_transient('wp_archive_updater_todays_page');
      $post_id = get_new_post();
    }
  } else {
    $post_id = get_new_post();
  }

  /**
   * Outputs the widget.
   */
  if (current_user_can('edit_others_posts')) {
    if ($post_id) {
      $post_url           = get_edit_post_link($post_id);
      $post_title         = get_the_title($post_id);
      $post_last_modified = esc_html(human_time_diff(get_the_modified_date('U', $post_id), current_time('timestamp')));

      echo '<p>' . __('This looks like it could use an update:', 'little-free-archive-freshener') . '</p>';
      echo '<strong style="font-size: 150%;"><a href="' . $post_url . '" title="' . __('Click to edit this.', 'little-free-archive-freshener') . '">' . $post_title . '</a></strong>';
      echo '<br /><small>' . sprintf(__('It was last updated %1$s ago.', 'little-free-archive-freshener'), $post_last_modified) . '</small>';

      $skip_link    = '<a href="' . add_query_arg(['lfaf_post_id' => $post_id, 'lfaf_archive_action' => 'skip']) . '">' . __('skip it for now', 'little-free-archive-freshener') . '</a>';
      $ignore_link  = '<a href="' . add_query_arg(['lfaf_post_id' => $post_id, 'lfaf_archive_action' => 'ignore']) . '">' . __('ignore it forever', 'little-free-archive-freshener') . '</a>';
      $post_query   = new \WP_Query(get_new_post_args());

      echo '<p><small>' . sprintf(__('Update it to get a new suggestion (there are %1$s more). You can also %2$s or %3$s.', 'little-free-archive-freshener'), $post_query->found_posts - 1, $skip_link, $ignore_link) . '</small></p>';
    } else {
      echo '<p>' . __('There\'s nothing left to update!', 'little-free-archive-freshener') . '</p>';
      echo '<p><small>' . sprintf(__('(If this doesn\'t seem right, try %1$sadding more post types%2$s.)', 'little-free-archive-freshener'), '<a href="' . get_admin_url('', 'options-writing.php') . '">', '</a>') . '</small></p>';
    }
  } else {
    global $wp_roles;
    $roles_with_edit_others_posts = [];

    foreach ($wp_roles->roles as $role) {
      if ($role['capabilities']['edit_others_posts']) $roles_with_edit_others_posts[] = '<strong>' . $role['name'] . '</strong>';
    }

    $formatted_roles = get_formatted_list($roles_with_edit_others_posts);

    echo '<p><strong>' . __('Sorry, you don\'t have permision to update posts.', 'little-free-archive-freshener') . '</strong></p>';
    echo '<p><small>' . __('In order to use this plugin you must have one of these user roles:', 'little-free-archive-freshener') . ' ' . $formatted_roles . '.</small></p>';
    echo '<p><small>' . __('Or you can hide this widget by clicking on the <strong>Screen Options</strong> tab at the top of this page.') . '</small></p>';
  }
}


function get_new_post() {
  $post_query = new \WP_Query(get_new_post_args());
  if ($post_query->have_posts()) : while ($post_query->have_posts()) : $post_query->the_post();
    $post_id = get_the_ID();
  endwhile; endif;

  if (isset($post_id) && $post_id) {
    $now                  = time();
    $midnight             = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'));
    $seconds_to_midnight  = $midnight - $now;

    set_transient('wp_archive_updater_todays_page', $post_id, $seconds_to_midnight);
    return $post_id;
  } else {
    return false;
  }
}

function get_new_post_args() {
  if (get_option('wp_archive_updater_ignored')) {
    $exclude = get_option('wp_archive_updater_ignored');
  } else {
    $exclude = array();
  }

  $expiration_date  = get_option('lfaf_expiration_date');
  $post_types       = get_option('lfaf_included_post_types');

  $args = [
    'date_query'      => [
      [
        'column'  => 'post_modified_gmt',
        'before'  => $expiration_date . ' days ago',
      ],
    ],
    'fields'          => 'ids',
    'orderby'         => 'rand',
    'post__not_in'    => $exclude,
    'posts_per_page'  => 1,
    'post_status'     => 'publish',
    'post_type'       => $post_types,
  ];
  return $args;
}


function skip_post($post_id) {
  if (get_transient('wp_archive_updater_todays_page') && $post_id == get_transient('wp_archive_updater_todays_page')) {
    delete_transient('wp_archive_updater_todays_page');
  }
}


function ignore_post($post_id) {
  if (get_option('wp_archive_updater_ignored')) {
    $ignored_posts = get_option('wp_archive_updater_ignored');

    if (!in_array($post_id, $ignored_posts)) {
      $ignored_posts[] = $post_id;
      update_option('wp_archive_updater_ignored', $ignored_posts);
    }
  } else {
    update_option('wp_archive_updater_ignored', [$post_id]);
  }
  skip_post($post_id);
}


function get_formatted_list($array, $conjunction = 'or') {
  ob_start();
    $count = count($array);

    switch ($count) {
      case 1:
        echo $array[0];
        break;
      case 2:
        echo $array[0] . ' ' . $conjunction . ' ' . $array[1];
        break;
      default:
        $key = 1;
        foreach ($array as $entry) {
          echo $entry;
          if ($key < $count) echo ', ';
          if ($key == $count - 1) echo $conjunction . ' ';
          $key++;
        }
    }
  return ob_get_clean();
}
