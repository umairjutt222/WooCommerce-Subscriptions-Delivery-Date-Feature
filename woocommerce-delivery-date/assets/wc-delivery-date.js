jQuery(document).ready(function($) {

  $(document).on('change', 'select[name="delivery_date"]', function() {
      var deliveryDate = $(this).val();
      $.ajax({
          type: 'POST',
          url: wc_delivery_date.ajax_url,
          data: {
              action: 'update_delivery_date',
              delivery_date: deliveryDate
          },
          success: function(response) {
              if (response.success) {
                  location.reload();
              }
          }
      });
  });
});
