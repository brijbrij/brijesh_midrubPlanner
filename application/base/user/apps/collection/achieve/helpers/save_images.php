<?php
/**
 * Save Images Helpers
 *
 * This file contains the class Save_images
 * with methods to download images from url
 *
 * @author Scrisoft
 * @package Midrub
 * @since 0.0.7.6
 */

// Define the page namespace
namespace MidrubBase\User\Apps\Collection\Achieve\Helpers;

defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Save_images class provides the methods to download images from url
 * 
 * @author Scrisoft
 * @package Midrub
 * @since 0.0.7.6
*/
class Save_images {
    
    /**
     * Class variables
     *
     * @since 0.0.7.6
     */
    protected $CI;

    /**
     * Initialise the Class
     *
     * @since 0.0.7.6
     */
    public function __construct() {
        
        // Get codeigniter object instance
        $this->CI =& get_instance();
        
    }
    
    /**
     * The public method download_images_from_urls downloads images from url
     * 
     * @since 0.0.7.6
     * 
     * @return void
     */ 
    public function download_images_from_urls() {
        
        // Check if data was submitted
        if ( $this->CI->input->post() && get_option('app_achieve_enable_url_download') ) {
            
            // Load Media Model
            $this->CI->load->model('media');
            
            // Add form validation
            $this->CI->form_validation->set_rules('imported_urls', 'Imported Urls', 'trim|required');
            
            // Get data
            $imported_urls = $this->CI->input->post('imported_urls');
            
            if ( $this->CI->form_validation->run() === false ) {
                
                $data = array(
                    'success' => FALSE,
                    'message' => $this->CI->lang->line('error_occurred')
                );

                echo json_encode($data);
                exit();
                
            } else {
                
                // Get all image's urls
                $all_urls = explode("\n", $imported_urls);
                
                // Verify if there is at least one url
                if ( count($all_urls) > 0 ) {
                    
                    $count = 0;
                    
                    $user_achieve = get_user_option('user_achieve', $this->CI->user_id);
                    
                    if ( !$user_achieve ) {
                        $user_achieve = 0;
                    }
                    
                    $total_achieve = 0;
                    
                    // Fedine the medias array
                    $medias = array();
                    
                    // List all urls
                    foreach ( $all_urls as $url ) {
                        
                        // Clear the url
                        $clean_url = strip_tags(trim($url));
                        
                        // Get image's info
                        $img_info = $this->check_if_is_image($clean_url);
                        
                        // Get plan's achieve
                        $plan_achieve = $this->CI->plans->get_plan_features( $this->CI->user_id, 'achieve' );
                        
                        // Verify if url is an image
                        if ( $img_info ) {
                            
                            // Verify if the image has supported format
                            if ( ($img_info[2] > 0) && ($img_info[2] < 4 ) ) {
                                
                                // Get image size
                                $image_size = $this->get_image_size($clean_url);

                                if ( !$image_size ) {
                                    
                                    continue;
                                    
                                }
                
                                // Get temp achieve
                                $temp_achieve = $image_size + $user_achieve;
                                
                                // Verify if user has enough achieve
                                if ( $temp_achieve >= $plan_achieve ) {
                                    
                                    continue;

                                }

                                // Get upload limit
                                $upload_limit = get_option('upload_limit');

                                if ( !$upload_limit ) {

                                    $upload_limit = 6291456;

                                } else {

                                    $upload_limit = $upload_limit * 1048576;

                                }

                                if ( $image_size > $upload_limit ) {
                                    
                                    continue;

                                }
                                
                                // Generate a new file name
                                $file_name = uniqid() . '-' . time();
                                
                                // Generate a thumb name
                                $thumb_name = $file_name . '-cover.png';
                                
                                // Save image
                                file_put_contents(FCPATH . 'assets/share/' . $file_name . '.jpeg', fopen($clean_url, 'r'));

                                // Set read permission
                                chmod(FCPATH . 'assets/share/' . $file_name . '.jpeg', 0644); 
                                
                                // Verify if the image was saved
                                if ( !file_exists(FCPATH . 'assets/share/' . $file_name . '.jpeg') ) {
                                    continue;
                                } else {
                                    $img_url = base_url() . 'assets/share/' . $file_name . '.jpeg';
                                }
                                
                                // Generate thumbnail
                                $thumb = $this->generate_thumbnail(FCPATH . 'assets/share/' . $file_name . '.jpeg', 250, 250);

                                if ( $thumb ) {
                                
                                    // Save thumbnail
                                    imagepng($thumb, FCPATH . 'assets/share/' . $thumb_name);
                                
                                }
                                
                                // Verify if the image was saved
                                if ( !file_exists(FCPATH . 'assets/share/' . $file_name . '.png') ) {
                                    $thumb_path = base_url() . 'assets/share/' . $file_name . '.jpeg';
                                } else {
                                    // Set read permission
                                    chmod(FCPATH . 'assets/share/' . $file_name . '-cover.png', 0644);
                                    $thumb_path = base_url() . 'assets/share/' . $file_name . '-cover.png';
                                }
                                
                                // Save uploaded file data
                                $last_id = $this->CI->media->save_media($this->CI->user_id,  $img_url, 'image', $thumb_path, $image_size);
                                
                                if ( $last_id ) {
                                
                                    $count++;
                                    $total_achieve = $temp_achieve;
                                    
                                    // Update the user achieve
                                    update_user_option( $this->CI->user_id, 'user_achieve', $total_achieve );
                                    
                                }
                                
                            }
                            
                        }
                        
                    }
                    
                    if ( $count ) {
                        
                        $data = array(
                            'success' => TRUE,
                            'message' => $count . ' ' . $this->CI->lang->line('images_were_saved_successfully'),
                            'user_achieve' => calculate_size($total_achieve)
                        );

                        echo json_encode($data);
                        exit();                        
                        
                    } else {
                        
                        $data = array(
                            'success' => FALSE,
                            'message' => $count . ' ' . $this->CI->lang->line('images_were_saved_successfully')
                        );

                        echo json_encode($data);
                        exit();
                        
                    }
                    
                }
                
            }
            
        }
        
        $data = array(
            'success' => FALSE,
            'message' => $this->CI->lang->line('error_occurred')
        );

        echo json_encode($data);  
        
    }
    
