<?php
use function DonationManager\utilities\{get_alert};
use function DonationManager\globals\{add_html};

add_html( get_alert([
  'type'        => 'warning',
  'title'       => 'Duplicate Submission Detected',
  'description' => '<p>We have already received this donation and entered it into our system. Please check your email for a confirmation of your submission.</p>',
]));