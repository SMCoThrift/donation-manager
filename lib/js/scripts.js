jQuery(document).ready(function($){
  console.log('🔔 scripts.js is loaded.');
  $('#additional-details').addClass('hidden');

  $(document).on('submit', 'form', function (e) {
    // Disable submit
    $('button[type="submit"]').attr('disabled', 'disabled');
    $('button[type="submit"]').addClass('disabled');
    $('button[type="submit"]').html('one moment...');
    e.stopPropagation();
  });

  var provide_additional_details = $(`form[name="screening-questions"] input[name="provide_additional_details"]`);
  console.log('🔔 provide_additional_details = ', provide_additional_details.val() );
  let padBoolVal = ( provide_additional_details.val() === '1' ); // get the boolean value
  console.log('🔔 padBoolVal = ', padBoolVal );

  if (true === padBoolVal) {
    var questions = $(`form[name="screening-questions"] input:checked`);

    for (x = 0; x < questions.length; x++) {
      if ('yes' === questions[x].value.toLowerCase())
        $('#additional-details').removeClass('hidden');
    }

    $(`form[name="screening-questions"] input:radio`).change(function () {
      var showAdditionalDetails = false;

      var questions = $(`form[name="screening-questions"] input:checked`);

      for (x = 0; x < questions.length; x++) {
        if ('yes' === questions[x].value.toLowerCase())
          showAdditionalDetails = true;
      }

      if (true === showAdditionalDetails) {
        $('#additional-details').removeClass('hidden');
      } else {
        $('#additional-details').addClass('hidden');
      }
    });
  }

  /* Show Priority Pick Up Option */
  $('#show-priority').on('click', function (e) {
    e.preventDefault();
    $('.priority-alert').slideUp();
    $('.priority-note').slideDown();
  });
  $('.priority-note button.elementor-alert-dismiss').on('click', function (e) {
    e.preventDefault();
    $('.priority-note').slideUp();
    $('.priority-alert').slideDown();
  });
});
