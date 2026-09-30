<?php
/**
 * Iubenda Cookie Solution integration
 *
 * @package Ficus
 */

add_action('wp_head', function () { ?>
<script type="text/javascript">
var _iub = _iub || [];
_iub.csConfiguration = {"askConsentAtCookiePolicyUpdate":true,"countryDetection":true,"enableFadp":true,"enableLgpd":true,"enableUspr":true,"floatingPreferencesButtonDisplay":"bottom-right","lgpdAppliesGlobally":false,"perPurposeConsent":true,"siteId":1381751,"storage":{"useSiteId":true},"whitelabel":true,"cookiePolicyId":32935352,"banner":{"acceptButtonCaptionColor":"#FFFFFF","acceptButtonColor":"#038037","acceptButtonDisplay":true,"backgroundColor":"#FFFFFF","closeButtonDisplay":false,"continueWithoutAcceptingButtonCaptionColor":"#4D4D4D","continueWithoutAcceptingButtonColor":"#DADADA","customizeButtonCaptionColor":"#4D4D4D","customizeButtonColor":"#DADADA","customizeButtonDisplay":true,"explicitWithdrawal":true,"listPurposes":true,"ownerName":"shop.fomet.it","position":"float-top-center","rejectButtonCaptionColor":"#FFFFFF","rejectButtonColor":"#038037","rejectButtonDisplay":true,"showTitle":false,"showTotalNumberOfProviders":true,"textColor":"#000000"}};
_iub.csLangConfiguration = {"it":{"cookiePolicyId":32935352}};
</script>
<script type="text/javascript" src="//cs.iubenda.com/sync/1381751.js"></script>
<script type="text/javascript" src="//cdn.iubenda.com/cs/gpp/stub.js"></script>
<script type="text/javascript" src="//cdn.iubenda.com/cs/iubenda_cs.js" charset="UTF-8" async></script>
<?php }, 1);

add_action('wp_footer', function () { ?>
<script type="text/javascript">
(function (w,d) {
    var loader = function () {
        var s = d.createElement("script"), tag = d.getElementsByTagName("script")[0];
        s.src = "https://cdn.iubenda.com/iubenda.js";
        tag.parentNode.insertBefore(s, tag);
    };
    if (w.addEventListener) { w.addEventListener("load", loader, false); }
    else if (w.attachEvent) { w.attachEvent("onload", loader); }
    else { w.onload = loader; }
})(window, document);
</script>
<?php });