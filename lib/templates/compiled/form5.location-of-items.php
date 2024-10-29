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
    return '<form action="" method="post">
  <p class="lead">Thank you for your donation to <em>'.(($inary && isset($in['organization'])) ? $in['organization'] : null).'</em>. Once you hit <em>Finish and Submit</em>, your donation request will be emailed to us and we will contact you to schedule a pick up time that is convenient for you as well as <em>'.(($inary && isset($in['organization'])) ? $in['organization'] : null).'</em>.</p>
  <br />

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

  <br />
  <div class="row">
    <div class="col-md-12 elementor-align-justify"><p><button type="submit" class="btn btn-block btn-primary" style="width: 100%;">Finish and Submit</button></p></div>
  </div>
  <input type="hidden" name="nextpage" value="'.htmlspecialchars((string)(($inary && isset($in['nextpage'])) ? $in['nextpage'] : null), ENT_QUOTES, 'UTF-8').'" />
  <input type="hidden" name="skip_pickup_dates" value="true" />
</form>';
};
?>