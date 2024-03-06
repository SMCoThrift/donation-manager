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
    return '<!doctype html><html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head><title></title><!--[if !mso]><!-- --><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]--><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style type="text/css">#outlook a { padding:0; }
          .ReadMsgBody { width:100%; }
          .ExternalClass { width:100%; }
          .ExternalClass * { line-height:100%; }
          body { margin:0;padding:0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%; }
          table, td { border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt; }
          img { border:0;height:auto;line-height:100%; outline:none;text-decoration:none;-ms-interpolation-mode:bicubic; }
          p { display:block;margin:13px 0; }</style><!--[if !mso]><!--><style type="text/css">@media only screen and (max-width:480px) {
            @-ms-viewport { width:320px; }
            @viewport { width:320px; }
          }</style><!--<![endif]--><!--[if mso]>
        <xml>
        <o:OfficeDocumentSettings>
          <o:AllowPNG/>
          <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
        </xml>
        <![endif]--><!--[if lte mso 11]>
        <style type="text/css">
          .outlook-group-fix { width:100% !important; }
        </style>
        <![endif]--><!--[if !mso]><!--><link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css"><style type="text/css">@import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);</style><!--<![endif]--><style type="text/css">@media only screen and (min-width:480px) {
        .mj-column-per-50 { width:50% !important; max-width: 50%; }
