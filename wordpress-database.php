<?php
/****
Plugin Name:Wordpress Database
Plugin URI:
Author: Milton
Author URI:
Description: Our 2019 default theme is designed to show off the power of the block editor. It features custom styles for all the default blocks, and is built so that what you see in the editor looks like what you'll see on your website. Twenty Nineteen is designed to be adaptable to a wide range of websites, whether you’re running a photo blog, launching a new business, or supporting a non-profit. Featuring ample whitespace and modern sans-serif headlines paired with classic serif body text, it's built to be beautiful on all screen sizes.
Version: 1.0
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: 
******/

define("PLUGIN_VERSION",'1.1');
require_once("UsersClassTable.php");
function create_custom_database_table(){
    global $wpdb;
    $table_name=$wpdb->prefix.'persons';
    $sql="CREATE TABLE {$table_name} (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(250),
        email VARCHAR(230),
        PRIMARY KEY (id)
    );";

    require_once (ABSPATH."wp-admin/includes/upgrade.php");
    dbDelta($sql);

    add_option('Demo_Plugin_Version',PLUGIN_VERSION);

    if(get_option("Demo_Plugin_Version") !=PLUGIN_VERSION){
        $sql="CREATE TABLE {$table_name} (
            id INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(250),
            email VARCHAR(230),
            age INT,
            PRIMARY KEY (id)
        );";
        dbDelta($sql);
        update_option('Demo_Plugin_Version',PLUGIN_VERSION);
    }
    

}
register_activation_hook( __FILE__,'create_custom_database_table');


function insert_demo_data(){
    global $wpdb;
    $table_name=$wpdb->prefix.'persons';
    $wpdb->insert($table_name,[
        'name' =>'Milton',
        'email'=>'milton123@gmail.com'
    ]);
    
    $wpdb->insert($table_name,[
    'name' =>'Tayaba',
    'email'=>'tayaba123@gmail.com'
    ]);
    
}

register_activation_hook( __FILE__,'insert_demo_data');

function flush_demo_data(){
    global $wpdb;
    $table_name=$wpdb->prefix.'persons';
    $query="TRUNCATE TABLE {$table_name}";
    $wpdb->query($query);
    
}

register_deactivation_hook(__FILE__,'flush_demo_data');

function add_menu_demo_data(){
    add_menu_page( 'Demo Database', 'Demo Database', 'manage_options', 'demo_database','load_database_data' );
}
function load_database_data(){ 
    global $wpdb;
    if(isset($_GET['pid'])){
        if(!isset($_GET['pid']) || ! wp_verify_nonce($_GET['n'],'dbdemo_edit')){
            wp_die("You are not allowed");
        }
        if(isset($_GET['action']) && $_GET['action']=='delete'){
            $pid=sanitize_key($_GET['pid']);
            $wpdb->delete("{$wpdb->prefix}persons",['id'=>$pid]);
            $_GET['pid']=null;

        }
    }
    ?>
    <h2>Database Data</h2>
    <?php 
    $pid=$_GET['pid'] ??0;
    $pid=sanitize_key($pid);
    if($pid){
        $result=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}persons WHERE id='{$pid}'");
    }
    ?>
    <div class="form">
        <form action="<?php echo admin_url("admin-post.php"); ?>" method="POST">
            <?php 
                wp_nonce_field("database","nonce");
            ?>
            <input type="hidden" name="action" value="database-add-record">
            <div><span>Name:</span> <input type="text" name="name" value="<?php if ($pid) echo $result->name; ?>"></div>
            <div><span>Email:</span> <input type="text" name="email" value="<?php if ($pid) echo $result->email; ?>"></div>

            <?php if($pid){
                ?>
                <input type="hidden" name="id" value="<?php echo $pid; ?>"id="">
                <?php
                submit_button("Update Record"); 
            } else{ 
                submit_button("Add Record");
            } ?>
        </form>
    </div>
    <div class="data-table">
        <div class="form-header">
            <h2>Users</h2>
        </div>
        <div class="form-box-content">
            <?php 
                global $wpdb;
                $users=$wpdb->get_results("SELECT id,name,email FROM {$wpdb->prefix}persons",ARRAY_A);
                $user_table= new UserClassTable($users);
                $user_table->prepare_items();
                $user_table->display();
            ?>
        </div>
    </div>
    <?php 
    
        // if(isset($_POST['submit'])){
        //     $nonce=sanitize_text_field($_POST['nonce']);
        //     if(wp_verify_nonce($nonce,'database')){
        //         $name=sanitize_text_field($_POST['name']);
        //         $email=sanitize_email($_POST['email']);

        //         $wpdb->insert("{$wpdb->prefix}persons",[
        //         'name' =>$name,
        //         'email'=>$email
        //         ]);
        //     }else{
        //         echo "You are not alowed to insert data";
        //     }
        // }
    
    ?>
<?php }
add_action('admin_menu','add_menu_demo_data');

function add_record_database(){
    global $wpdb;
    if(isset($_POST['submit'])){
        $nonce=sanitize_text_field($_POST['nonce']);
        if(wp_verify_nonce($nonce,'database')){
            $name=sanitize_text_field($_POST['name']);
            $email=sanitize_email($_POST['email']);
            $id=sanitize_key($_POST['id']);
            if($id){
                $wpdb->update("{$wpdb->prefix}persons",[
                    'name' =>$name,
                    'email'=>$email],
                    ['id'=>$id]
                );
                $nonce = wp_create_nonce( "dbdemo_edit" );
                wp_redirect(admin_url('admin.php?page=demo_database&pid=').$id ."&n={$nonce}");
            }else{
                $wpdb->insert("{$wpdb->prefix}persons",[
                    'name' =>$name,
                    'email'=>$email
                    ]);
                wp_redirect(admin_url("admin.php?page=demo_database"));
            }
            
            
        }
        
    }
}
add_action("admin_post_database-add-record","add_record_database");

function admin_scripts_loaded($hook){
    if("toplevel_page_demo_database"==$hook){
        wp_enqueue_style('database-style',plugin_dir_url(__FILE__).'assets/plugin-style.css');
    }
}
add_action('admin_enqueue_scripts','admin_scripts_loaded');