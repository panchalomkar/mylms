<?php
/**
 * Added services  
 * @package    local_tenant_appearance
 * @author     Sonali B
 * @copyright  Paradiso
 */
$services = array(

    'Local tenant appearance' => array(
  
        'functions' => array('local_tenant_appearance_delete_font'),
  
        'shortname' => 'local_tenant_appearance',
  
        'restrictedusers' => 0,
  
        'enabled' => 1,
  
        'ajax' => true,
  
    )
  
);

$functions = array(

    'local_tenant_appearance_delete_font' => array(
        'classname'     => 'local_tenant_appearance_external',
        'methodname'    => 'delete_font',
        'classpath'     => 'local/tenant_appearance/externallib.php',
        'description'   => 'delete font',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => ''
    ),
);
