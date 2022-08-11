<?php
use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html};

$html = render_template( 'form0.enter-your-zipcode', [ 'nextpage' => $nextpage ] );
add_html( $html );
