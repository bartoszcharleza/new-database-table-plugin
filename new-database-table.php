<?php

/*
  Plugin Name: Pet Adoption (New DB Table)
  Version: 1.0
  Author: Bartek
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
require_once plugin_dir_path(__FILE__) . 'inc/generatePet.php';

define( 'NEWDATABASETABLEPATH', plugin_dir_path( __FILE__ ));

class PetAdoptionTablePlugin {
  function __construct() {
    global $wpdb;
    $this->charset = $wpdb->get_charset_collate();
    $this->tablename = $wpdb->prefix . "pets";

    // Register activation hook to create the database table
    add_action('activate_new-database-table/new-database-table.php', array($this, 'onActivate'));

    // Register actions for creating and deleting pets
    add_action('admin_post_createpet', array($this, 'createPet'));
    add_action('admin_post_nopriv_createpet', array($this, 'createPet'));
    add_action('admin_post_deletepet', array($this, 'deletePet'));
    add_action('admin_post_nopriv_deletepet', array($this, 'deletePet'));

    // Register asset loading for front-end
    add_action('wp_enqueue_scripts', array($this, 'loadAssets'));
  }

  // Delete a pet
  function deletePet() {
    if (current_user_can('administrator')) {
      $id = sanitize_text_field($_POST['idtodelete']);
      global $wpdb;
      $wpdb->delete($this->tablename, array('id' => $id));
      wp_safe_redirect(site_url('/pet-adoption'));
    } else {
      wp_safe_redirect(site_url());
    }
    exit;
  }

  // Create a pet
  function createPet() {
    if (current_user_can('administrator')) {
      $pet = generatePet();
      $pet['petname'] = sanitize_text_field($_POST['incomingpetname']);
      global $wpdb;
      $wpdb->insert($this->tablename, $pet);
      wp_safe_redirect(site_url('/pet-adoption'));
    } else {
      wp_safe_redirect(site_url());
    }
    exit;
  }

  // Create the database table on plugin activation
  function onActivate() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta("CREATE TABLE $this->tablename (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      birthyear smallint(5) NOT NULL DEFAULT 0,
      petweight smallint(5) NOT NULL DEFAULT 0,
      favfood varchar(60) NOT NULL DEFAULT '',
      favhobby varchar(60) NOT NULL DEFAULT '',
      favcolor varchar(60) NOT NULL DEFAULT '',
      petname varchar(60) NOT NULL DEFAULT '',
      species varchar(60) NOT NULL DEFAULT '',
      PRIMARY KEY  (id)
    ) $this->charset;");
  }

  // Load assets (CSS) for the front-end
  function loadAssets() {
    if (is_page('pet-adoption')) {
      wp_enqueue_style('petadoptioncss', plugin_dir_url(__FILE__) . 'pet-adoption.css');
    }
  }

  // Load a custom template for the 'pet-adoption' page
  function loadTemplate($template) {
    if (is_page('pet-adoption')) {
      return plugin_dir_path(__FILE__) . 'inc/template-pets.php';
    }
    return $template;
  }

  // Populate the database with pet data (used for testing)
  function populateFast() {
    $query = "INSERT INTO $this->tablename (`species`, `birthyear`, `petweight`, `favfood`, `favhobby`, `favcolor`, `petname`) VALUES ";
    $numberofpets = 100000;
    for ($i = 0; $i < $numberofpets; $i++) {
      $pet = generatePet();
      $query .= "('{$pet['species']}', {$pet['birthyear']}, {$pet['petweight']}, '{$pet['favfood']}', '{$pet['favhobby']}', '{$pet['favcolor']}', '{$pet['petname']}')";
      if ($i != $numberofpets - 1) {
        $query .= ", ";
      }
    }

    global $wpdb;
    $wpdb->query($query);
  }
}

$petAdoptionTablePlugin = new PetAdoptionTablePlugin();

// Custom block registration
class OurPluginPlaceholderBlock {
  function __construct($name) {
    $this->name = $name;
    add_action('init', [$this, 'onInit']);
  }

  function ourRenderCallback($attributes, $content) {
    ob_start();
    require plugin_dir_path(__FILE__) . 'our-blocks/' . $this->name . '.php';
    return ob_get_clean();
  }

  function onInit() {
    wp_register_script($this->name, plugin_dir_url(__FILE__) . "/our-blocks/{$this->name}.js", array('wp-blocks', 'wp-editor'));
    
    register_block_type("ourdatabaseplugin/{$this->name}", array(
      'editor_script' => $this->name,
      'render_callback' => [$this, 'ourRenderCallback']
    ));
  }
}

new OurPluginPlaceholderBlock("petslist");
