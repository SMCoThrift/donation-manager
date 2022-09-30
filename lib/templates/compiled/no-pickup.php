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
    return '<p class="text-center lead">'.htmlspecialchars((string)(($inary && isset($in['name'])) ? $in['name'] : null), ENT_QUOTES, 'UTF-8').' (<a href="mailto:'.htmlspecialchars((string)(($inary && isset($in['email'])) ? $in['email'] : null), ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars((string)(($inary && isset($in['email'])) ? $in['email'] : null), ENT_QUOTES, 'UTF-8').'</a>)<br />'.htmlspecialchars((string)(($inary && isset($in['organization'])) ? $in['organization'] : null), ENT_QUOTES, 'UTF-8').', '.htmlspecialchars((string)(($inary && isset($in['title'])) ? $in['title'] : null), ENT_QUOTES, 'UTF-8').'<br />'.htmlspecialchars((string)(($inary && isset($in['phone'])) ? $in['phone'] : null), ENT_QUOTES, 'UTF-8').'</p>
'.((LR::ifvar($cx, (($inary && isset($in['stores'])) ? $in['stores'] : null), false)) ? ''.LR::sec($cx, (($inary && isset($in['stores'])) ? $in['stores'] : null), null, $in, true, function($cx, $in) {$inary=is_array($in);return '  <p class="text-center" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccc;"><strong>'.htmlspecialchars((string)(($inary && isset($in['name'])) ? $in['name'] : null), ENT_QUOTES, 'UTF-8').'</strong><br />'.htmlspecialchars((string)(($inary && isset($in['address'])) ? $in['address'] : null), ENT_QUOTES, 'UTF-8').'<br />'.htmlspecialchars((string)(($inary && isset($in['city'])) ? $in['city'] : null), ENT_QUOTES, 'UTF-8').', '.htmlspecialchars((string)(($inary && isset($in['state'])) ? $in['state'] : null), ENT_QUOTES, 'UTF-8').' '.htmlspecialchars((string)(($inary && isset($in['zip_code'])) ? $in['zip_code'] : null), ENT_QUOTES, 'UTF-8').'<br />'.htmlspecialchars((string)(($inary && isset($in['phone'])) ? $in['phone'] : null), ENT_QUOTES, 'UTF-8').'</p>
';}).'' : '').'';
};
?>