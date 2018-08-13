<?php

/*
 * Description : PHP CLI to Link Taxonomies To WP Posts
 * Parameters  --id post_id --tm term --tx taxonomy
 */

// Established Connection
// require '/home/admin/wwwroot/adpost.com/wp/variables.php'; // Variables file
require 'Connection.php';
$shortopts = "t:";
$longopts = array("id:", "tm:", "tx:");
$parameters = getopt($shortopts, $longopts);

$image_post_id = $parameters['id'];
$term = $parameters['tm'];
$taxonomy = $parameters['tx'];

// Get term taxonomy 
    $stmt_get_term_id = $conn->prepare("SELECT term_taxonomy_id FROM `wp_term_taxonomy` join wp_terms on wp_term_taxonomy.term_id = wp_terms.term_id Where taxonomy = ? and slug= ?");
    $stmt_get_term_id->bind_param("ss", $taxonomy, $term);
    $stmt_get_term_id->execute();
    $stmt_get_term_id->bind_result($term_id);
    $stmt_get_term_id->fetch();
    $stmt_get_term_id->close();

    // insert in wp_term_relationships
    $stmt_terms = $conn->prepare("INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (?, ?)");
    $stmt_terms->bind_param("ss", $image_post_id, $term_id);
    $status = $stmt_terms->execute();
    $stmt_terms->close();

//
    // Update count of posts has terms
    if($status == 1){
        $stmt_update_count = $conn->prepare("UPDATE wp_term_taxonomy SET count = count+1 WHERE term_taxonomy_id = ?");
        $stmt_update_count->bind_param("s", $term_id);
        $stmt_update_count->execute();
        $stmt_update_count->close();
    }
    

?>
