<?php

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Utilities;

/**
 * Class WPFDWidgetSearch
 */
class WPFDWidgetSearch extends WP_Widget
{

    /**
     * WPFDWidgetSearch constructor.
     */
    public function __construct()
    {
        $widget_ops = array('classname' => 'widget_wpfd_search', 'description' => esc_html__('A search form.', 'wpfd'));
        parent::__construct('wpfd_search', esc_html__('WP File Download Search', 'wpfd'), $widget_ops);
    }

    /**
     * Method display search files
     *
     * @param array $args     Options
     * @param array $instance Instance
     *
     * @return void
     */
    public function widget($args, $instance)
    {
        $widget_title = empty($instance['title']) ? esc_html__('Search', 'wpfd') : $instance['title'];
        $title = apply_filters('widget_title', $widget_title, $instance, $this->id_base);

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allow html
        echo $args['before_widget'];
        if ($title) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allow html
            echo $args['before_title'] . $title . $args['after_title'];
        }
        $shortcode = '[wpfd_search ';

        if (isset($instance['cat_filter']) && intval($instance['cat_filter']) === 1) {
            $shortcode .= 'cat_filter="1" ';
        } else {
            $shortcode .= 'cat_filter="0" ';
        }

        if (isset($instance['tag_filter']) && intval($instance['tag_filter']) === 1) {
            $shortcode .= 'tag_filter="1" ';
        } else {
            $shortcode .= 'tag_filter="0" ';
        }

        if (isset($instance['display_tag']) && in_array($instance['display_tag'], array('searchbox', 'checkbox'))) {
            $shortcode .= 'display_tag="' . esc_html($instance['display_tag']) . '" ';
        }

        if (isset($instance['creation_date']) && intval($instance['creation_date']) === 1) {
            $shortcode .= 'create_filter="1" ';
        } else {
            $shortcode .= 'create_filter="0" ';
        }

        if (isset($instance['update_date']) && intval($instance['update_date']) === 1) {
            $shortcode .= 'update_filter="1" ';
        } else {
            $shortcode .= 'update_filter="0" ';
        }

        if (isset($instance['files_per_page']) && intval($instance['files_per_page']) > 0) {
            $shortcode .= 'files_per_page="' . esc_html($instance['files_per_page']) . '" ';
        }

        $shortcode .= ']';

        echo do_shortcode($shortcode);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allow html
        echo $args['after_widget'];
    }

    /**
     * Method update instance
     *
     * @param array $new_instance Instance to replace
     * @param array $old_instance Old Instance
     *
     * @return array
     */
    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['tag_filter'] = $new_instance['tag_filter'];
        $instance['cat_filter'] = $new_instance['cat_filter'];
        $instance['display_tag'] = $new_instance['display_tag'];
        $instance['creation_date'] = $new_instance['creation_date'];
        $instance['update_date'] = $new_instance['update_date'];
        $instance['files_per_page'] = $new_instance['files_per_page'];
        return $instance;
    }

    /**
     * Method form instance
     *
     * @param array $instance Instance
     *
     * @return string|void
     */
    public function form($instance)
    {
        $instance = wp_parse_args(
            (array)$instance,
            array(
                'title' => '',
                'tag_filter' => 1,
                'display_tag' => 'searchbox',
                'cat_filter' => 1,
                'creation_date' => 1,
                'update_date' => 1,
                'files_per_page' => 15
            )
        );
        $title = esc_attr($instance['title']);

        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'wpfd'); ?></label>
            <input class="widefat" id="<?php esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>"/>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('cat_filter')); ?>">
                <?php esc_html_e('Filter by category', 'wpfd'); ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('cat_filter')); ?>"
                    id="<?php echo esc_attr($this->get_field_id('cat_filter')); ?>" class="widefat">
                <option value="1"<?php selected($instance['cat_filter'], '1'); ?>><?php esc_html_e('Yes', 'wpfd'); ?></option>
                <option value="0"<?php selected($instance['cat_filter'], '0'); ?>><?php esc_html_e('No', 'wpfd'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('tag_filter')); ?>"><?php esc_html_e('Filter by tag', 'wpfd'); ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('tag_filter')); ?>"
                    id="<?php echo esc_attr($this->get_field_id('tag_filter')); ?>" class="widefat">
                <option value="1"<?php selected($instance['tag_filter'], '1'); ?>><?php esc_html_e('Yes', 'wpfd'); ?></option>
                <option value="0"<?php selected($instance['tag_filter'], '0'); ?>><?php esc_html_e('No', 'wpfd'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('display_tag')); ?>">
                <?php esc_html_e('Display tag as', 'wpfd'); ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('display_tag')); ?>"
                    id="<?php echo esc_attr($this->get_field_id('display_tag')); ?>" class="widefat">
                <option value="searchbox"<?php selected($instance['display_tag'], 'searchbox'); ?>>
                    <?php esc_html_e('Search box', 'wpfd'); ?></option>
                <option value="checkbox"<?php selected($instance['display_tag'], 'checkbox'); ?>>
                    <?php esc_html_e('Checkbox', 'wpfd'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('creation_date')); ?>">
                <?php esc_html_e('Filter by creation date', 'wpfd'); ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('creation_date')); ?>"
                    id="<?php echo esc_attr($this->get_field_id('creation_date')); ?>" class="widefat">
                <option value="1"<?php selected($instance['creation_date'], '1'); ?>>
                    <?php esc_html_e('Yes', 'wpfd'); ?></option>
                <option value="0"<?php selected($instance['creation_date'], '0'); ?>><?php esc_html_e('No', 'wpfd'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('update_date')); ?>">
                <?php esc_html_e('Filter by update date', 'wpfd'); ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('update_date')); ?>"
                    id="<?php echo esc_attr($this->get_field_id('update_date')); ?>" class="widefat">
                <option value="1"<?php selected($instance['update_date'], '1'); ?>><?php esc_html_e('Yes', 'wpfd'); ?></option>
                <option value="0"<?php selected($instance['update_date'], '0'); ?>><?php esc_html_e('No', 'wpfd'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('files_per_page')); ?>">
                <?php esc_html_e('# Files per page', 'wpfd'); ?></label>
            <select name="<?php echo esc_attr($this->get_field_name('files_per_page')); ?>"
                    id="<?php echo esc_attr($this->get_field_id('files_per_page')); ?>" class="widefat">
                <option value="5"<?php selected($instance['files_per_page'], '5'); ?>>5</option>
                <option value="10"<?php selected($instance['files_per_page'], '10'); ?>>10</option>
                <option value="15"<?php selected($instance['files_per_page'], '15'); ?>>15</option>
                <option value="20"<?php selected($instance['files_per_page'], '20'); ?>>20</option>
                <option value="25"<?php selected($instance['files_per_page'], '25'); ?>>25</option>
                <option value="30"<?php selected($instance['files_per_page'], '30'); ?>>30</option>
                <option value="50"<?php selected($instance['files_per_page'], '50'); ?>>50</option>
                <option value="100"<?php selected($instance['files_per_page'], '100'); ?>>100</option>
                <option value="-1"<?php selected($instance['files_per_page'], '-1'); ?>>All</option>
            </select>
        </p>
        <?php
    }
}

/**
 * Method widgets load
 *
 * @return void
 */
function wpfd_widgets_init()
{
    register_widget('WPFDWidgetSearch');
}

add_action('widgets_init', 'wpfd_widgets_init', 1);
