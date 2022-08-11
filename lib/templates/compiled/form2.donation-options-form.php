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
.elementor-button{font-weight: bold;}
.help-block{background-color: #eee; padding: .5em; border-radius: 5px; color: #666; font-size: .9em;}
.help-block p:last-child{margin-bottom: 0;}
table tr td{border: none; padding: 20px;}
table tr .help-block{background-color: transparent;}
div.help-block{margin-top: .5em;}
div.checkbox label{display: flex; align-items: center; cursor: pointer;}
div.checkbox label input{margin-right: .25em; width: 20px; height: 20px;}
span.label-text{font-size: 1.25em; font-weight: bold;}
</style>

'.(($inary && isset($in['step_one_notice'])) ? $in['step_one_notice'] : null).'
<p class="lead"><strong>Step 1 of 4:</strong> What Items do you have to donate? (<em>Check all that apply</em>)</p>
<form action="" method="post" style="margin-bottom: 3em;">
  <table class="">
'.LR::sec($cx, (($inary && isset($in['checkboxes'])) ? $in['checkboxes'] : null), null, $in, true, function($cx, $in) {$inary=is_array($in);return '    <tr>
      <td>
        <div class="checkbox">
          <label><input type="checkbox" name="donor[options]['.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').'][field_value]" value="'.htmlspecialchars((string)(($inary && isset($in['value'])) ? $in['value'] : null), ENT_QUOTES, 'UTF-8').'"'.htmlspecialchars((string)(($inary && isset($in['checked'])) ? $in['checked'] : null), ENT_QUOTES, 'UTF-8').' /> <span class="label-text">'.htmlspecialchars((string)(($inary && isset($in['name'])) ? $in['name'] : null), ENT_QUOTES, 'UTF-8').'</span></label>
          <div class="help-block">'.(($inary && isset($in['desc'])) ? $in['desc'] : null).'</div>
          <input type="hidden" name="donor[options]['.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').'][pickup]" value="'.htmlspecialchars((string)(($inary && isset($in['pickup'])) ? $in['pickup'] : null), ENT_QUOTES, 'UTF-8').'" />
          <input type="hidden" name="donor[options]['.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').'][skipquestions]" value="'.htmlspecialchars((string)(($inary && isset($in['skip_questions'])) ? $in['skip_questions'] : null), ENT_QUOTES, 'UTF-8').'" />
          <input type="hidden" name="donor[options]['.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').'][term_id]" value="'.htmlspecialchars((string)(($inary && isset($in['term_id'])) ? $in['term_id'] : null), ENT_QUOTES, 'UTF-8').'" />
        </div>
      </td>
    </tr>
';}).'  </table>
  <h3>Brief description of items:</h3>
  <p><strong><em>Required field.</em></strong> In order to proceed to the next page, please provide a brief description of your donation(s):</p>
  <textarea class="form-control" rows="4" name="donor[description]">'.htmlspecialchars((string)(($inary && isset($in['description'])) ? $in['description'] : null), ENT_QUOTES, 'UTF-8').'</textarea>
  <p class="help-block">Example: I have a couch and three boxes of household items from spring cleaning.</p>
  <div class="elementor-align-justify">
    <button type="submit" class="btn btn-primary button btn-lg btn-block elementor-button">Continue to Step 2</button>
  </div>

  <input type="hidden" name="nextpage" value="'.htmlspecialchars((string)(($inary && isset($in['nextpage'])) ? $in['nextpage'] : null), ENT_QUOTES, 'UTF-8').'" />
</form>';
};
?>