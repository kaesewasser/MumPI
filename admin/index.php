<?php
/**
 * Mumble PHP Interface by Kissaki
 * Released under Creative Commons Attribution-Noncommercial License
 * http://creativecommons.org/licenses/by-nc/3.0/
 * @author Kissaki
 */

define('MUMPHPI_MAINDIR', '..');
define('MUMPHPI_SECTION', 'admin');

	// Start timer for execution time of script first
	require_once(MUMPHPI_MAINDIR.'/classes/PHPStats.php');
	PHPStats::scriptExecTimeStart();
	
	require_once(MUMPHPI_MAINDIR.'/classes/SettingsManager.php');
	require_once(MUMPHPI_MAINDIR.'/classes/DBManager.php');
	require_once(MUMPHPI_MAINDIR.'/classes/Logger.php');
	require_once(MUMPHPI_MAINDIR.'/classes/SessionManager.php');
	SessionManager::startSession();
	
	require_once(MUMPHPI_MAINDIR.'/classes/TranslationManager.php');
	require_once(MUMPHPI_MAINDIR.'/classes/ServerInterface.php');
	require_once(MUMPHPI_MAINDIR.'/classes/HelperFunctions.php');
	require_once(MUMPHPI_MAINDIR.'/classes/TemplateManager.php');
	require_once(MUMPHPI_MAINDIR.'/classes/MessageManager.php');
	
	if(SettingsManager::getInstance()->isDebugMode())
		error_reporting(E_ALL);
	
	// Check for running Ice with Murmur
	try{
		ServerInterface::getInstance();
	}catch(Ice_UnknownLocalException $ex) {
		MessageManager::addError(tr('error_noIce'));
		MessageManager::echoAll();
		exit();
  	}
	
	if(!SessionManager::getInstance()->isAdmin() && HelperFunctions::getActivePage()!='login'){
		header('Location: ?page=login');
		exit();
	}elseif(SessionManager::getInstance()->isAdmin() && isset($_GET['ajax'])){
		require_once(MUMPHPI_MAINDIR.'/ajax/admin.ajax.php');
		exit();
	}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	
	<title><?php echo SettingsManager::getInstance()->getSiteTitle().' - '.HelperFunctions::getActivePage(); ?></title>
	<meta name="description" content="<?php echo SettingsManager::getInstance()->getSiteDescription(); ?>" />
	<meta name="keywords" content="<?php echo SettingsManager::getInstance()->getSiteKeywords(); ?>" />
	
	<?php TemplateManager::parseTemplate('HTMLHead');; ?>
</head>
<body>

<?php
	$pageSection = 'meta';
	if( isset($_GET['page']) && !empty($_GET['page']) )
		$pageSection = $_GET['page'];
	
	// Parse Template
	TemplateManager::parseTemplate('header');
	echo '<div id="content">';
	TemplateManager::parseTemplate($pageSection);
	echo '</div>';
	TemplateManager::parseTemplate('footer');
	
?>
</body></html>
