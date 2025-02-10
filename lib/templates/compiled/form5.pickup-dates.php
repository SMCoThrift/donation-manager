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
    return '<style>
.row-pickup-dates{margin: 1em 0 0 0;}
label.pickup-date{font-size: 16px; font-weight: bold;}
.date-column{margin: 0 5px;}
.date-column:first-child{margin-left: 0;}
.date-column:last-child{margin-right: 0;}
.date-column .available-times{margin-top: .25em;}
label.pickup-date{font-size: 15px;}
@media(min-width: 768px){
  .elementor-column.elementor-col-33.date-column{width: 32%;}
}
</style>
<form action="" method="post">
  <p class="lead">Please select three <em>POTENTIAL</em> pick up dates.</p>
  '.(($inary && isset($in['date_note'])) ? $in['date_note'] : null).'
  <div class="row elementor-form-fields-wrapper elementor-labels-above row-pickup-dates">
'.LR::sec($cx, (($inary && isset($in['pickupdates'])) ? $in['pickupdates'] : null), null, $in, true, function($cx, $in) {$inary=is_array($in);return '    <div class="elementor-column elementor-col-33 date-column" style="display: block;">
      <label class="pickup-date" for="donor[pickupdate'.htmlspecialchars((string)(isset($cx['sp_vars']['index']) ? $cx['sp_vars']['index'] : null), ENT_QUOTES, 'UTF-8').']">Preferred Pick Up Date '.htmlspecialchars((string)(isset($cx['sp_vars']['index']) ? $cx['sp_vars']['index'] : null), ENT_QUOTES, 'UTF-8').'<span class="required">*</span>:</label><br />
      <input type="text" name="donor[pickupdate'.htmlspecialchars((string)(isset($cx['sp_vars']['index']) ? $cx['sp_vars']['index'] : null), ENT_QUOTES, 'UTF-8').']" id="pickupdate'.htmlspecialchars((string)(isset($cx['sp_vars']['index']) ? $cx['sp_vars']['index'] : null), ENT_QUOTES, 'UTF-8').'" class="date" gldp-id="gldatepicker'.htmlspecialchars((string)(isset($cx['sp_vars']['index']) ? $cx['sp_vars']['index'] : null), ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars((string)(($inary && isset($in['value'])) ? $in['value'] : null), ENT_QUOTES, 'UTF-8').'" />
      <div style="position: relative;">
        <div gldp-el="gldatepicker'.htmlspecialchars((string)(isset($cx['sp_vars']['index']) ? $cx['sp_vars']['index'] : null), ENT_QUOTES, 'UTF-8').'" style="width: 400px; height: 300px;"></div>
      </div>
      <div class="available-times">
'.LR::sec($cx, (($inary && isset($in['times'])) ? $in['times'] : null), null, $in, true, function($cx, $in) {$inary=is_array($in);return '        <div class="radio">
          <label>
            <input type="radio" name="donor[pickuptime'.htmlspecialchars((string)((isset($cx['sp_vars']['_parent']) && isset($cx['sp_vars']['_parent']['index'])) ? $cx['sp_vars']['_parent']['index'] : null), ENT_QUOTES, 'UTF-8').']" id="pickuptimes'.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars((string)(($inary && isset($in['value'])) ? $in['value'] : null), ENT_QUOTES, 'UTF-8').'"'.((LR::ifvar($cx, (($inary && isset($in['checked'])) ? $in['checked'] : null), false)) ? ' checked="checked"' : '').'>
            '.htmlspecialchars((string)(($inary && isset($in['value'])) ? $in['value'] : null), ENT_QUOTES, 'UTF-8').'
          </label>
        </div>
';}).'    </div><!-- .available-times -->
    </div>
';}).'  </div>
  <br />
  '.(($inary && isset($in['priority_pickup_option'])) ? $in['priority_pickup_option'] : null).'
  <div class="row">
    <div class="col-md-12">
      <p><strong>Location of items:</strong></p>
'.LR::sec($cx, (($inary && isset($in['pickuplocations'])) ? $in['pickuplocations'] : null), null, $in, true, function($cx, $in) {$inary=is_array($in);return '      <div class="radio">
        <label>
          <input type="radio" name="donor[pickuplocation]" id="pickuplocation'.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars((string)(($inary && isset($in['location_attr_esc'])) ? $in['location_attr_esc'] : null), ENT_QUOTES, 'UTF-8').'"'.htmlspecialchars((string)(($inary && isset($in['checked'])) ? $in['checked'] : null), ENT_QUOTES, 'UTF-8').'>
          '.htmlspecialchars((string)(($inary && isset($in['location'])) ? $in['location'] : null), ENT_QUOTES, 'UTF-8').'
        </label>
      </div>
';}).'    </div>
  </div>
  <br/>
  <div class="row">
    <div class="col-md-12">'.(($inary && isset($in['sms_consent_note'])) ? $in['sms_consent_note'] : null).'</div>
  </div>
  <br/>

'.((LR::ifvar($cx, (($inary && isset($in['orphaned_donation'])) ? $in['orphaned_donation'] : null), false)) ? '  <div class="row">
    <div class="col-md-12">
      <div class="elementor-alert elementor-widget elementor-alert-warning">
        <span class="elementor-alert-description">
          <h2 style="font-size: 24px; margin-bottom: 4px;"><strong style="font-size: 16px; text-transform: uppercase; display: block; margin-bottom: 6px;">Important note regarding your donation:</strong>Free and Fee-Based Providers, Your choice!</h2>
          <p>We do our best to connect you with a local organization that will pick up your items for free. Unfortunately, we are unable to guarantee a free pick up in your market. If a free pick up provider can\'t perform a free pick up, a fee-based organization may be able to take your item to a nonprofit in your area.</p>
          <p><strong>Would you like for us to send your request to a fee-based pick up service for a competitive quote?</strong></p>
          <div class="radio">
            <label for="fee_based_true">
              <input type="radio" name="donor[fee_based]" id="fee_based_true" value="1"'.htmlspecialchars((string)(($inary && isset($in['checked_fee_based_true'])) ? $in['checked_fee_based_true'] : null), ENT_QUOTES, 'UTF-8').'> Yes, please send this request to free <strong><em>and fee-based<sup>*</sup></em></strong> pick up services.
              <div style="background-color: #d9edf7; padding: 10px; border-radius: 3px; color: #31708f; margin: 10px 0 20px;"><sup style="font-weight: bold;">*</sup>Prices start as low as $100 for a single item.</div>
            </label>
            <label for="fee_based_false">
              <input type="radio" name="donor[fee_based]" id="fee_based_false" value="0"'.htmlspecialchars((string)(($inary && isset($in['checked_fee_based_false'])) ? $in['checked_fee_based_false'] : null), ENT_QUOTES, 'UTF-8').'> No, only send this request to free pick up services.
            </label>
          </div>
        </span>
      </div>
    </div>
  </div>
  <br />
' : '').'
  <div class="row">
    <div class="col-md-12 elementor-align-justify"><p><button type="submit" class="btn btn-block btn-primary" style="width: 100%;">Finish and Submit</button></p></div>
  </div>
  <input type="hidden" name="nextpage" value="'.htmlspecialchars((string)(($inary && isset($in['nextpage'])) ? $in['nextpage'] : null), ENT_QUOTES, 'UTF-8').'" />
</form>';
};
?>