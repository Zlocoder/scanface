<?php

/**
 * This file contains information related to a help section 
 * of the plugin. Each directory is a specific language
 *
 * @package SZGoogle
 * @subpackage SZGoogleAdmin
 * @author Massimo Della Rovere
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

if (!defined('SZ_PLUGIN_GOOGLE') or !SZ_PLUGIN_GOOGLE) die(); 

// Variable definition HTML for the preparation of the
// string which contains the documentation of this feature

$HTML = <<<EOD

<h2>Descripción</h2>

<p>The <b>SZ-Google</b> plugin pdes functions to automatically insert the code selector language in its theme, but if you want 
to use for a particular need entering the code manually and use the admin panel for the configuration parameters, you can use the 
PHP function of the plugin and implement them with your code. These are the available functions:</p>

<ul>
<li>szgoogle_translate_get_code()</li>
<li>szgoogle_translate_get_meta()</li>
<li>szgoogle_translate_get_meta_ID()</li>
</ul>

<p>For example if we wanted to enter the code into our theme and take only the options that relate to your account, we could use 
a PHP code similar to the following where function used is <b>szgoogle_translate_get_meta_ID()</b>.</p>

<pre>&lt;meta name="google-translate-customization" content="&lt;?php echo szgoogle_translate_get_meta_ID() ?&gt"/&gt;</pre>

<p>If you want to insert the complete code automatically generated by the plugin in a defined position of our theme, we can use 
the PHP function <b>szgoogle_translate_get_meta()</b> and insert it in the exact spot we want.</p>

<pre>
&lt;head&gt;
  if (function_exists('szgoogle_translate_get_meta')) {
    echo szgoogle_translate_get_meta();
  }
&lt;/head&gt;
</pre>

<h2>Advertencias</h2>

<p>El plugin <b>SZ-Google</b> se ha desarrollado con una técnica de módulos de carga individuales para optimizar el rendimiento general,
así que antes de utilizar un shortcode, un widget o una función PHP debe comprobar que aparece el módulo general y la opción específica
permitido a través de la opción que se encuentra en el panel de administración.</p>

EOD;

// Call function for creating the page of standard
// documentation based on the contents of the HTML variable

$this->moduleCommonFormHelp(__('translate PHP functions','szgoogleadmin'),NULL,NULL,false,$HTML,basename(__FILE__));