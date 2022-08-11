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
    return '<div class="select-your-organization">
  <style type="text/css">
  .organization{border-bottom: 1px solid #eee; padding-bottom: 2em; margin-bottom: 2em;}
  .organization:last-child{border-bottom: none;}
  .organization .elementor-button{font-weight: bold;}
  .organization h3{margin-top: 0; font-weight: bold;}
  .organization .description{margin-top: 1em;}
  </style>
'.LR::sec($cx, (($inary && isset($in['rows'])) ? $in['rows'] : null), null, $in, true, function($cx, $in) {$inary=is_array($in);return '  <div class="row organization'.htmlspecialchars((string)(($inary && isset($in['css_classes'])) ? $in['css_classes'] : null), ENT_QUOTES, 'UTF-8').'">
      <h3>'.htmlspecialchars((string)(($inary && isset($in['name'])) ? $in['name'] : null), ENT_QUOTES, 'UTF-8').'</h3>
'.((LR::ifvar($cx, (($inary && isset($in['pause_pickups'])) ? $in['pause_pickups'] : null), false)) ? '        <div class="description">'.(($inary && isset($in['desc'])) ? $in['desc'] : null).'</div>
' : '      <div class="elementor-align-justify">
        <a class="btn btn-primary button btn-lg btn-block elementor-button" href="'.htmlspecialchars((string)(($inary && isset($in['link'])) ? $in['link'] : null), ENT_QUOTES, 'UTF-8').'">
          <span class="elementor-button-content-wrapper">
            <span class="elementor-button-icon elementor-align-icon-right"><i aria-hidden="true" class="fas fa-arrow-alt-circle-right"></i></span>
            <span class="elementor-button-text">'.htmlspecialchars((string)(($inary && isset($in['button_text'])) ? $in['button_text'] : null), ENT_QUOTES, 'UTF-8').'</span>
          </span>
        </a>
      </div>
      <div class="description">'.(($inary && isset($in['desc'])) ? $in['desc'] : null).'</div>
').''.((LR::ifvar($cx, (($inary && isset($in['edit_url'])) ? $in['edit_url'] : null), false)) ? '      <div class="edit-this"><a href="'.htmlspecialchars((string)(($inary && isset($in['edit_url'])) ? $in['edit_url'] : null), ENT_QUOTES, 'UTF-8').'" target="_blank">Edit</a></div>
' : '').'  </div>
';}).'</div>';
};
?>