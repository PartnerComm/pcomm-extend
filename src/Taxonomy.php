<?php

namespace PComm\extend;

class Taxonomy {
    public $tax;
    public $options = [];
    public $labels = [];
    public $supports = [];

    /**
     * Creates a new Taxonomy object
     * @param string $tax
     * @param array $options
     * @param array $labels
     * @param  array $supports optional array of post types
     */
    public function __construct($tax, $options = [], $labels = [], $supports = []) {
        $this->tax = $tax;

        $default_options = [
            'hierarchical' => true,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => $this->tax )
        ];
        $required_labels = [
            'singular_name' => ucwords($this->tax),
            'plural_name' => ucwords($this->tax)
        ];

        $this->options = $options + $default_options;
        $this->labels = $labels + $required_labels;
        $this->options['labels'] = $labels + $this->default_labels();

        $this->supports = $supports;

        add_action('init', array($this, 'register'));

        add_action( 'admin_enqueue_scripts', array ( $this, 'load_wp_media_files' ) );
        add_action( $this->tax . '_add_form_fields', array ( $this, 'add_taxonomy_image' ), 10, 2 );
        add_action( 'created_' . $this->tax, array ( $this, 'save_taxonomy_image' ), 10, 2 );
        add_action( $this->tax . '_edit_form_fields', array ( $this, 'update_taxonomy_image' ), 10, 2 );
        add_action( 'edited_' . $this->tax, array ( $this, 'updated_taxonomy_image' ), 10, 2 );
        add_action( 'admin_footer', array ( $this, 'add_script' ) );

    }

    /**
     * Registers the taxonomy using WP core function(s)
     * @return null
     */
    public function register() {
        register_taxonomy($this->tax, $this->supports, $this->options);
    }

    /**
     * Creates intelligent default labels from the required singular and plural labels
     * @return array
     */
    public function default_labels() {

        return [
            'name' => _x( $this->labels['plural_name'], 'taxonomy general name' ),
            'singular_name' => _x( $this->labels['plural_name'], 'taxonomy singular name' ),
            'search_items' =>  __( 'Search ' . $this->labels['plural_name'] ),
            'all_items' => __( 'All ' . $this->labels['plural_name'] ),
            'parent_item' => __( 'Parent ' . $this->labels['singular_name'] ),
            'parent_item_colon' => __( 'Parent ' . $this->labels['singular_name'] . ':'),
            'edit_item' => __( 'Edit ' . $this->labels['singular_name'] ),
            'update_item' => __( 'Update ' . $this->labels['singular_name'] ),
            'add_new_item' => __( 'Add New ' . $this->labels['singular_name'] ),
            'new_item_name' => __( 'New ' . $this->labels['singular_name'] ),
            'menu_name' => __( $this->labels['plural_name'] ),
        ];

    }

    /*
    Loads the WP media API Javascript
    */
    function load_wp_media_files() {
        wp_enqueue_media();
    }

    /*
      * Adds a form field for an image in the new taxonomy page
     */
    public function add_taxonomy_image ( $taxonomy ) { ?>
        <div class="form-field term-group">
            <label for="category-image-id">Image</label>
            <input type="hidden" id="taxonomy-image-id" name="taxonomy-image-id" class="custom_media_url" value="">
            <div id="taxonomy-image-wrapper"></div>
            <p>
                <input type="button" class="button button-secondary pc_tax_media_button" id="tax_media_button" name="tax_media_button" value="Add Image" />
                <input type="button" class="button button-secondary pc_tax_media_remove" id="tax_media_remove" name="tax_media_remove" value="Remove Image" />
            </p>
        </div>
        <?php
    }

    /*
     * Saves the form field
    */
    public function save_taxonomy_image ( $term_id, $tt_id ) {
        if( isset( $_POST['taxonomy-image-id'] ) && '' !== $_POST['taxonomy-image-id'] ){
            $image = $_POST['taxonomy-image-id'];
            $term = get_term($term_id);
            $term_slug = $term->slug;
            $taxonomy = $term->taxonomy;
            add_term_meta( $term_id, $term_slug . '-' . $taxonomy . '-image-id', $image, true );
        }
    }

    /*
     * Edit the form field
    */
    public function update_taxonomy_image ( $term, $taxonomy ) { ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="taxonomy-image-id">Image</label>
            </th>
            <td>
                <?php $image_id = get_term_meta ( $term -> term_id, 'taxonomy-image-id', true ); ?>
                <input type="hidden" id="taxonomy-image-id" name="taxonomy-image-id" value="<?php echo $image_id; ?>">
                <div id="taxonomy-image-wrapper">
                    <?php if ( $image_id ) { ?>
                        <?php echo wp_get_attachment_image ( $image_id, 'thumbnail' ); ?>
                    <?php } ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary pc_tax_media_button" id="tax_media_button" name="tax_media_button" value="Add Image" />
                    <input type="button" class="button button-secondary pc_tax_media_remove" id="tax_media_remove" name="tax_media_remove" value="Remove Image" />
                </p>
            </td>
        </tr>
        <?php
    }

    /*
     * Update the form field value
     */
    public function updated_taxonomy_image ( $term_id, $tt_id ) {
        if( isset( $_POST['taxonomy-image-id'] ) && '' !== $_POST['taxonomy-image-id'] ){
            $image = $_POST['taxonomy-image-id'];
            $term = get_term($term_id);
            $term_slug = $term->slug;
            $taxonomy = $term->taxonomy;
            update_term_meta ( $term_id, $term_slug . '-' . $taxonomy . '-image-id', $image );
        } else {
            update_term_meta ( $term_id, $term_slug . '-' . $taxonomy . '-image-id', '' );
        }
    }

    /*
     * Add script to handle attachment adding and removing
     */
    public function add_script() { ?>
        <script>
            jQuery(document).ready( function($) {
                function pc_media_upload(button_class) {
                    console.log(button_class);
                    var _custom_media = true,
                        _orig_send_attachment = wp.media.editor.send.attachment;
                    $('body').on('click', button_class, function(e) {
                        var button_id = '#'+$(this).attr('id');
                        var send_attachment_bkp = wp.media.editor.send.attachment;
                        var button = $(button_id);
                        _custom_media = true;
                        wp.media.editor.send.attachment = function(props, attachment){
                            if ( _custom_media ) {
                                $('#taxonomy-image-id').val(attachment.id);
                                $('#taxonomy-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
                                $('#taxonomy-image-wrapper .custom_media_image').attr('src',attachment.sizes.thumbnail.url).css('display','block');
                            } else {
                                return _orig_send_attachment.apply( button_id, [props, attachment] );
                            }
                        }
                        wp.media.editor.open(button);
                        return false;
                    });
                }
                pc_media_upload('.pc_tax_media_button.button');
                $('body').on('click','.pc_tax_media_remove',function(){
                    $('#taxonomy-image-id').val('');
                    $('#taxonomy-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
                });
                // Thanks: http://stackoverflow.com/questions/15281995/wordpress-create-category-ajax-response
                $(document).ajaxComplete(function(event, xhr, settings) {
                    var queryStringArr = settings.data.split('&');
                    if( $.inArray('action=add-tag', queryStringArr) !== -1 ){
                        var xml = xhr.responseXML;
                        $response = $(xml).find('term_id').text();
                        if($response!=""){
                            // Clear the thumb image
                            $('#taxonomy-image-wrapper').html('');
                        }
                    }
                });
            });
        </script>
    <?php }

}