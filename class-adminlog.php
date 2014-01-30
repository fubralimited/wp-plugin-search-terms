<?php

/**
 * WP_List_Table class extension
 *
 * Documentation:  http://codex.wordpress.org/Class_Reference/WP_List_Table
 **/

// Main class
if(!class_exists('WP_List_Table'))
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class Keyword_WP_List_Table extends WP_List_Table {

    private $order;
    private $orderby;
    private $keywords_per_page = 15;

    public function __construct() {

        parent :: __construct(
            array(
                'singular' => 'keyword',
                'plural'   => 'keywords',
                'ajax'     => true
            )
        );

        // Call functions
        $this->set_order();
        $this->set_orderby();
        $this->prepare_items();
        $this->display();

    }

    /**
    * Query the DB for the keywords
    **/
    private function get_keywords() {
        
        global $wpdb;

        $sql_results = $wpdb->get_results('
                    SELECT * FROM '.$wpdb->prefix.'search_terms
                    ORDER BY '.$this->orderby.' '.$this->order.' LIMIT 100'
                );

        return $sql_results;
    }

    /**
    * Gets and sets the order in which the data is displayed
    **/
    public function set_order() {

        $order = 'DESC';

        if ( isset($_GET['order']) AND $_GET['order'] )
            $order = $_GET['order'];

        $this->order = esc_sql( $order );

    }

    /**
    * Gets and sets the field on which the data is sorted
    **/
    public function set_orderby() {

        $orderby = 'id';

        if ( isset( $_GET['orderby'] ) AND $_GET['orderby'] )
            $orderby = $_GET['orderby'];

        $this->orderby = esc_sql( $orderby );

    }

    /**
     * Set the permissions to whom can access the log
     *
     * @see WP_List_Table::ajax_user_can()
     **/
    public function ajax_user_can() {

        return current_user_can( 'edit_posts' );

    }

    /**
     * Sets the message to display when nothing has been found
     *
     * @see WP_List_Table::no_items()
     **/
    public function no_items() {

        _e( 'No keywords found.' );

    }

    /**
     * Set the columns and it's titles for display 
     *
     * @see WP_List_Table::get_columns()
     **/
    public function get_columns() {

        $columns = array(
            'id'         => __('ID'),
            'keyword'    => __('Keyword'),
            'browser'    => __('Browser'),
            'results'    => __('Results'),
            'location'   => __('Location'),
            'date'       => __('Date')
        );

        return $columns;

    }

    /**
     * Set which column can be sortable
     *
     * @see WP_List_Table::get_sortable_columns()
     **/
    public function get_sortable_columns() {

        $sortable = array(
            'id'         => array( 'id', true ),
            'keyword'    => array( 'keyword', true ),
            'results'    => array( 'results', true )
        );

        return $sortable;

    }

    /**
     * Prepare data for display
     *
     * @see WP_List_Table::prepare_items()
     **/
    public function prepare_items() {

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( 
            $columns,
            $hidden,
            $sortable 
        );

        // Get the keywords data
        $keywords = $this->get_keywords();
        empty( $keywords ) AND $keywords = array();

        // Set the pagination
        $per_page     = $this->keywords_per_page;
        $current_page = $this->get_pagenum();
        $total_items  = count( $keywords );

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            )
        );

        $last_word = $current_page * $per_page;
        $first_word = $last_word - $per_page + 1;
        $last_word > $total_items AND $last_word = $total_items;

        // Setup the range of keys/indizes that contain 
        // the posts on the currently displayed page(d).
        // Flip keys with values as the range outputs the range in the values.
        $range = array_flip( range( $first_word - 1, $last_word - 1, 1 ) );

        // Store the keywords that we're not displaying
        $words_array = array_intersect_key( $keywords, $range );

        // Serve the data
        $this->items = $words_array;

    }

    /**
     * Default column needed to render the data inside the table
     *
     * @param $item
     * @param $column_name
     **/
    public function column_default($item, $column_name) {

        return $item->$column_name;

    }

    /**
     * Override the navigation bar to avoid breaking and according nonce field
     *
     * @param $which
     **/
    public function display_tablenav($which) {
            
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <!-- Export to CSV form & button -->
            <form id="export" action="" method="get">
                <div class="alignleft actions">
                    <input type="submit" name id="docsv" class="button action" value="Export to CSV">
                    <input type="hidden" name="page" value="report_menu">
                    <input type="hidden" name="csv" value="do">
                </div>
            </form>

            <?php
            $this->extra_tablenav( $which );
            $this->pagination( $which );
            ?>

            <br class="clear" />

        </div>
        <?php

    }

}