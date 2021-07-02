$(document).ready(function() {
  let combinations = false;
  let set_combinations = false;
  if (configuration_form.MOODLECON_SET_COMBINATION) {
    combinations = true;
    set_combinations = (configuration_form.MOODLECON_SET_COMBINATION.value == 1);
  }
  const product_name = $('#MOODLECON_PRODUCT_NAME');
  product_name.prop('disabled', 'disabled');

  const course_name = $('#MOODLECON_COURSE_NAME');
  course_name.prop('disabled', 'disabled');

  if (combinations) {
    const combinations = $('#MOODLECON_PRODUCT_COMBINATION');
    combinations.removeClass('fixed-width-xl');
    combinations.prop('disabled', set_combinations ? false : 'disabled');

    const list = document.getElementById('MOODLECON_PRODUCT_COMBINATION');
    $('#MOODLECON_SET_COMBINATION_on').prop('disabled', (list.length > 1) ? false: 'disabled');
    $('#MOODLECON_SET_COMBINATION_off').prop('disabled', (list.length > 1) ? false: 'disabled');
  }

  const roles = $('#MOODLECON_ROLE_ID');
  roles.removeClass('fixed-width-xl');

  $(document).on('click', 'label', function (event) {
    const label_for = event.target.getAttribute('for')
    console.log();
    if (label_for == 'MOODLECON_SET_COMBINATION_on') {
      if ($('#MOODLECON_SET_COMBINATION_on').prop('disabled') == false) {
        $('#MOODLECON_PRODUCT_COMBINATION').prop('disabled', false);
      }
    } else if (label_for == 'MOODLECON_SET_COMBINATION_off') {
      if ($('#MOODLECON_SET_COMBINATION_off').prop('disabled') == false) {
        $('#MOODLECON_PRODUCT_COMBINATION').prop('disabled', 'disabled');
        $('#MOODLECON_PRODUCT_COMBINATION').val('0');
      }
    }
  });

  $(document).on('keyup', '#MOODLECON_ID_PRODUCT', function (event) {
    const numbers = /^[0-9]+$/;
    if (this.value.match(numbers)) {
      $.ajax({
        url : ajax_path,
        type : 'POST',
        cache : false,
        data : {
          ajax : true,
          action : 'getProductInfo',
          id_product : this.value,
          ajax_token : ajax_token
        },
        success : function (result) {
          if (result && result.length > 0) {
            let response = JSON.parse(result);
            moodlecon_setProductName(response.name);
            moodlecon_setCombinations(response.combinations);
          }
        }
      });
    } else {
      moodlecon_setProductName(undefined);
      moodlecon_setCombinations(undefined);
    }
  });

  $(document).on('keyup', '#MOODLECON_COURSE_ID', function (event) {
    const numbers = /^[0-9]+$/;
    if (this.value.match(numbers)) {
      $.ajax({
        url : ajax_path,
        type : 'POST',
        cache : false,
        data : {
          ajax : true,
          action : 'getCourseName',
          course_id : this.value,
          ajax_token : ajax_token
        },
        success : function (result) {
          if (result && result.length > 0) {
            let response = JSON.parse(result);
            moodlecon_setCourseName(response.name);
          }
        }
      });
    } else {
      moodlecon_setCourseName(undefined);
    }
  });

});

function moodlecon_setProductName(name) {
  $('#MOODLECON_PRODUCT_NAME').val(name ? name : '');
}

function moodlecon_setCourseName(name) {
  $('#MOODLECON_COURSE_NAME').val(name ? name : '');
}

function moodlecon_setCombinations(combinations) {
  let list = document.getElementById('MOODLECON_PRODUCT_COMBINATION');
  while (list.options.length > 1) {
    list.remove(1);
  }
  if (combinations) {
    for (i = 0; i < combinations.length; i++) {
      let newOption = new Option(combinations[i]['txt'],
                                 combinations[i]['id']);
      list.add(newOption, undefined);
    }
    $('#MOODLECON_SET_COMBINATION_on').prop('disabled', false);
    $('#MOODLECON_SET_COMBINATION_off').prop('disabled', false);
    $('#MOODLECON_PRODUCT_COMBINATION').prop('disabled', (configuration_form.MOODLECON_SET_COMBINATION.value == 1) ? false : 'disabled');
  } else {
    configuration_form.MOODLECON_SET_COMBINATION.value = 0
    $('#MOODLECON_SET_COMBINATION_on').prop('disabled', 'disabled');
    $('#MOODLECON_SET_COMBINATION_off').prop('disabled', 'disabled');
    $('#MOODLECON_PRODUCT_COMBINATION').prop('disabled', 'disabled');
  }
  list.value = 0;
}