    /**
     * The public method generate_thumbnail generates an image thumbnail
     * 
     * @param string $image contains the image's path
     * @param integer $w contains the thumbnail width
     * @param integer $h contains the image's height
     * 
     * @since 0.0.7.6
     * 
     * @return array with image's information or false
     */ 
    public function generate_thumbnail($image, $w, $h) {
        
        try {
         
            // Get imagge information
            list($width, $height) = getimagesize($image);

            $r = $width / $height;

            if ( $w/$h > $r ) {

                $newwidth = $h*$r;
                $newheight = $h;

            } else {

                $newheight = $w/$r;
                $newwidth = $w;

            }

            $extension = explode( '.', $image );

            $ext = end($extension);

            $src = imagecreatefromjpeg($image);

            $idnt = imagecreatetruecolor($newwidth, $newheight);

            imagecopyresampled($idnt, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            return $idnt;
            
        } catch (Exception $ex) {
            
            return false;

        }
        
    }
    
    /**
     * The public method check_if_is_image verifies if url is an image
     * 
     * @param string $url contains the image's url
     * 
     * @since 0.0.7.6
     * 
     * @return array with image's information or false
     */ 
    public function check_if_is_image($url) {
        
        $img_info = getimagesize($url);
        
        if ( @is_array($img_info ) ) {

            return $img_info;

        } else {

            return false;

        }
        
    }
    
    /**
     * The public method get_image_size gets the image's size
     * 
     * @param string $url contains the image's url
     * 
     * @since 0.0.7.6
     * 
     * @return integer with image size or false
     */ 
    public function get_image_size($url) {
        
        // Get real image's size
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HEADER, TRUE);
        curl_setopt($curl, CURLOPT_NOBODY, TRUE);
        $data = curl_exec($curl);
        $size = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($curl);

        // Verify if the size is real
        if ( !is_numeric($size) ) {

            return false;

        } else {
            
            return $size;
            
        }
        
    }    

}

