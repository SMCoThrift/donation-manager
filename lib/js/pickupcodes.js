jQuery(document).ready(function($) {
	//check the pickup code the function is on the user-dashboard.php function is_pickup_code_available_callback
    $('.pickupcode-btn').on('click', function(event) {
        event.preventDefault(); // Prevent default form submission
        var code = $('#pickup-code').val();
        if (code.length == 5) {
            $.ajax({
                type: "POST",
                url: ajax_object.ajax_url,
                data: {
                    action: "check_pickup_code",
                    code: code
                },
                success: function(response) {
                    console.log(response);
                    if (response === 'This pickup code is available.') {
                        $('#pickup-code-result').append('<li><a href="#" class="add-pickupcode valid" value="' + code + '">' + code + ' <i class="fa fa-solid fa-plus"></i></a></li>');
                    } else {
                        $('#pickup-code-result').append('<li class="invalid">' + code + '</li>');
                        var invalidPickupCount = parseInt($('#invalid-pickup-count').text() || 0) + 1;
                        $('#invalid-pickup-count').html('<p class = "result-output"><b>' + invalidPickupCount + '</b> of the pick up codes you entered are available. Click "+" next to each to receive donation pickup requests for the zip code:</p>');
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