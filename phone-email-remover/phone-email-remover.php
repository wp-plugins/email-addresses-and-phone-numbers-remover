<?php
/*
Plugin Name: Email Addresses and Phone Numbers Remover
Plugin URI: http://www.no-privacy.it/email-addressed-and-phone-numbers-remover-a-wordpress-plugin-to-save-privacy-59.html
Description: Based on <a href="http://paxoo.com/wp-emob">Email Obfuscator</a> by Billy Halsey, this plugin extends his parent's functionality by removing the emails and phone numbers from any comments  
Version: 1.2
License: GPL
Author: Michele Gobbi
Author URI: http://www.dynamick.it
*/

// =========================================================================
// = Hide your email address!                                              =
// =                                                                       =
// = Planned features:                                                     =
// =  - Customizable friendly text output                                  =
// =========================================================================


function dynamick_hexify_mailto($mailto)
{
    $m = preg_replace('/mailto:/', '', $mailto);
    $hexified = '';
    for ($i=0; $i < strlen($m); $i++) { 
        $hexified .= '%' . strtoupper(base_convert(ord($m[$i]), 10, 16));
    }
    return $hexified;
}
// ----------------------------------------------------------------------
// ABOVE IS GOLDEN
// ----------------------------------------------------------------------

function dynamick_readable_mail($address)
{
    $addr_pattern =
        '/([A-Z0-9._%+-]+)@([A-Z0-9.-]+)\.([A-Z]{2,4})/i';
    $addr_readable =
        'xxxxx {at} $2(.)$3';
    $ret=preg_replace($addr_pattern, $addr_readable, $address);

/*
    $addr_pattern =
        '/([0-9]+)/i';
    $addr_readable =
        '00000000000';
    $ret=preg_replace($addr_pattern, $addr_readable, $ret);
*/
    return $ret;
}
// ----------------------------------------------------------------------
// ABOVE IS GOLDEN
// ----------------------------------------------------------------------

function dynamick_obfusc_mail($address)
{
    // Requires: PHP >= 4.2.0
    return str_rot13($address);
}

function dynamick_makelink($mailto, $id, $js = false)
{
    $hexlink  = dynamick_hexify_mailto($mailto);
    $readable = dynamick_readable_mail($mailto);
    $obfusc   = dynamick_obfusc_mail($mailto);
    
    if ($js) {
        $link = "#";
    } else {
        $link = "<a href=\"#\" id=\"no-$obfusc-$id\">$readable</a>";
    }
    return $link;
}

function dynamick_addJScript($address, $id)
{
    $obfusc   = dynamick_obfusc_mail($address);
    $readable = dynamick_readable_mail($address);
    $link     = dynamick_makelink($address, $id, true);
    
    $dynamick_js = <<<EJS
<script type="text/javascript">
    var mailNode = document.getElementById('no-$obfusc-$id');
    var linkNode = document.createElement('a');
    linkNode.setAttribute('href', "$link");
    tNode = document.createTextNode("$readable");
    linkNode.appendChild(tNode);
    linkNode.setAttribute('id', "no-$obfusc-$id");
    mailNode.parentNode.replaceChild(linkNode, mailNode);
</script>
EJS;
    return ;
}

function dynamick_replace($content)
{
    $removed_phone_number = "347XXXXXX";
    $mailto_pattern = '/mailto:[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i';

    preg_match_all($mailto_pattern, $content, $mailtos);
    for ($i=0; $i < count($mailtos); $i++) { 
        $mto[0][$i] = "#";
    }
    $cont = str_replace($mailtos[0], $mto[0], $content);


    $mailto_pattern = '/([+]39)?([0-9]{3})[\/\s-]?([\d]{7})/i';
    preg_match_all($mailto_pattern, $cont, $mailtos);
    for ($i=0; $i < count($mailtos); $i++) { 
        $mto[0][$i] = $removed_phone_number;
    }
    $cont = str_replace($mailtos[0], $mto[0], $cont);

    // ----------------------------------------------------------------------
    // ABOVE IS GOLDEN
    // ----------------------------------------------------------------------
    
    $addr_pattern = '/([A-Z0-9._%+-]+)@([A-Z0-9.-]+)\.([A-Z]{2,4})/i';
    preg_match_all($addr_pattern, $cont, $addresses);
    $the_addrs = $addresses[0];
    for ($a=0; $a < count($the_addrs); $a++) {
            $r = rand(10, 99);  // Avoid collisions in 'id' attributes namespace!
            $obfusc = dynamick_obfusc_mail($the_addrs[$a]);
            $repaddr[$a] = "<span id=\"no-$obfusc-$r\">" . 
                dynamick_readable_mail($the_addrs[$a]) . "</span>";
            $repaddr[$a] .= dynamick_addJScript($the_addrs[$a], $r);
        }
    
    $cc = str_replace($the_addrs, $repaddr, $cont);
    return $cc;
}

add_filter('the_content', 'dynamick_replace');
add_filter('the_excerpt', 'dynamick_replace');
#add_filter('comment_text', 'dynamick_replace');
#add_filter('author_email', 'dynamick_replace');
#add_filter('comment_email', 'dynamick_replace');

?>