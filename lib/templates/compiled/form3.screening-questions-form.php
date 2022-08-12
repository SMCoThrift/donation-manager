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

<form action="" method="post" name="screening-questions" style="margin-bottom: 3em;">
  <table class="table table-striped"><colgroup><col style="width: 70%;" /><col style="width: 15%" /><col style="width: 15%" /></colgroup>

'.LR::sec($cx, (($inary && isset($in['questions'])) ? $in['questions'] : null), null, $in, true, function($cx, $in) {$inary=is_array($in);return '  <tr>
    <td>'.htmlspecialchars((string)(($inary && isset($in['question'])) ? $in['question'] : null), ENT_QUOTES, 'UTF-8').'<input type="hidden" name="donor[questions]['.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').']" value="'.htmlspecialchars((string)(($inary && isset($in['question_esc_attr'])) ? $in['question_esc_attr'] : null), ENT_QUOTES, 'UTF-8').'"><input type="hidden" name="donor[question][ids][]" value="'.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').'" /></td>
    <td><label ><input type="radio" name="donor[answers]['.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').']" value="Yes"'.htmlspecialchars((string)(($inary && isset($in['checked_yes'])) ? $in['checked_yes'] : null), ENT_QUOTES, 'UTF-8').' /> Yes</label></td>
    <td><label ><input type="radio" name="donor[answers]['.htmlspecialchars((string)(($inary && isset($in['key'])) ? $in['key'] : null), ENT_QUOTES, 'UTF-8').']" value="No"'.htmlspecialchars((string)(($inary && isset($in['checked_no'])) ? $in['checked_no'] : null), ENT_QUOTES, 'UTF-8').' /> No</label></td>
  </tr>
';}).'  </table>
  <div id="additional-details">
    <label>Please provide additional detail about your items for which you answered "Yes" above:</label>
    <textarea class="form-control" rows="4" name="donor[additional_details]">'.htmlspecialchars((string)(($inary && isset($in['additional_details'])) ? $in['additional_details'] : null), ENT_QUOTES, 'UTF-8').'</textarea>
    <span class="help-block">Please be considerate of the costs associated with large item pick ups to our organization. We will gladly and thankully pick up items that will help us further the mission of our organization. However we must respectfully deny items that create an additional disposal liability.</span>
  </div>
'.((LR::ifvar($cx, (($inary && isset($in['file_upload_input'])) ? $in['file_upload_input'] : null), false)) ? '  <div id="donation-photo" style="margin-bottom: 2em;">
    <h3>Upload one or more photos of your donation:</h3>
    '.(($inary && isset($in['file_upload_input'])) ? $in['file_upload_input'] : null).'
    <div class="progress_bar" style=""></div>
    <div class="preview" style="display: none;"></div>
    <input type="hidden" name="image_public_id" id="image_public_id" value="" />
  </div>
' : '').'
  <div class="elementor-align-justify">
    <button type="submit" class="btn btn-primary button btn-lg btn-block elementor-button">Continue to Step 3</button>
  </div>

  <input type="hidden" name="nextpage" value="'.htmlspecialchars((string)(($inary && isset($in['nextpage'])) ? $in['nextpage'] : null), ENT_QUOTES, 'UTF-8').'" />
  <input type="hidden" name="provide_additional_details" value="'.htmlspecialchars((string)(($inary && isset($in['provide_additional_details'])) ? $in['provide_additional_details'] : null), ENT_QUOTES, 'UTF-8').'" />
</form>';
};
?>