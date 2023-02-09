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
  .elementor-widget-form #form-field-pickupcode {border-radius: 3px 0 0 3px;}
  .elementor-widget-form #button-submit {border-radius: 0 3px 3px 0;}
</style>
<div class="elementor-element elementor-element-7c0a83f elementor-button-align-stretch elementor-widget elementor-widget-form">
  <div class="elementor-widget-container">
    <form class="elementor-form" method="post" name="City Pages">
      <input type="hidden" name="nextpage" value="'.htmlspecialchars((string)(($inary && isset($in['nextpage'])) ? $in['nextpage'] : null), ENT_QUOTES, 'UTF-8').'">
      <div class="elementor-form-fields-wrapper elementor-labels-above">
        <div class="elementor-field-type-text elementor-field-group elementor-column elementor-field-group-name elementor-col-60 elementor-field-required">
          <input size="1" type="text" name="pickupcode" id="form-field-pickupcode" class="elementor-field elementor-size-sm  elementor-field-textual" placeholder="Zip/Donation Code" required="required" aria-required="true">
        </div>
        <div class="elementor-field-group elementor-column elementor-field-type-submit elementor-col-40 e-form__buttons">
          <button type="submit" class="elementor-button elementor-size-sm" id="button-submit">
          <span>
            <span class=" elementor-button-icon">
            </span>
            <span class="elementor-button-text">Submit</span>
          </span>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>';
};
?>