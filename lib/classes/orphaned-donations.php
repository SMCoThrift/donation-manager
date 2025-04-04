<?php

class DMOrphanedDonations {
  const DBVER = '1.0.6';

  private string $orphaned_donations_hook; // Declare property

  private static $instance = null;

  public static function get_instance() {
    if ( null == self::$instance ) {
      self::$instance = new self;
    }

    return self::$instance;
  }

  private function __construct() {
    register_activation_hook( __FILE__, array( 'DMOrphanedDonations', 'db_tables_install' ) );
    //add_action( 'plugins_loaded', array( 'DMOrphanedDonations', 'db_tables_check' ) ); // 10/24/2022 (08:29) - don't check for DB tables b/c these are handled by lib/fns/db.php

    add_action( 'admin_menu', array( $this, 'callback_orphaned_donations_admin' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    add_action( 'wp_ajax_orphaned_donations_ajax', array( $this, 'callback_ajax' ) );

    // AJAX TESTS for Orphaned Donations
    add_action( 'wp_ajax_orphaned_utilities_ajax', array( $this, 'utilities_callback' ) );
    add_action( 'wp_ajax_query_orphaned_donations', array( $this, 'query_orphaned_donations' ) );
    add_action( 'wp_ajax_query_orphaned_donation_pickup_providers', array( $this, 'query_orphaned_donation_pickup_providers' ) );
  }

  /**
   * Enqueues scripts for our admin page
   *
   * @since 1.2.0
   *
   * @param string  $hook The admin page's hook.
   * @return void
   */
  public function admin_enqueue_scripts( $hook ) {
    if ( 'donation_page_orphaned-donations' != $hook )
      return;

    wp_enqueue_style( 'dm-admin-css', DONMAN_PLUGIN_URL . 'lib/css/admin.css', ['dashicons', 'thickbox'], filemtime( DONMAN_PLUGIN_PATH . 'lib/css/admin.css' ) );

    wp_enqueue_style( 'datatables', 'https://cdn.datatables.net/r/dt/dt-1.10.9,fh-3.0.0/datatables.min.css' );
    wp_register_script( 'datatables', 'https://cdn.datatables.net/r/dt/dt-1.10.9,fh-3.0.0/datatables.min.js', array( 'jquery' ) );

    wp_enqueue_script( 'dm-orphaned-donations', DONMAN_PLUGIN_URL . 'lib/js/orphaned-donations.js', ['jquery', 'media-upload', 'thickbox', 'jquery-ui-progressbar', 'datatables', 'jquery-color'], filemtime( DONMAN_PLUGIN_PATH . 'lib/js/orphaned-donations.js' ), false );


    $month_options = array( '<option value="">All dates</option>' );
    $start_month = '2015-09-01';

    $option_month = current_time( 'Y-m' ) . '-01';
    while ( $option_month != $start_month ) {
      $month_options[] = '<option value="' . $option_month . '">' . date( 'M Y', strtotime( $option_month ) ) . '</option>';
      $option_month = new DateTime( $option_month );
      $option_month->sub( new DateInterval( 'P1M' ) );
      $option_month = $option_month->format( 'Y-m-d' );
    }
    $month_options[] = '<option value="' . $start_month . '">' . date( 'M Y', strtotime( $start_month ) ) . '</option>';
    $month_options = implode( '', $month_options );

    $debug = ( DONMAN_DEV_ENV )? true : false;

    wp_localize_script( 'dm-orphaned-donations', 'wpvars', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'month_options' => $month_options, 'debug' => $debug ) );
  }

  /**
   * Processes AJAX callbacks for the Orphaned Donations admin screens
   *
   * @since 1.2.0
   *
   * @return string JSON Object.
   */
  public function callback_ajax() {
    // Restrict access to WordPress `administrator` role
    if ( ! current_user_can( 'activate_plugins' ) )
      return;

    $response = new stdClass();

    $cb_action = $_POST['cb_action'];
    $id = ( isset( $_POST['csvID'] ) )? $_POST['csvID'] : '';

    switch ( $cb_action ) {
    case 'delete_csv':
      wp_delete_attachment( $id );
      $data['deleted'] = true;

      $response->data = $data;
      break;

    case 'get_csv_list':
      $args = array(
        'post_type' => 'attachment',
        'numberposts' => -1,
        'post_mime_type' => 'text/csv',
        'orderby' => 'date',
        'order' => 'DESC'

      );
      $files = get_posts( $args );
      $x = 0;
      foreach ( $files as $file ) {
        if ( stristr( $file->post_title, 'all-donations' ) )
          continue;
        setup_postdata( $file );
        $data['csv'][$x]['id'] = $file->ID;
        $data['csv'][$x]['post_title'] = $file->post_title;
        $data['csv'][$x]['timestamp'] = date( 'm/d/y g:i:sa', strtotime( $file->post_date ) );
        $data['csv'][$x]['filename'] = basename( $file->guid );
        $data['csv'][$x]['last_import'] = get_post_meta( $file->ID, '_last_import', true );
        if ( empty( $data['csv'][$x]['last_import'] ) )
          $data['csv'][$x]['last_import'] = 0;
        $x++;
      }

      $response->data = ( isset( $data ) )? $data : '';
      break;

    case 'import_csv':
      $limit = 100; // limit the number of rows to import
      $offset = $_POST['csvoffset'];

      $response->id = $id;
      $response->title = get_the_title( $id );

      // Get the URL and filename of the CSV
      $url = wp_get_attachment_url( $id );
      $response->url = $url;
      $response->filename = basename( $url );

      // Open this CSV
      $csvfile = str_replace( get_bloginfo( 'url' ) . '/', ABSPATH, $url );
      $csv = $this->open_csv( $csvfile, $id );
      $response->total_rows = count( $csv['rows'] );
      $csv['rows'] = array_slice( $csv['rows'], $offset, $limit );
      $response->selected_rows = count( $csv['rows'] );
      $response->csv = $csv;
      //$response->contacts = array();
      foreach ( $response->csv['rows'] as $row ) {
        $x = 0;
        $contact = array();
        foreach ( $row as $key => $value ) {
          $assoc_key = $csv['columns'][$x];
          $contact[$assoc_key] = $value;
          $x++;
        }
        $last_import = $offset + $limit;

        $args = array(
          'store_name' => $contact['store_name'],
          'zipcode' => $contact['zipcode'],
          'email' => $contact['email'],
          'receive_emails' => $contact['receive_emails'],
          'csvID' => $id,
          'offset' => $last_import,
        );

        if ( isset( $contact['priority'] ) )
          $args['priority'] = $contact['priority'];

        $status = $this->contact_update( $args );
        $response->statuses[] = $status;
      }
      $response->current_offset = ( 1 == $limit )? 'Importing row ' . ( $offset + 1 ) : 'Importing rows '.( $offset + 1 ).' - '.( $offset + $limit );
      $response->offset = $offset + $limit;
      break;

    case 'load_csv':
      $url = wp_get_attachment_url( $id );
      $data['id'] = $id;
      $data['url'] = $url;
      $data['title'] = get_the_title( $id );
      $data['filename'] = basename( $url );
      $csvfile = str_replace( get_bloginfo( 'url' ). '/', ABSPATH, $url );
      $data['filepath'] = $csvfile;
      $data['csv'] = $this->open_csv( $csvfile, $id );
      $data['offset'] = 0;

      $response->notice = '<div class="notice error" id="import-notice"><p>' . esc_attr( 'IMPORTANT: Do not leave or refresh this screen until the import completes!', 'wp_admin_style' ) . '</p></div>';

      $response->csv = $data;
      break;

    default:
      // code...
      break;
    }

    wp_send_json( $response );
  }

  /**
   * Adds an Orphaned Donations submenu page
   *
   * @since 1.2.0
   *
   * @return void
   */
  function callback_orphaned_donations_admin() {
    $orphaned_donations_hook = add_submenu_page( 'edit.php?post_type=donation', 'Orphaned Donations', 'Orphaned Donations', 'activate_plugins', 'orphaned-donations', array( $this, 'page_orphaned_donations_admin' ) );
    $this->orphaned_donations_hook = $orphaned_donations_hook;

    add_action( 'load-' . $orphaned_donations_hook, array( $this, 'callback_orphaned_donations_help' ) );
  }

  /**
   * Adds contextual help for Orphaned Donations
   *
   * @since 1.3.0
   *
   * @return void
   */
  function callback_orphaned_donations_help() {
    $screen = get_current_screen();
    $screen->add_help_tab( array(
        'id' => 'orphaned-donations-import',
        'title' => __( 'Import' ),
        'callback' => array( $this, 'callback_help' ),
      ) );
  }

  function callback_help(){
    $html = DonationManager\templates\get_template_part( 'help.orphaned-donations.import' );
    echo $html;
  }

  /**
   * Updates/creates contacts in the orphaned donations contacts table.
   *
   * @since 1.2.0
   *
   * @param array   $args {
   *      @type string $store_name Name of store associated with contact.
   *      @type string $zipcode Zipcode.
   *      @type string $email Contact's email address.
   *      @type bool $receive_emails `true` or `false`. Optional.
   *      @type bool $priority `true` or `false`. Defaults to `false`. Optional.
   * }
   * @return string Update status message.
   */
  public function contact_update( $args ) {
    global $wpdb;

    $defaults = array(
      'store_name' => null,
      'zipcode' => null,
      'email' => null,
      'receive_emails' => true,
      'priority' => false,
      'show_in_results' => false,
    );

    $args = wp_parse_args( $args, $defaults );
    $args['email'] = strtolower( $args['email'] );

    if ( empty( $args['store_name'] ) || empty( $args['zipcode'] ) || empty( $args['email'] ) )
      return false;

    $receive_emails = ( true == $args['receive_emails'] || 1 == $args['receive_emails'] )? 1 : 0;

    $priority = ( true == $args['priority'] || 1 == $args['priority'] )? 1 : 0;

    $show_in_results = ( true == $args['show_in_results'] )? 1 : 0;

    $emails = array();
    if ( stristr( $args['email'], ',' ) ) {
      $emails = explode( ',', $args['email'] );
      foreach ( $emails as $email ) {
        $this->contact_update( array( 'store_name' => $args['store_name'], 'zipcode' => $args['zipcode'], 'email' => trim( $email ), 'receive_emails' => $receive_emails, 'priority' => $priority ) );
      }
      return;
    }

    if ( ! is_email( $args['email'] ) )
      return false;

    // Returns `false` for does not exist, or contact ID.
    $contact = $this->contact_exists( array( 'store_name' => $args['store_name'], 'zipcode' => $args['zipcode'], 'email' => $args['email'] ) );

    if ( false == $contact ) {
      $args['store_name'] = stripslashes_deep( $args['store_name'] );
      $data = array(
        'store_name' => $args['store_name'],
        'zipcode' => $args['zipcode'],
        'email_address' => $args['email'],
        'receive_emails' => $receive_emails,
        'priority' => $priority,
        'show_in_results' => $show_in_results,
      );

      $format = array(
        '%s',
        '%s',
        '%s',
        '%d',
        '%d',
        '%d',
      );

      $affected = $wpdb->insert( $wpdb->prefix . 'donman_contacts', $data, $format );

      if ( false === $affected ) {
        $message = 'Error encountered while attempting to create contact';
      } else if ( 0 === $affected ) {
          $message = '0 rows affected';
        } else {
        $message = $affected . ' contact created';
      }
    } else {
      $message = 'Contact already exists.';
    }
    /**
     * By commenting out the following our import will only add new contacts.
     **/
    /*
    elseif ( is_numeric( $contact ) ) {
      // The following logic requires a `receive_emails` column in our import CSV:
      $sql = 'UPDATE ' . $wpdb->prefix . 'donman_contacts' . ' SET receive_emails="%d" WHERE ID=' . $contact;
      $affected = $wpdb->query( $wpdb->prepare( $sql, $receive_emails ) );
      if ( false === $affected ) {
        $message = 'Could not update contact';
      } else if ( 0 === $affected ) {
          $message = '0 rows updated';
        } else {
        $message = $affected . ' rows updated';
      }
    }
    */

    $message.= ' (' . $args['store_name'] . ', ' . $args['zipcode'] . ', ' . $args['email'] . ')';

    return $message;
  }

  /**
   * Checks to see if a contact exists.
   *
   * Queries store_name + zipcode + email_address to see if we find
   * a matching contact.
   *
   * @since 1.2.0
   *
   * @param array   $args {
   *      @type string $store_name Name of store associated with contact.
   *      @type string $zipcode Zipcode.
   *      @type string $email Contact email.
   * }
   * @return mixed Returns contact ID if exists. `false` if not exists.
   */
  private function contact_exists( $args ) {
    global $wpdb;

    $defaults = array(
      'store_name' => null,
      'zipcode' => null,
      'email' => null,
    );

    $args = wp_parse_args( $args, $defaults );
    $args['email'] = strtolower( $args['email'] );

    if ( empty( $args['store_name'] ) || empty( $args['zipcode'] ) || empty( $args['email'] ) )
      return 'ERROR - missing args for contact_exists';

    $sql = 'SELECT ID FROM ' . $wpdb->prefix . 'donman_contacts' . ' WHERE store_name="%s" AND zipcode="%s" AND email_address="%s" ORDER BY zipcode ASC';
    $contacts = $wpdb->get_results( $wpdb->prepare( $sql, $args['store_name'], $args['zipcode'], $args['email'] ) );
    if ( $contacts ) {
      return $contacts[0]->ID;
    } else {
      return false;
    }
  }

  /**
   * Returns orphaned donation pickup providers SQL.
   */
  private function _get_orphaned_donation_pickup_providers_query( $args ){
    global $wpdb;

    $args = wp_parse_args( $args, array(
        'orderby' => 'store_name',
        'sort' => 'DESC',
        'limit' => null,
        'offset' => null,
        'search' => null,
        'searchfield' => 'email',
        'priority' => 'all',
    ));

    // Since priority, orderby, sort, and limit can be passed via
    // AJAX, let's ensure these vars are clean:
    $priority = '';
    if ( 'all' !== $args['priority'] ) {
      if ( 0 === $args['priority'] || 1 === $args['priority'] )
        $priority = "\n\t\t" . 'AND priority="' . $args['priority'] . '"';
    }

    $order_cols = array( 'store_name', 'email_address', 'zipcode' );
    $orderby = ( 'total_donations' != $args['orderby'] && in_array( $args['orderby'],  $order_cols ) )? $args['orderby'] : 'store_name';

    $sort = ( 'DESC' != $args['sort'] )? 'ASC' : 'DESC';

    $limit = ( is_numeric( $args['limit'] ) )? 'LIMIT ' . $args['limit'] : '';
    if ( is_numeric( $args['offset'] ) && 0 < $args['offset'] )
      $limit.= ' OFFSET ' . $args['offset'];

    $search = '';
    if ( ! empty( $args['search'] ) ) {
      $searchfields = array( 'store_name', 'zipcode', 'email_address' );
      $searchfield = ( in_array( $args['searchfield'], $searchfields ) )? $args['searchfield'] : 'email_address';

      $search = "\n\t\t" . 'WHERE ' . $searchfield . ' LIKE \'%' . esc_sql( $args['search'] ) . '%\'';
    }

    $sql_format = 'SELECT ID,store_name,zipcode,email_address,receive_emails,priority
        FROM ' . $wpdb->prefix . 'donman_contacts %4$s
        ORDER BY %1$s %2$s
        %3$s';

    return sprintf( $sql_format, $orderby, $sort, $limit, $search );
  }

  /**
   * Returns orphaned donations SQL.
   *
   * @global object $wpdb WordPress database object.
   *
   * @access query_orphaned_donations()
   * @since 1.2.6
   *
   * @param array   $args{
   *      Array of arguments.
   *
   *      @type string $orderby Column SQL result will be ordered by.
   *      @type string $sort SQL sorting direction - ASC|| DESC.
   *      @type int $limit Optional. Limit the results by this number.
   *      @type int $offset Optional. Offset the query by this number.
   *      @type date $start_date Optional. Start date for a date range.
   * }
   * @return string SQL for querying orphaned donations.
   */
  public function _get_orphaned_donations_query( $args ) {
    global $wpdb;

    $args = wp_parse_args( $args, array(
        'orderby' => 'total_donations',
        'sort' => 'DESC',
        'limit' => null,
        'offset' => null,
        'start_date' => null,
        'end_date' => null,
        'search' => null,
        'priority' => 'all',
        'filterby' => 'email_address',
      ) );

    // Since priority, orderby, sort, and limit can be passed via
    // AJAX, let's ensure these vars are clean:
    $priority = '';
    if ( 'all' !== $args['priority'] ) {
      if ( 0 === $args['priority'] || 1 === $args['priority'] )
        $priority = "\n\t\t" . 'AND priority="' . $args['priority'] . '"';
    }

    $date_range = '';
    if ( ! empty( $args['start_date'] ) ) {
      $start_date_ts = strtotime( $args['start_date'] );
      $num_of_days = date( 't', $start_date_ts );
      $end_date = date( 'Y', $start_date_ts ) . '-' . date( 'm', $start_date_ts ) . '-' . $num_of_days;
      $date_range = "\n\t\t" . 'AND timestamp >= \'' . date( 'Y-m-d', $start_date_ts ) . '\' AND timestamp <= \'' . $end_date . '\'';
    }

    $order_cols = array( 'store_name', 'zipcode', 'email_address', 'timestamp', 'total_donations' );
    $orderby = ( 'total_donations' != $args['orderby'] && in_array( $args['orderby'],  $order_cols ) )? $args['orderby'] : 'total_donations';

    $sort = ( 'DESC' != $args['sort'] )? 'ASC' : 'DESC';

    $limit = ( is_numeric( $args['limit'] ) )? 'LIMIT ' . $args['limit'] : '';
    if ( is_numeric( $args['offset'] ) && 0 < $args['offset'] )
      $limit.= ' OFFSET ' . $args['offset'];

    $filterby_cols = array( 'email_address', 'store_name', 'zipcode' );
    $filterby = ( in_array( $args['filterby'], $filterby_cols ) )? $args['filterby'] : 'email_address' ;

    $search = ( ! empty( $args['search'] ) )? "\n\t\t" . 'AND ' . $filterby . ' LIKE \'%' . esc_sql( $args['search'] ) . '%\'' : null;

    $sql_format = 'SELECT contacts.ID,store_name,zipcode,email_address,receive_emails,timestamp,COUNT(donation_id) AS total_donations
        FROM ' . $wpdb->prefix . 'donman_contacts AS contacts, ' . $wpdb->prefix . 'donman_orphaned_donations AS donations
        WHERE contacts.ID = donations.contact_id %s%s%s
        GROUP BY contact_id
        ORDER BY %s %s
        %s';

    return sprintf( $sql_format, $priority, $date_range, $search, $orderby, $sort, $limit );
  }

  /**
   * Opens a CSV file, populates an array for return
   *
   * @since 1.2.0
   *
   * @param string  $csvfile Full filename of CSV file.
   * @param int     $csvID   Post ID of CSV.
   * @return array CSV returned as an array.
   */
  public function open_csv( $csvfile = '', $csvID = null ) {
    if ( empty( $csvfile ) )
      return $csv['error'] = 'No CSV specified!';
    if ( empty( $csvID ) )
      return $csv['error'] = 'No csvID sent!';

    if ( false === ( $csv = get_transient( 'csv_' . $csvID ) ) ) {
      $csv = array( 'row_count' => 0, 'column_count' => 0, 'columns' => array(), 'rows' => array() );
      if ( !empty( $csvfile ) && file_exists( $csvfile ) ) {
        if ( ( $handle = @fopen( $csvfile, 'r' ) ) !== false ) {
          $x = 0;
          while ( $row = fgetcsv( $handle, 2048, ',' ) ) {
            if ( $x == 0 ) {
              // trim spaces from column headings
              foreach ( $row as $key => $heading ) {
                $row[$key] = trim( $heading );
              }
              $csv['columns'] = $row;
            } else {
              //array_walk( $row, array( $this, 'trim_csv_row' ) );
              $csv['rows'][] = $row;
              $csv['row_count']++;
            }
            $x++;
          }
          $csv['column_count'] = count( $csv['columns'] );
        }
      }
      set_transient( 'csv_' . $csvID, $csv );
    }

    return $csv;
  }

  /**
   * Interface for Donations > Orphaned Donations page
   *
   * @since 1.2.0
   *
   * @return void
   */
  public function page_orphaned_donations_admin() {
    $active_tab = ( isset( $_GET['tab'] ) )? $_GET['tab'] : 'default';

    ?><div class="wrap">

            <h2>Orphaned Donation Manager</h2>

            <h2 class="nav-tab-wrapper">
                <a href="edit.php?post_type=donation&page=orphaned-donations" class="nav-tab<?php echo ( 'default' == $active_tab )? ' nav-tab-active' : ''; ?>">Donations by Store</a>
                <a href="edit.php?post_type=donation&page=orphaned-donations&tab=providers" class="nav-tab<?php echo ( 'providers' == $active_tab )? ' nav-tab-active' : ''; ?>">Providers</a>
                <a href="edit.php?post_type=donation&page=orphaned-donations&tab=import" class="nav-tab<?php echo ( 'import' == $active_tab )? ' nav-tab-active' : ''; ?>">Import</a>
                <a href="edit.php?post_type=donation&page=orphaned-donations&tab=utilities" class="nav-tab<?php echo ( 'utilities' == $active_tab )? ' nav-tab-active' : ''; ?>">Utilities</a>
            </h2>

            <div class="wrap">
            <?php
    switch ( $active_tab ) {
      case 'import':
        include_once DONMAN_PLUGIN_PATH . 'lib/views/orphaned-donations.import.php';
        break;
      case 'utilities':
        include_once DONMAN_PLUGIN_PATH . 'lib/views/orphaned-donations.utilities.php';
        break;
      case 'providers':
        include_once DONMAN_PLUGIN_PATH . 'lib/views/orphaned-donations.providers.php';
        break;
      default:
        include_once DONMAN_PLUGIN_PATH . 'lib/views/orphaned-donations.php';
        break;
    }
?>
            </div>

        </div><?php
  }

  public function query_orphaned_donation_pickup_providers(){
    global $wpdb;

    $response = new stdClass(); // returned as JSON
    $args = array(); // passed to WP_Query( $args )

    $response->draw = $_POST['draw']; // $draw == 1 for the first request when the page is requested

    // Paging and offset
    $response->offset = ( isset( $_POST['start'] ) && is_numeric( $_POST['start'] ) )? $_POST['start'] : 0;
    $args['offset'] = $response->offset;

    $response->limit = ( isset( $_POST['length'] ) )? (int) $_POST['length'] : 10;

    // ORDER BY
    $cols = array( 1 => 'store_name', 2 => 'email_address', 3 => 'zipcode', 4 => 'priority' );
    $order_key = ( isset( $_POST['order'][0]['column'] ) && array_key_exists( $_POST['order'][0]['column'], $cols ) )? $_POST['order'][0]['column'] : 0;
    $response->orderby = $cols[$order_key];
    $response->order_key = $order_key;

    // Sorting (ASC||DESC)
    $response->sort = ( isset( $_POST['order'][0]['dir'] ) )? strtoupper( $_POST['order'][0]['dir'] ) : 'DESC';

    // Search
    if ( isset( $_POST['search']['value'] ) ) {
      $response->search = $_POST['search']['value'];
    }
    if( isset( $_POST['searchfield'] ) ){
      $response->searchfield = $_POST['searchfield'];
    }
    if( ! isset( $priority ) )
      $priority = 'all';

    // SQL: Count the total number of records
    $count_sql = $this->_get_orphaned_donation_pickup_providers_query( array( 'orderby' => $response->orderby, 'sort' => $response->sort, 'search' => $response->search, 'priority' => $priority, 'searchfield' => $response->searchfield ) );

    $wpdb->get_results( $count_sql );
    $response->recordsTotal = (int) $wpdb->num_rows;
    $response->recordsFiltered = (int) $wpdb->num_rows;
    $response->count_sql = $wpdb->last_query;

    // Get the data
    $data = array();

    // SQL: Get the stores
    $stores_sql = $this->_get_orphaned_donation_pickup_providers_query( array( 'orderby' => $response->orderby, 'sort' => $response->sort, 'limit' => $response->limit, 'offset' => $response->offset, 'search' => $response->search, 'priority' => $priority, 'searchfield' => $response->searchfield ) );

    $stores = $wpdb->get_results( $stores_sql );

    $response->stores = $stores;
    $response->stores_sql = $stores_sql;

    if ( 0 < count( $stores ) ) {
      $x = 0;
      foreach ( $stores as $store ) {
        $domain = '';
        if ( stristr( $store->email_address, '@' ) ) {
          $email_array = explode( '@', $store->email_address );
          $domain = $email_array[1];
        }
        $website = ( ! empty( $domain ) && ! in_array( $domain, GENERIC_DOMAINS ) )? '<a href="http://' . $domain . '" target="_blank">' . $domain . '</a>' : '&nbsp;';

        $subscribed = ( true == $store->receive_emails )? '<span style="color: #090">Yes</span>' : '<span style="color: #900">No</span>';
        $data[$x] = array(
          'id' => $store->ID,
          'store_name' => $store->store_name,
          'zipcode' => $store->zipcode,
          'website' => $website,
          'email_address' => '<a href="mailto:' . $store->email_address . '">' . $store->email_address . '</a>',
          'receive_emails' => $subscribed,
          'priority' => $store->priority,
        );
        $x++;
      }
    }

    $response->data = $data;

    wp_send_json( $response );
  }

  /**
   * Returns JSON formatted orphaned donation queries
   *
   * This method has been built to work with data sent
   * by datatables.js. In particular, this function receives the
   * following $_POST vars:
   *
   *  @type int $draw - Draw counter. This is used by DataTables to ensure that the Ajax returns from server-side
   *      processing requests are drawn in sequence by DataTables.
   *  @type int $start - Paging first record indicator, maps to $wp_query->$args->$offset.
   *  @type int $length - Number of records for the table to display, maps to $wp_query->$args->$post_per_page.
   *  @type int $auction - The WP taxonomy ID for the queried auction, maps to $wp_query->$args->$tax_query->$terms.
   *  @type int $order[0]['column'] - The column which specifies $wp_query->$args->$orderby.
   *  @type str $order[0]['dir'] - Sort by ASC|DESC, maps to $wp_query->$args->$order.
   *  @type str $search['value'] - Search string, maps to $response->search.
   *
   * For more info, see the [DataTables Server-side Processing docs]
   * (http://datatables.net/manual/server-side).
   *
   * @since 1.2.6
   *
   * @return string JSON formatted orphaned donation query
   */
  public function query_orphaned_donations() {
    global $wpdb;

    $response = new stdClass(); // returned as JSON
    $args = array(); // passed to WP_Query( $args )

    $response->draw = $_POST['draw']; // $draw == 1 for the first request when the page is requested

    $start_date = '';
    if ( isset( $_POST['month'] ) ) {
      $response->month = $_POST['month'];
      $start_date = $response->month;
    }

    // Donation Priority
    $priority = 'all';
    if ( isset( $_POST['priority'] ) ) {
      $priority = $_POST['priority'];
      switch ( $priority ) {
      case 'nonprofit':
        $priority = 0;
        break;

      case 'priority':
        $priority = 1;
        break;
      }
    }
    $response->priority = $priority;

    // Paging and offset
    $response->offset = ( isset( $_POST['start'] ) && is_numeric( $_POST['start'] ) )? $_POST['start'] : 0;
    $args['offset'] = $response->offset;

    $response->limit = ( isset( $_POST['length'] ) )? (int) $_POST['length'] : 10;

    // ORDER BY
    $cols = array( 0 => 'store_name', 1 => 'email_address', 2 => 'total_donations' );
    $order_key = ( isset( $_POST['order'][0]['column'] ) && array_key_exists( $_POST['order'][0]['column'], $cols ) )? $_POST['order'][0]['column'] : 2;
    $response->orderby = $cols[$order_key];

    // Sorting (ASC||DESC)
    $response->sort = ( isset( $_POST['order'][0]['dir'] ) )? strtoupper( $_POST['order'][0]['dir'] ) : 'DESC';

    // Search
    if ( isset( $_POST['search']['value'] ) ) {
      $response->search = $_POST['search']['value'];
    }

    // Filterby
    $response->filterby = ( isset( $_POST['filterby'] ) )? $_POST['filterby'] : '';

    // SQL: Count the total number of records
    $count_sql = $this->_get_orphaned_donations_query( array( 'orderby' => $response->orderby, 'sort' => $response->sort, 'start_date' => $start_date, 'search' => $response->search, 'priority' => $priority, 'filterby' => $response->filterby ) );

    $wpdb->get_results( $count_sql );
    $response->recordsTotal = (int) $wpdb->num_rows;
    $response->recordsFiltered = (int) $wpdb->num_rows;
    $response->count_sql = $wpdb->last_query;

    // Get the data
    $data = array();

    // SQL: Get the stores
    $stores_sql = $this->_get_orphaned_donations_query( array( 'orderby' => $response->orderby, 'sort' => $response->sort, 'limit' => $response->limit, 'offset' => $response->offset, 'start_date' => $start_date, 'search' => $response->search, 'priority' => $priority, 'filterby' => $response->filterby ) );
    $stores = $wpdb->get_results( $stores_sql );

    $response->stores = $stores;
    $response->stores_sql = $stores_sql;

    if ( 0 < count( $stores ) ) {
      $x = 0;
      foreach ( $stores as $store ) {
        $domain = '';
        if ( stristr( $store->email_address, '@' ) ) {
          $email_array = explode( '@', $store->email_address );
          $domain = $email_array[1];
        }
        $website = ( ! empty( $domain ) && ! in_array( $domain, GENERIC_DOMAINS ) )? '<a href="http://' . $domain . '" target="_blank">' . $domain . '</a>' : '&nbsp;';

        $subscribed = ( true == $store->receive_emails )? '<span style="color: #090">Yes</span>' : '<span style="color: #900">No</span>';
        $data[$x] = array(
          'id' => $store->ID,
          'store_name' => $store->store_name,
          'zipcode' => $store->zipcode,
          'website' => $website,
          'email_address' => '<a href="mailto:' . $store->email_address . '">' . $store->email_address . '</a>',
          'receive_emails' => $subscribed,
          'total_donations' => $store->total_donations,
        );
        $x++;
      }
    }

    $response->data = $data;

    wp_send_json( $response );
  }

  /**
   * Trim spaces from CSV column values
   */
  private function trim_csv_row( &$value, $key ) {
    $value = htmlentities( utf8_encode( trim( $value ) ), ENT_QUOTES, 'UTF-8' );
  }

  /**
   * Updates a row in the `donman_contacts` table
   *
   * @param      int    $id     The row ID
   * @param      string   $field  The column name
   * @param      mixed    $value  The column value
   *
   * @return     boolean  Returns TRUE if contact updated.
   */
  public static function update_contact( $id = null, $field = null, $value = null ){
    if( is_null( $id ) || is_null( $field ) || is_null( $value ) )
      return false;

    $fields = ['store_name','zipcode','email_address','receive_emails','priority','show_in_results'];
    if( ! in_array( $field, $fields ) )
      return false;

    global $wpdb;

    $sql = 'UPDATE ' . $wpdb->prefix . 'donman_contacts SET ' . $field . '=%s WHERE ID=%d';

    $rows_affected = $wpdb->query( $wpdb->prepare( $sql, $value, $id ) );

    $status = ( 1 == $rows_affected )? true : false ;
    return $status;
  }

  /**
   * Unsubscribes an orphaned donation contact email
   *
   * @since 1.2.2
   *
   * @param string  $email Contact email to unsubscribe.
   * @return int/bool Returns number of rows affected or false on failure.
   */
  public static function update_email( $email = null, $action = 'unsubscribe' ) {
    if ( is_null( $email ) || ! is_email( $email ) )
      return false;

    global $wpdb;

    $receive_emails = ( 'unsubscribe' == $action )? 0 : 1;

    $sql = 'UPDATE ' . $wpdb->prefix . 'donman_contacts SET receive_emails=%d WHERE email_address="%s"';
    $rows_affected = $wpdb->query( $wpdb->prepare( $sql, $receive_emails, $email ) );

    return $rows_affected;
  }

  /**
   * Hooked to `wp_ajax_orphaned_utilities_ajax`
   */
  public function utilities_callback() {
    // Restrict access to WordPress `administrator` role
    if ( ! current_user_can( 'activate_plugins' ) )
      return;

    global $wpdb;

    $response = new stdClass();

    $cb_action = ( isset( $_POST['cb_action'] ) )? $_POST['cb_action'] : '';
    $pcode = ( isset( $_POST['pcode'] ) )? $_POST['pcode'] : '';

    $response->pcode = $pcode;

    switch ( $cb_action ) {
    case 'add_contact':
      $args['zipcode'] = $_POST['zipcode'];
      $args['email'] = $_POST['email_address'];
      $args['store_name'] = $_POST['store_name'];
      $args['priority'] = $_POST['priority'];
      $args['show_in_results'] = ( 1 == $_POST['show_in_results'] )? 1 : 0;

      $message = $this->contact_update( $args );
      $response->output = '<pre>' . $message . '</pre>';
      break;

    case 'delete_contact':
      if( ! isset( $_POST['ID'] ) || ! is_numeric( $_POST['ID'] ) )
        return false;

      $args['ID'] = $_POST['ID'];
      $sql = 'DELETE FROM ' . $wpdb->prefix . 'donman_contacts WHERE ID=%d';
      $success = $wpdb->query( $wpdb->prepare( $sql, $args['ID'] ) );
      $response->ID = $args['ID'];
      $response->success = $success;
      break;

    case 'search_replace_email':
      $search = $_POST['search'];
      $replace = $_POST['replace'];

      $errors = array();
      if ( empty( $search ) )
        $errors[] = '$search email is empty!';

      if ( ! empty( $search ) && ! is_email( $search ) )
        $errors[] = '$search is not a valid email!';

      if ( ! empty( $replace ) && ! is_email( $replace ) )
        $errors[] = '$replace is not a valid email!';

      if ( 0 < count( $errors ) )
        $response->output = '<pre>There were some errors in your request:' . "\n" . implode( "\n-", $errors ) . '</pre>';

      $message = '';
      if ( ! empty( $replace ) ) {
        $sql = 'UPDATE ' . $wpdb->prefix . 'donman_contacts SET email_address = replace(email_address,"%s","%s") WHERE email_address="%s"';
        $success = $wpdb->query( $wpdb->prepare( $sql, $search, $replace, $search ) );
        $message = '<br />';
        $message.= ( true == $success )? 'SUCCESS: Replaced ' : 'ERROR: Unable to replace ';
        $message.= '`' . $search . '` with `' . $replace . '`.';
      } else {
        $sql = 'SELECT ID,store_name,zipcode,receive_emails FROM ' . $wpdb->prefix . 'donman_contacts WHERE email_address="%s"';
        $wpdb->get_row( $wpdb->prepare( $sql, $search ) );
      }

      $response->output = '<pre>$search = '.$search.'<br />$replace = '.$replace.'<br />$wpdb->last_result = ' . print_r( $wpdb->last_result, true ) . '<br />$wpdb->num_rows = ' . $wpdb->num_rows . '<br />$wpdb->last_query = ' . $wpdb->last_query . $message . '</pre>';
      break;

    case 'subscribe':
    case 'unsubscribe':
      $email = $_POST['email'];

      if ( ! is_email( $email ) || empty( $email ) )
        $response->output = '<pre>ERROR: Not a valid email!</pre>';

      $rows_affected = self::update_email( $email, $cb_action );
      $response->action = $cb_action;
      $response->output = '<pre>$email = ' . $email . '<br />' . $rows_affected . ' contacts ' . $cb_action . 'd.</pre>';
      break;

    case 'update_contact':
      $id = $_POST['ID'];
      $field = $_POST['field'];
      $value = $_POST['value'];
      $rows_affected = $this->update_contact( $id, $field, $value );
      $response->ID = $id;
      $response->field = $field;
      $response->value = $value;
      break;

    default:
      $posted_radius = $_POST['radius'];
      $orphaned_pickup_radius = get_field( 'orphaned_pickup_radius', 'option' );
      $radius = ( is_numeric( $posted_radius ) )? $posted_radius : $orphaned_pickup_radius;
      if( empty( $radius ) )
        $radius = 15; // Ensure radius is set
      $priority = $_POST['priority'];
      $contacts = DonationManager\orphanedproviders\get_orphaned_donation_contacts( array( 'pcode' => $pcode, 'radius' => $radius, 'priority' => $priority, 'fields' => 'store_name,email_address,zipcode,priority,show_in_results' ) );
      $orphaned_donation_routing = get_option( 'donation_settings_orphaned_donation_routing' );
      //$response->output = ( ! is_wp_error( $contacts ) )? '<pre>$orphaned_donation_routing = ' . $orphaned_donation_routing . '<br />Results for `' . $pcode . '` within a ' . $radius . ' mile radius.<br />' . count( $contacts ) . ' result(s):<br />'.print_r( $contacts, true ).'</pre>' : $contacts->get_error_message();
      $x = 0;
      $table_rows = '';
      if( is_array( $contacts ) ){
        foreach( $contacts as $contact ){
          $show_in_results = '<input type="checkbox" class="show-in-results" value="1" name="show_in_results[' . $contact['ID']. ']" ' . checked( $contact['show_in_results'], 1, false ) . ' />';
          $class = ( $x % 2 )? 'alternate' : '';
          $table_rows.= '<tr class="' . $class . '" id="contact_' . $contact['ID'] . '" data-id="' . $contact['ID'] . '"><td>' . $contact['ID'] . '</td><td><a href="' . $contact['by-pass-link'] . '" target="_blank">' . $contact['store_name'] . '</a></td><td>' . $contact['email_address'] . '</td><td>' . $contact['zipcode'] . '</td><td>' . $contact['priority'] . '</td><td>' . $show_in_results . '</td><td><a href="#" class="delete-contact button button-small">Delete</a></td></tr>';
          $x++;
        }
      }
      $table = '<table class="widefat"><thead><tr><td>ID</td><td>Store Name <span style="color: #090; font-size: .9em">(Click store name to open by-pass link)</span></td><td>Email</td><td>Zip Code</td><td>Priority</td><td>Show</td><td>&nbsp;</td></tr></thead><tbody>'.$table_rows.'</tbody></table>';
      $response->output = $table;
      break;
    }


    wp_send_json( $response );
  }
}
?>