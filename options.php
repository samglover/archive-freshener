<?php

namespace ARCHIVE_FRESHENER;

add_action('admin_init', __NAMESPACE__ . '\options_init');
function options_init() {
  add_settings_section(
    'lfaf-options',
    __('Little Free Archive Freshener Options', 'little-free-archive-freshener'),
    __NAMESPACE__ . '\lfaf_options_section',
    'writing',
  );

  register_setting('writing', 'lfaf_expiration_date');
  add_settings_field(
    'lfaf_expiration_date',
    '<label for="lfaf_expiration_date">' . __('Freshen After', 'little-free-archive-freshener') . '</label>',
    __NAMESPACE__ . '\lfaf_expiration_date',
    'writing',
    'lfaf-options',
  );

  register_setting('writing', 'lfaf_included_post_types');
  add_settings_field(
    'lfaf_included_post_types',
    '<label for="lfaf_included_post_types">' . __('Include Post Types', 'little-free-archive-freshener') . '</label>',
    __NAMESPACE__ . '\lfaf_included_post_types',
    'writing',
    'lfaf-options',
  );

  register_setting('writing', 'lfaf_clear_ignored');
  add_settings_field(
    'lfaf_clear_ignored',
    '<label for="lfaf_clear_ignored">' . __('Clear Ignored Posts', 'little-free-archive-freshener') . '</label>',
    __NAMESPACE__ . '\lfaf_clear_ignored',
    'writing',
    'lfaf-options',
  );
}


function lfaf_options_section() {
  echo '<p>' . __('Options for the Freshen Up Your Archives dashboard widget.', 'little-free-archive-freshener') . '</p>';
}


function lfaf_expiration_date() {
  $expiration_date = get_option('lfaf_expiration_date');
  echo '<input name="lfaf_expiration_date" class="small-text" type="number" required aria-required="true" value="' . $expiration_date . '"> ' . __('days', 'little-free-archive-freshener');
  echo '<p class="description">' . sprintf(__('Items older than this will be fetched. Items that have been updated within the last %1$s days are "fresh" and will not be fetched.', 'little-free-archive-freshener'), $expiration_date) . '</p>';
}

function lfaf_included_post_types() {
  $post_types           = get_post_types(['public' => true]);
  $included_post_types  = get_option('lfaf_included_post_types');

  ob_start();
    echo '<fieldset>';
      foreach ($post_types as $key => $post_type) {
        if (in_array($post_type, $included_post_types)) {
          $checked = ' checked';
        } else {
          $checked = '';
        }
        echo '<p><label for="lfaf_included_post_types[' . $key . ']">';
          echo '<input name="lfaf_included_post_types[' . $key . ']" id="lfaf_included_post_types[' . $key . ']" type="checkbox" value="' . $post_type . '"' . $checked . '>';
          echo $post_type;
        echo '</label></p>';

        unset($checked);
      }
    echo '</fieldset>';
  echo ob_get_clean();
}


function lfaf_clear_ignored() {
  ob_start();
    echo '<fieldset>';
      echo '<p><label for="lfaf_clear_ignored">';
        echo '<input name="lfaf_clear_ignored" id="lfaf_clear_ignored" type="checkbox" value="1">';
        _e('Clear the list of ignored posts.', 'little-free-archive-freshener');
      echo '</label></p>';
      echo '<p class="description">' . __('If you check this box and then save changes, it will clear the list of items for which you have previously clicked "ignore forever." This box will not remain checked after you save changes.', 'little-free-archive-freshener') . '</p>';
    echo '</fieldset>';
  echo ob_get_clean();
}
