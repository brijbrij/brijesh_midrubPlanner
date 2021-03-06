<?php
/**
 * Members Inc
 *
 * PHP Version 7.2
 *
 * This files contains the hooks for
 * the Team's component from the user Panel
 *
 * @category Social
 * @package  Midrub
 * @author   Scrisoft <asksyn@gmail.com>
 * @license  https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License
 * @link     https://www.midrub.com/
 */

 // Define the constants
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The public set_member_permissions registers the team's permissions
 * 
 * @since 0.0.7.9
 */
set_member_permissions(

    array(
        'name' => $this->lang->line('planner'),
        'icon' => '<i class="icon-calendar"></i>',
        'slug' => 'planner',
        'fields' => array(

            array (
                'type' => 'checkbox_input',
                'slug' => 'planner',
                'label' => $this->lang->line('planner_allow'),
                'label_description' => $this->lang->line('planner_allow_if_enabled')
            )

        )

    )
    
);