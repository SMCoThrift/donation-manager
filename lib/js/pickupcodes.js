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

  
  // MASKING THE INPUT PICKUP CODES
 $('#pickup-code').on('input', function() {
      var val = this.value.replace(/\D/g, '');
      if (val.length > 5) {
        var formattedVal = '';
        for (var i = 0; i < val.length; i += 5) {
          formattedVal += val.slice(i, i + 5) + ',';
        }
        this.value = formattedVal.slice(0, -1);
      }
      else {
        this.value = val;
      }
    });


//check if the user already have the zipcode he intered
    $('#pickup-code').on('input', function() {
      var enteredCodes = $(this).val().split(',');
      var existingCodes = $('.user-pickup-code').find('a.removed-pickupcode').map(function() {
        return $(this).attr('value');
      }).get();

      var matchingCodes = [];
      var nonMatchingCodes = [];

      for (var i = 0; i < enteredCodes.length; i++) {
        var code = enteredCodes[i].trim();
        if (existingCodes.includes(code)) {
          matchingCodes.push(code);
        } else {
          nonMatchingCodes.push(code);
        }
      }

      // Show matching and non-matching codes
      $('.user-pickup-code a.removed-pickupcode').removeClass('highlight');
      $('.user-pickup-code a.removed-pickupcode[value="' + matchingCodes.join('"], .user-pickup-code a.removed-pickupcode[value="') + '"]').addClass('highlight');

      // Update indicator
      var indicator = '';
      if (matchingCodes.length > 0) {
        indicator = 'You already have ' + matchingCodes.length + ' of your zipcodes.';
        if (nonMatchingCodes.length > 0) {
          indicator += ' ' + nonMatchingCodes.length + ' are not found.';
        }
      }
      $('#indicator').text(indicator);
    });

    // Handle removing zipcodes from the list
    $('.user-pickup-code').on('click', 'a.removed-pickupcode', function(e) {
      e.preventDefault();
      var removedCode = $(this).attr('value');
      $(this).parent().remove();

      // Update matching codes array
      var matchingCodes = $('#pickup-code').val().split(',').map(function(code) {
        return code.trim();
      }).filter(function(code) {
        return code !== removedCode;
      });

      // Update input field value
      $('#pickup-code').val(matchingCodes.join(','));

      // Check if matchingCodes array is empty and remove the indicator
      if (matchingCodes.length === 0) {
        $('#indicator').text('');
      } else {
        // Update indicator
        var indicator = 'You already have ' + matchingCodes.length + ' of your zipcodes.';
        $('#indicator').text(indicator);
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


var baseUrl = window.location.protocol + '//' + window.location.host;
const perPage = 100;
const totalRecords = 2387;

function fetchZipcodes(page) {
  var url = `${baseUrl}/wp-json/wp/v2/pickup_code?per_page=${perPage}&page=${page}`;
  return fetch(url)
    .then(function(response) {
      if (response.ok) {
        return response.json();
      } else {
        throw new Error('Error: ' + response.status);
      }
    })
    .then(function(data) {
      return data.map(function(term) {
        return {
          id: term.title,
          title: term.name
        };
      });
    });
}

function initializeTomSelect(options) {
  var tomSelect = new TomSelect('.zipcodes-data', {
    valueField: 'title',
    labelField: 'title',
    field: 'number',
    searchField: 'title',
    options: options,
    create: false,
    maxLength: 5,
    onChange: function(value) {
      // Get the selected values
      var selectedValues = value.split(',');
      
      // Update the input value to display comma-separated values
      this.$input.val(selectedValues.join(','));
    }
  });
}

function fetchAllZipcodes() {
  const totalPages = Math.ceil(totalRecords / perPage);
  const requests = [];

  for (let page = 1; page <= totalPages; page++) {
    requests.push(fetchZipcodes(page));
  }

  Promise.all(requests)
    .then(function(results) {
      const options = results.flat();
      initializeTomSelect(options);
    })
    .catch(function(error) {
      console.log(error);
    });
}

fetchAllZipcodes();




});