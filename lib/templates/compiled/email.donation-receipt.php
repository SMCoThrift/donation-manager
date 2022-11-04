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
    return '<strong>DONATION ID:</strong> '.htmlspecialchars((string)(($inary && isset($in['id'])) ? $in['id'] : null), ENT_QUOTES, 'UTF-8').'<br><br>

<strong>DONOR INFORMATION:</strong><br>
'.(($inary && isset($in['donor_info'])) ? $in['donor_info'] : null).'<br><br>

<strong>PICK UP ADDRESS:</strong><br>
<a href="http://maps.google.com/?q='.htmlspecialchars((string)(($inary && isset($in['pickupaddress_query'])) ? $in['pickupaddress_query'] : null), ENT_QUOTES, 'UTF-8').'" target="_blank">'.(($inary && isset($in['pickupaddress'])) ? $in['pickupaddress'] : null).'</a><br><br>

<strong>PREFERRED CONTACT METHOD:</strong> '.(($inary && isset($in['preferred_contact_method'])) ? $in['preferred_contact_method'] : null).'<br><br>

'.((LR::ifvar($cx, (($inary && isset($in['pickupdates'])) ? $in['pickupdates'] : null), false)) ? '<strong>PREFERRED PICK UP DATES:</strong>
<ul>
'.LR::sec($cx, (($inary && isset($in['pickupdates'])) ? $in['pickupdates'] : null), null, $in, true, function($cx, $in) {$inary=is_array($in);return '  <li>'.htmlspecialchars((string)(($inary && isset($in['date'])) ? $in['date'] : null), ENT_QUOTES, 'UTF-8').' ('.htmlspecialchars((string)(($inary && isset($in['time'])) ? $in['time'] : null), ENT_QUOTES, 'UTF-8').')</li>
';}).'</ul>
' : '').'
<strong>ITEMS:</strong> '.htmlspecialchars((string)(($inary && isset($in['items'])) ? $in['items'] : null), ENT_QUOTES, 'UTF-8').'<br><br>

<strong>CUSTOMER DESCRIPTION:</strong><br>
'.htmlspecialchars((string)(($inary && isset($in['description'])) ? $in['description'] : null), ENT_QUOTES, 'UTF-8').'<br><br>

<strong>SCREENING QUESTIONS:</strong><br>
'.(($inary && isset($in['screening_questions'])) ? $in['screening_questions'] : null).'<br><br>

<strong>PICK UP LOCATION:</strong> '.htmlspecialchars((string)(($inary && isset($in['pickuplocation'])) ? $in['pickuplocation'] : null), ENT_QUOTES, 'UTF-8').'<br><br>

<strong>PICK UP CODE:</strong> '.htmlspecialchars((string)(($inary && isset($in['pickup_code'])) ? $in['pickup_code'] : null), ENT_QUOTES, 'UTF-8').'<br><br>

<strong>PREFERRED DONOR CODE:</strong> '.htmlspecialchars((string)(($inary && isset($in['preferred_code'])) ? $in['preferred_code'] : null), ENT_QUOTES, 'UTF-8').'<br><br>

<strong>REASON FOR DONATING:</strong> '.htmlspecialchars((string)(($inary && isset($in['reason'])) ? $in['reason'] : null), ENT_QUOTES, 'UTF-8').'';
};
?>