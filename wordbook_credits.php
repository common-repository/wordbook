<?php

function wordbook_option_credits_render($donors) {
?>

    <div style="float: left; vertical-align: top; margin-right: 1em;">

    <ul>

<?php
    foreach ($donors as $title => $url) {
        if ($url) {
            $prefix = '<a href="' . $url . '" target="_blank">';
            $suffix = '</a>';
        } else {
            $prefix = '';
            $suffix = '';
        }
?>

        <li><?php echo $prefix . htmlentities($title) . $suffix; ?></li>

<?php
    }
?>

    </ul>

    </div>

<?php
}

function wordbook_option_credits() {
    $donors = array(
        'The Camaras' => 'http://thecamaras.net/',
        "Steve's Space" => 'http://www.steve-c.co.uk/',
        'The .Plan' => 'http://alex.tsaiberspace.net/',
        'drunkencomputing' => 'http://drunkencomputing.com/',
        'life by way of media' => 'http://trentadams.com/',
        'Mount Hermon' => 'http://www.mounthermon.org/',
        'Superjudas bloggt' => 'http://superjudas.net/',
        'Blood, Glory & Steel' => 'http://blog.ofsteel.net/',
        'itinerant' => 'http://shashikiran.com/',
        'Patrick Simpson' => 'http://www.patricksimpson.nl/',
        'Hippocrates in San Diego' => 'http://hippocratesinsandiego.com/',
        "It's a Small World After All" => 'http://www.peterbakke.com/',
        'Miss Mentor' => 'http://www.missmentor.com/',
        'Gary Said' => 'http://garysaid.com/',
        'Daily Horse Racing Ratings & Analysis' => 'http://formbet.co.uk/',
        'Interactive Webs' => 'http://www.interactivewebs.com/',
        'Achieve and Maintain Optimal Health' =>
            'http://blog.freetobelean.com/wordpress',
        'Office Tips and Methods' => 'http://www.officetipsandmethods.com/',
        'Marcus East' => 'http://www.marcuseast.org/',
        'Linda Eligoulachvili' => null,
        'Light and Truth Specialties LLC' => null,
        'Michael Sweeney Photography' =>
            'http://www.michaelsweeneyphotography.com/',
        'Distinction Unlimited' =>
            'http://distinctionunlimited.com/',
        'Your Wellness Toolkit' => 'http://www.yourwellnesstoolkit.com/',
        'GearChic' => 'http://www.gearchic.com/',
        'Patrick Baldwin Photography' => 'http://www.patrickbaldwin.com/',
        'Jeffrey Paul' => 'http://www.jeffandcrystal.com/',
        'Temple of Poi' => 'http://templeofpoi.com',
        );
    $off = (count($donors) + 1) / 2;
    $left = array_slice($donors, 0, $off);
    $right = array_slice($donors, $off);
?>

<h3><?php _e('Thanks'); ?></h3>
<div class="wordbook_thanks">

    Special thanks to:

    <div>

<?php

    wordbook_option_credits_render($left);
    wordbook_option_credits_render($right);

?>

    </div>

    <div style="clear: left;">&nbsp;</div>

    If you find this plugin useful, please consider making a donation to
    support its continued development; any amount is welcome:

    <div style="text-align: center; margin: 1em auto;">
        <form name="_xclick" action="https://www.paypal.com/cgi-bin/webscr"
                method="post">
            <input type="hidden" name="cmd" value="_xclick">
            <input type="hidden" name="business" value="wordbook@tsaiberspace.net">
            URL for acknowledgement (optional):
            <input type="text" name="item_name" value="">
            <input type="hidden" name="currency_code" value="USD">
            <br>
            <input type="image" src="http://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" valign="center" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
        </form>
    </div>
</div>

<?php
}

// vim:et sw=4 ts=8
?>
