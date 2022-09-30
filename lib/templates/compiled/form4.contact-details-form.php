<?php
use \LightnCandy\SafeString as SafeString;use \LightnCandy\Runtime as LR;return function ($in = null, $options = null) {
    $helpers = array();
    $partials = array();
    $cx = array(
        'flags' => array(
            'jstrue' => false,
            'jsobj' => false,
            'jslen' => false,
            'spvar' => true,
            'prop' => false,
            'method' => false,
            'lambda' => false,
            'mustlok' => false,
            'mustlam' => false,
            'mustsec' => false,
            'echo' => false,
            'partnc' => false,
            'knohlp' => false,
            'debug' => isset($options['debug']) ? $options['debug'] : 1,
        ),
        'constants' => array(),
        'helpers' => isset($options['helpers']) ? array_merge($helpers, $options['helpers']) : $helpers,
        'partials' => isset($options['partials']) ? array_merge($partials, $options['partials']) : $partials,
        'scopes' => array(),
        'sp_vars' => isset($options['data']) ? array_merge(array('root' => $in), $options['data']) : array('root' => $in),
        'blparam' => array(),
        'partialid' => 0,
        'runtime' => '\LightnCandy\Runtime',
    );
    
    $inary=is_array($in);
    return ''.(($inary && isset($in['uploaded_image'])) ? $in['uploaded_image'] : null).'
<!-- START Elementor Form -->
<div class="elementor-form-container elementor-widget-wrap elementor-element-populated" style="margin-bottom: 3em;">
  <div class="elementor-element elementor-element-form elementor-button-align-stretch elementor-widget elementor-widget-form">
    <div class="elementor-widget-container">
      <form class="elementor-form" method="post" name="Form 4 - Contact Details">
        <input type="hidden" name="nextpage" value="'.htmlspecialchars((string)(($inary && isset($in['nextpage'])) ? $in['nextpage'] : null), ENT_QUOTES, 'UTF-8').'" />
        <div class="elementor-form-fields-wrapper elementor-labels-above">

          <div class="elementor-field-type-text elementor-field-group elementor-column elementor-field-group-first_name elementor-col-50 elementor-field-required elementor-mark-required">
            <label for="form-field-first_name" class="elementor-field-label">First Name</label>
            <input size="1" type="text" name="donor[address][name][first]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_name_first'])) ? $in['donor_name_first'] : null), ENT_QUOTES, 'UTF-8').'" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="First Name" required="required" aria-required="true">
          </div>

          <div class="elementor-field-type-text elementor-field-group elementor-column elementor-field-group-last_name elementor-col-50 elementor-field-required elementor-mark-required">
            <label for="form-field-last_name" class="elementor-field-label">Last Name</label>
            <input size="1" type="text" name="donor[address][name][last]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_name_last'])) ? $in['donor_name_last'] : null), ENT_QUOTES, 'UTF-8').'" id="form-field-last_name" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="Last Name" required="required" aria-required="true">
          </div>

          <div class="elementor-field-type-text elementor-field-group elementor-column elementor-field-group-company elementor-col-100">
            <label for="form-field-company" class="elementor-field-label">Company <small style="color: #999;">(optional)</small></label>
            <input size="1" type="text" name="donor[address][company]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_company'])) ? $in['donor_company'] : null), ENT_QUOTES, 'UTF-8').'" id="form-field-company" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="Company">
          </div>

          <div class="elementor-field-type-text elementor-field-group elementor-column elementor-field-group-address elementor-col-100 elementor-field-required elementor-mark-required">
            <label for="form-field-address" class="elementor-field-label">Address</label>
            <input size="1" type="text" name="donor[address][address]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_address'])) ? $in['donor_address'] : null), ENT_QUOTES, 'UTF-8').'" id="form-field-address" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="123 Any Street" required="required" aria-required="true">
          </div>

          <div class="elementor-field-type-text elementor-field-group elementor-column elementor-field-group-city elementor-col-40 elementor-field-required elementor-mark-required">
            <label for="form-field-city" class="elementor-field-label">City</label>
            <input size="1" type="text" name="donor[address][city]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_city'])) ? $in['donor_city'] : null), ENT_QUOTES, 'UTF-8').'" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="City" required="required" aria-required="true">
          </div>

          <div class="elementor-field-type-select elementor-field-group elementor-column elementor-field-group-field_1d8c20d elementor-col-33 elementor-field-required elementor-mark-required">
            <label class="elementor-field-label">State</label>
            <div class="elementor-field elementor-select-wrapper ">
              '.(($inary && isset($in['state'])) ? $in['state'] : null).'
            </div>
          </div>

          <div class="elementor-field-type-text elementor-field-group elementor-column elementor-field-group-field_cf4945d elementor-col-25 elementor-field-required elementor-mark-required">
            <label for="form-field-field_cf4945d" class="elementor-field-label">ZIP/Postal Code</label>
            <input size="1" type="text" name="donor[address][zip]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_zip'])) ? $in['donor_zip'] : null), ENT_QUOTES, 'UTF-8').'" class="elementor-field elementor-size-sm  elementor-field-textual" required="required" aria-required="true">
          </div>

          <div class="elementor-field-type-radio elementor-field-group elementor-column elementor-field-group-different_pickup_address elementor-col-100" style="margin: 1em 0;">
            <label for="form-field-different_pickup_address" class="elementor-field-label">Pick up address is different from above address?</label>
            <div class="elementor-field-subgroup  elementor-subgroup-inline">
              <span class="elementor-field-option">
                <input type="radio" value="Yes"'.htmlspecialchars((string)(($inary && isset($in['checked_yes'])) ? $in['checked_yes'] : null), ENT_QUOTES, 'UTF-8').' name="donor[different_pickup_address]" id="form-field-different_pickup_address-0"> <label for="form-field-different_pickup_address-0">Yes</label>
              </span>
              <span class="elementor-field-option">
                <input type="radio" value="No"'.htmlspecialchars((string)(($inary && isset($in['checked_no'])) ? $in['checked_no'] : null), ENT_QUOTES, 'UTF-8').' name="donor[different_pickup_address]" id="form-field-different_pickup_address-1"> <label for="form-field-different_pickup_address-1">No</label>
              </span>
            </div>
          </div>

          <!-- START Different Pick Up Address -->


            <div class="elementor-field-type-text elementor-field-group different-pickup-address elementor-column elementor-field-group-address elementor-col-100 elementor-field-required elementor-mark-required">
              <label for="form-field-address" class="elementor-field-label">Pickup Address</label>
              <input size="1" type="text" name="donor[pickup_address][address]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_pickup_address'])) ? $in['donor_pickup_address'] : null), ENT_QUOTES, 'UTF-8').'" id="donor-pickup-address-address" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="123 Any Street">
            </div>

            <div class="elementor-field-type-text elementor-field-group different-pickup-address elementor-column elementor-field-group-city elementor-col-40 elementor-field-required elementor-mark-required" style="margin-bottom: 2em;">
              <label for="form-field-city" class="elementor-field-label">Pickup City</label>
              <input size="1" type="text" name="donor[pickup_address][city]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_pickup_city'])) ? $in['donor_pickup_city'] : null), ENT_QUOTES, 'UTF-8').'" id="donor-pickup-address-city" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="City">
            </div>

            <div class="elementor-field-type-select elementor-field-group different-pickup-address elementor-column elementor-field-group-field_1d8c20d elementor-col-33 elementor-field-required elementor-mark-required" style="margin-bottom: 2em;">
              <label class="elementor-field-label">Pickup State</label>
              <div class="elementor-field elementor-select-wrapper ">
                '.(($inary && isset($in['pickup_state'])) ? $in['pickup_state'] : null).'
              </div>
            </div>

            <div class="elementor-field-type-text elementor-field-group different-pickup-address elementor-column elementor-field-group-field_cf4945d elementor-col-25 elementor-field-required elementor-mark-required" style="margin-bottom: 2em;">
              <label for="form-field-field_cf4945d" class="elementor-field-label">ZIP/Postal Code</label>
              <input size="1" type="text" name="donor[pickup_address][zip]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_pickup_zip'])) ? $in['donor_pickup_zip'] : null), ENT_QUOTES, 'UTF-8').'" donor-pickup-address-zip class="elementor-field elementor-size-sm  elementor-field-textual">
            </div>


          <!-- END Different Pick Up Address -->

            <div class="elementor-field-type-email elementor-field-group elementor-column elementor-field-group-email elementor-col-50 elementor-field-required elementor-mark-required">
              <label for="form-field-email" class="elementor-field-label">Email</label>
              <input size="1" type="email" name="donor[email]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_email'])) ? $in['donor_email'] : null), ENT_QUOTES, 'UTF-8').'" id="form-field-email" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="Email" required="required" aria-required="true">
            </div>

            <div class="elementor-field-type-tel elementor-field-group elementor-column elementor-field-group-phone elementor-col-50 elementor-field-required elementor-mark-required">
              <label for="form-field-phone" class="elementor-field-label">Phone</label>
              <input size="1" type="tel" name="donor[phone]" value="'.htmlspecialchars((string)(($inary && isset($in['donor_phone'])) ? $in['donor_phone'] : null), ENT_QUOTES, 'UTF-8').'" id="donor_phone" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="(___) ___-____" required="required" aria-required="true" title="Only numbers and phone characters (#, -, *, etc) are accepted.">
            </div>

            <div class="elementor-field-type-radio elementor-field-group elementor-column elementor-field-group-field_03140a8 elementor-col-100">
              <label for="form-field-field_03140a8" class="elementor-field-label">Preferred method of contact:</label>
              <div class="elementor-field-subgroup  elementor-subgroup-inline">
                <span class="elementor-field-option">
                  <input type="radio" value="Email" id="form-field-field_03140a8-0" name="donor[preferred_contact_method]"'.htmlspecialchars((string)(($inary && isset($in['checked_email'])) ? $in['checked_email'] : null), ENT_QUOTES, 'UTF-8').'>
                  <label for="form-field-field_03140a8-0">Email</label>
                </span>
                <span class="elementor-field-option">
                  <input type="radio" value="Phone" id="form-field-field_03140a8-1" name="donor[preferred_contact_method]"'.htmlspecialchars((string)(($inary && isset($in['checked_phone'])) ? $in['checked_phone'] : null), ENT_QUOTES, 'UTF-8').'>
                  <label for="form-field-field_03140a8-1">Phone</label>
                </span>
              </div>
            </div>
            <div class="elementor-field-type-select elementor-field-group elementor-column elementor-field-group-field_4393692 elementor-col-100">
              <label class="elementor-field-label">What led you to donate today?</label>
                <div class="elementor-field elementor-select-wrapper ">
                  '.(($inary && isset($in['reason_option'])) ? $in['reason_option'] : null).'
                </div>
            </div>

            <div class="elementor-field-group elementor-column elementor-field-type-submit elementor-col-100 e-form__buttons">
              <button type="submit" class="elementor-button elementor-size-sm">
              <span>
                <span class=" elementor-button-icon">
                </span>
                <span class="elementor-button-text">Continue to Final Step</span>
              </span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
<!-- END Elementor Form -->';
};
?>