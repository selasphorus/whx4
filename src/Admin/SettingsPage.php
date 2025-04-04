<?php

namespace atc\WHx4\Admin;

class SettingsPage {

	private $db;
    private $api;

    public function __construct( DatabaseManager $db ) { //public function __construct(DatabaseManager $db, MailchimpAPI $api) {
        $this->db = $db;  
        //$this->api = $api;  
        add_action('admin_menu', [$this, 'add_menu']);  
    }

    public function add_menu() {
        add_menu_page('WHx4', 'WHx4', 'manage_options', 'whx4', [$this, 'render_page']);  
    }
	
     public function render_page() { //public function render() {
        echo '<h1>WHx4 Plugin Settings</h1>';
    }
    
}

/*
Usage:

use WHx4\Admin\SettingsPage;

$settings = new SettingsPage();
$settings->render();
?>

?>