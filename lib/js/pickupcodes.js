jQuery(document).ready(function($) {

$('.pickupcode-btn').on('click', function(event) {
  event.preventDefault(); // Prevent default form submission
  var codes = $('#pickup-code').val().split(',').map(function(code) {
    return code.trim();
  });

  if (codes.length > 0) {
    $.ajax({
      type: "POST",
      url: ajax_object.ajax_url,
      data: {
        action: "check_pickup_code",
        codes: codes.join(',')
      },
      success: function(response) {
        console.log(response);
        if (response.startsWith('The following pickup codes are available:')) {
          var availableCodes = response.substring(response.indexOf(':') + 1).trim().split(',');
          var invalidCodes = codes.filter(function(code) {
            return !availableCodes.includes(code.trim());
          });
          $.each(availableCodes, function(index, code) {
            $('#pickup-code-result').append('<li><a href="#" class="add-pickupcode valid" value="' + code.trim() + '">' + code.trim() + ' <i class="fa fa-solid fa-plus"></i></a></li>');
          });
          $.each(invalidCodes, function(index, code) {
            $('#pickup-code-result').append('<li class="invalid">' + code.trim() + '</li>');
          });
          var availablePickupCount = availableCodes.length;
          $('#invalid-pickup-count').html('<p class="result-output"><b>' + availablePickupCount + '</b> of the pickup codes you entered are available. Click "+" next to each to receive donation pickup requests for the zip code:</p>');
        } else {
          var invalidCodes = response.trim().split(', ');
          $.each(invalidCodes, function(index, code) {
            $('#pickup-code-result').append('<li class="invalid">' + code + '</li>');
          });
          var invalidPickupCount = parseInt($('#invalid-pickup-count').text() || 0) + invalidCodes.length;
          $('#invalid-pickup-count').html('<p class="result-output"><b>' + invalidPickupCount + '</b> of the pickup codes you entered are available. Click "+" next to each to receive donation pickup requests for the zip code:</p>');
        }
        $('#pickup-code').val(''); // Clears the input field
      }
    });
  }
});



	//adding the pickup code on the current user plus sign the function is on the user-dashboard.php function add_pickup_code()
    $('#pickup-code-result').on('click', '.add-pickupcode', function(event) {
        event.preventDefault();
        var pickupcode = $(this).attr('value');
        var $currentLi = $(this).closest('li');
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url, //
            data: {
                'action': 'add_pickup_code', // 
                'pickupcode': pickupcode
            },
            success: function(response) {
                console.log(response);
                var $message = $('<div class="message-pickupcodes">Pickup code added.</div>'); 
                $currentLi.append($message); 
                setTimeout(function() {
                    $currentLi.hide(); 
                }, 2000);
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    });

//removing the pickup code on the current user minus sign the function is on the user-dashboard.php function remove_pickup_code()
    $('.removed-pickupcode').click(function(event) {
        event.preventDefault();
        var value = $(this).attr('value');
        var taxonomy = 'pickup_code';
        var postType = 'trans_dept';
        var $currentLi = $(this).closest('li');

        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'remove_pickup_code',
                value: value,
                taxonomy: taxonomy,
                post_type: postType
            },
            success: function(response) {
                // Success message

                var $message = $('<div class="message-pickupcodes">deleted.</div>'); 
                $currentLi.append($message); 
                setTimeout(function() {
                    $currentLi.hide(); 
                }, 2000);
            },
            error: function(error) {
                console.log(error);
            }
        });
    });


});