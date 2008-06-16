#- <?php echo "Access Denied!"; exit(); ?>
#- @package simpleMVC
#- @subpackage config
#- GENERAL CONFIG FOR APPS
#- ADD ANY CONSTANTS YOU MAY REQUIRE HERE

#- DATABASE CONNECTION
DB_CONNECTION  = mysqldb://dbname;dbhost;dbuser;dbpass

#- SOME PATH CONFIGURATION REQUIRED OR USEFULL FOR FURTHER DEVELOPPMENT.
ROOT_URL     = http://example.com/
APP_URL      = %ROOT_URL%/%FRONT_NAME%
APP_DIR      = %ROOT_DIR%/%FRONT_NAME%