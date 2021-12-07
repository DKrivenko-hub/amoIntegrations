jQuery(function () {
  document.addEventListener("wpcf7mailsent", function (event) {
    if (event.detail.apiResponse.my_response !== undefined) {
      jQuery.ajax({
        type: "POST",
        url: "/amoIntegrations/ajaxhandler.php",
        data: { form: event.detail.apiResponse.my_response },
        dataType: "json",
        success: function (response) {
          console.log(response);
        },
      });
    }
  });

  jQuery('div[id^="super-form"] form input[name="page_id"]').val(amoIntegr__page_id);
  jQuery('div[id^="super-form"] form input[name="page_id"]').attr('value',amoIntegr__page_id);
  jQuery('div[id^="super-form"] form input[name="page_id"]').attr('data-default-value',amoIntegr__page_id);
  jQuery('div[id^="super-form"] form input[name="page_id"]').prop('value',amoIntegr__page_id);
});
