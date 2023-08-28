(function($) {

  // Modals
  $(document).ready(function(){

    if ($('#archive_freshener_widget').length > 0) {

      /**
       * This just tidies up (removes) the URL parameters. (By the time the DOM
       * is ready they will have already been processed by the
       * process_query_args() function in widget.php.)
       */
      let baseURL       = [location.protocol, '//', location.host, location.pathname].join('');
      const params      = new URLSearchParams(location.search);

      params.delete('lfaf_archive_action');
      params.delete('lfaf_post_id');

      if (params.toString().length > 0) {
        history.replaceState({}, '', baseURL + '?' + params);
      } else {
        history.replaceState({}, '', baseURL);
      }

    }

  });


})(jQuery);
