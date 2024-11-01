jQuery(function ($) {
  if (
    typeof wc_country_select_params === "undefined" ||
    typeof woot_locations_params === "undefined"
  ) {
    return false;
  }

  // Globals
  var current_county = "";
  var current_city = "";

  // Open locations map
  window.wootOpenLocationsMap = function () {
    current_county = $("body")
      .find("#billing_state, #shipping_state, #calc_shipping_state")
      .val();

    current_city = $("body")
      .find("#billing_city, #shipping_city, #calc_shipping_city")
      .val();

    $("body").css("overflow", "hidden");

    var params = {};

    // Filter couriers
    if (woot_locations_params.couriers && woot_locations_params.couriers.length)
      params["courier_id"] = woot_locations_params.couriers.join(",");

    // Filter by county_code
    if (current_county) params["county_code"] = current_county;

    // Filter by city_name
    if (current_city) params["city_name"] = current_city;

    var queryString = new URLSearchParams(params).toString();
    let iframeUrl = "https://pro.woot.ro/locations.html";
    if (queryString) iframeUrl += "?" + queryString;

    let html =
      '<iframe src="' +
      iframeUrl +
      '" frameborder="0" width="100%" height="100%"></iframe>';

    $("#wt-locations-modal .wt-modal-body").html(html);
    $("#wt-locations-modal").addClass("wt-modal-open");
  };

  // Close locations map
  window.wootCloseLocationsMap = function () {
    $("#wt-locations-modal").removeClass("wt-modal-open");
    $("body").css("overflow", "auto");
  };

  window.handleMapMessage = function (event) {
    if (event.data.location) {
      var location = event.data.location;
      $("#location_id").val(location.id);
      $("#location_name").val(location.name);
      $("#location_address").val(location.address);

      var details = '<div class="wt-location-name">' + location.name + "</div>";

      details +=
        '<div class="wt-location-address">' +
        location.address +
        ", " +
        location.city_name +
        ", " +
        location.county_name +
        "</div>";

      $("#wt-location-details").html(details);
      $("#wt-location-details").show();

      wootCloseLocationsMap();
    }
  };

  window.addEventListener("message", handleMapMessage);
});