.mj-column-per-100 { width:100% !important; max-width: 100%; }
      }</style><style type="text/css">@media only screen and (max-width:480px) {
      table.full-width-mobile { width: 100% !important; }
      td.full-width-mobile { width: auto !important; }
    }</style></head><body style="background-color:#eeebeb;"><div><div style="background-color:#eeebeb;"><!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]--><div style="Margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"><tbody><tr><td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;vertical-align:top;"><!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:300px;" ><![endif]--><div class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"><tr><td align="left" style="font-size:0px;padding:0px;word-break:break-word;"><div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:24px;font-weight:600;line-height:1;text-align:left;color:#6f6f6f;">Donation Notification<br><span style="font-size: 14px; line-height: 18px; font-weight: normal; color: #000;">From: '.htmlspecialchars((string)(($inary && isset($in['donor_name'])) ? $in['donor_name'] : null), ENT_QUOTES, 'UTF-8').', '.(($inary && isset($in['contact_info'])) ? $in['contact_info'] : null).'<br>To: '.htmlspecialchars((string)(($inary && isset($in['organization_name'])) ? $in['organization_name'] : null), ENT_QUOTES, 'UTF-8').'</span></div></td></tr></table></div><!--[if mso | IE]></td><td class="" style="vertical-align:top;width:300px;" ><![endif]--><div class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"><tr><td align="right" style="font-size:0px;padding:10px 25px;word-break:break-word;"><table align="right" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"><tbody><tr><td style="width:250px;"><img height="auto" src="https://www.pickupmydonation.com/app/plugins/donation-manager/lib/images/pickupmydonation.1200x143.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;" width="250"></td></tr></tbody></table></td></tr></table></div><!--[if mso | IE]></td></tr></table><![endif]--></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]--><div style="background:#fff;background-color:#fff;Margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#fff;background-color:#fff;width:100%;"><tbody><tr><td style="direction:ltr;font-size:0px;padding:20px;padding-bottom:0px;text-align:center;vertical-align:top;"><!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:560px;" ><![endif]--><div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#fff;vertical-align:top;" width="100%">'.((LR::ifvar($cx, (($inary && isset($in['user_uploaded_image'])) ? $in['user_uploaded_image'] : null), false)) ? '<tr><td align="left" style="font-size:0px;padding:10px 0;word-break:break-word;"><div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:600;line-height:1;text-align:left;color:#000000;">DONATION PHOTO(S):</div></td></tr>'.LR::sec($cx, (($inary && isset($in['user_uploaded_image'])) ? $in['user_uploaded_image'] : null), null, $in, true, function($cx, $in) {$inary=is_array($in);return '<tr><td align="center" style="font-size:0px;padding:0px;word-break:break-word;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"><tbody><tr><td style="width:560px;"><a href="'.htmlspecialchars((string)$in, ENT_QUOTES, 'UTF-8').'" target="_blank"><img height="auto" src="'.htmlspecialchars((string)$in, ENT_QUOTES, 'UTF-8').'" style="border:1px solid #eee;display:block;outline:none;text-decoration:none;height:auto;width:100%;" width="560"></a></td></tr></tbody></table></td></tr>';}).'' : '').'</table></div><!--[if mso | IE]></td></tr></table><![endif]--></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]--><div style="background:#fff;background-color:#fff;Margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#fff;background-color:#fff;width:100%;"><tbody><tr><td style="direction:ltr;font-size:0px;padding:20px;text-align:center;vertical-align:top;"><!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:560px;" ><![endif]--><div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#e6e7e9;vertical-align:top;" width="100%">'.((LR::ifvar($cx, (($inary && isset($in['click_to_claim'])) ? $in['click_to_claim'] : null), false)) ? '<tr><td align="center" vertical-align="middle" style="background:#fff;font-size:0px;padding:10px 25px;word-break:break-word;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;"><tr><td align="center" bgcolor="#f68428" role="presentation" style="border:2px solid #fff;border-radius:3px;cursor:auto;padding:10px 25px;" valign="middle"><a href="'.htmlspecialchars((string)(($inary && isset($in['click_to_claim'])) ? $in['click_to_claim'] : null), ENT_QUOTES, 'UTF-8').'" style="background:#f68428;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:24px;font-weight:600;line-height:120%;Margin:0;text-decoration:none;text-transform:none;" target="_blank">View This Donation</a></td></tr></table></td></tr>' : '').'<tr><td align="left" style="font-size:0px;padding:0px;word-break:break-word;"><div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:22px;text-align:left;color:#000000;">'.(($inary && isset($in['orphaned_donation_note'])) ? $in['orphaned_donation_note'] : null).'</div></td></tr>'.((LR::ifvar($cx, (($inary && isset($in['click_to_claim'])) ? $in['click_to_claim'] : null), false)) ? '<tr><td align="center" vertical-align="middle" style="background:#fff;font-size:0px;padding:10px 25px;padding-bottom:40px;word-break:break-word;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;"><tr><td align="center" bgcolor="#f68428" role="presentation" style="border:2px solid #fff;border-radius:3px;cursor:auto;padding:10px 25px;" valign="middle"><a href="'.htmlspecialchars((string)(($inary && isset($in['click_to_claim'])) ? $in['click_to_claim'] : null), ENT_QUOTES, 'UTF-8').'" style="background:#f68428;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:24px;font-weight:600;line-height:120%;Margin:0;text-decoration:none;text-transform:none;" target="_blank">View This Donation</a></td></tr></table></td></tr>' : '').'<tr><td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;"><div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:22px;text-align:left;color:#000000;">'.(($inary && isset($in['donationreceipt'])) ? $in['donationreceipt'] : null).'</div></td></tr></table></div><!--[if mso | IE]></td></tr></table><![endif]--></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]--><div style="Margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"><tbody><tr><td style="direction:ltr;font-size:0px;padding:0;text-align:center;vertical-align:top;"><!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]--><div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"><tr><td align="center" style="font-size:0px;padding:0;word-break:break-word;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"><tbody><tr><td style="width:600px;"><a href="https://smcothrift.com/conference/" target="_blank"><img height="auto" src="https://pickupmydonation.com/app/plugins/donation-manager/lib/images/smco-training-conference-2022.jpg" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;" width="600"></a></td></tr></tbody></table></td></tr></table></div><!--[if mso | IE]></td></tr></table><![endif]--></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]--><div style="background:#fff;background-color:#fff;Margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#fff;background-color:#fff;width:100%;"><tbody><tr><td style="direction:ltr;font-size:0px;padding:20px;text-align:center;vertical-align:top;"><!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:280px;" ><![endif]--><div class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"><tbody><tr><td style="width:230px;"><a href="https://smcothrift.com" target="_blank"><img height="auto" src="https://pickupmydonation.com/app/plugins/donation-manager/lib/images/smcothrift_600x210.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;" width="230"></a></td></tr></tbody></table></td></tr></table></div><!--[if mso | IE]></td><td class="" style="vertical-align:top;width:280px;" ><![endif]--><div class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"><tbody><tr><td style="width:230px;"><a href="https://thrifttrac.com" target="_blank"><img height="auto" src="https://pickupmydonation.com/app/plugins/donation-manager/lib/images/thrifttrac-with-tagline_600x210.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;" width="230"></a></td></tr></tbody></table></td></tr></table></div><!--[if mso | IE]></td></tr></table><![endif]--></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]--><div style="background:#363636;background-color:#363636;Margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#363636;background-color:#363636;width:100%;"><tbody><tr><td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;vertical-align:top;"><!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]--><div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" ><tr><td><![endif]--><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="float:none;display:inline-table;"><tr><td style="padding:4px;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-radius:3px;width:30px;"><tr><td style="font-size:0;height:30px;vertical-align:middle;width:30px;"><a href="https://twitter.com/pickupdonations" target="_blank"><img height="30" src="https://www.pickupmydonation.com/app/plugins/donation-manager/lib/images/twitter.png" style="border-radius:3px;" width="30"></a></td></tr></table></td></tr></table><!--[if mso | IE]></td><td><![endif]--><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="float:none;display:inline-table;"><tr><td style="padding:4px;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-radius:3px;width:30px;"><tr><td style="font-size:0;height:30px;vertical-align:middle;width:30px;"><a href="https://www.facebook.com/PickUpMyDonationcom" target="_blank"><img height="30" src="https://www.pickupmydonation.com/app/plugins/donation-manager/lib/images/facebook.png" style="border-radius:3px;" width="30"></a></td></tr></table></td></tr></table><!--[if mso | IE]></td><td><![endif]--><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="float:none;display:inline-table;"><tr><td style="padding:4px;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-radius:3px;width:30px;"><tr><td style="font-size:0;height:30px;vertical-align:middle;width:30px;"><a href="https://www.instagram.com/pickupdonations/" target="_blank"><img height="30" src="https://www.pickupmydonation.com/app/plugins/donation-manager/lib/images/instagram.png" style="border-radius:3px;" width="30"></a></td></tr></table></td></tr></table><!--[if mso | IE]></td></tr></table><![endif]--></td></tr><tr><td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"><div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:20px;text-align:center;color:#fff;">&copy; 2012 &ndash; 2023 PickUpMyDonation.com. All rights reserved.<br><a href="https://www.pickupmydonation.com/email-preferences?email='.(($inary && isset($in['email'])) ? $in['email'] : null).'" style="color: #f68428; text-decoration: none;">Unsubscribe from these emails.</a></div></td></tr></table></div><!--[if mso | IE]></td></tr></table><![endif]--></td></tr></tbody></table></div><!--[if mso | IE]></td></tr></table><![endif]--></div></div></body></html>';
};
?>