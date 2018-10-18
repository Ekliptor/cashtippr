<?php
namespace Ekliptor\Cashtippr;

class UserSettings {
	/** @var \CTIP_TemplateEngine */
	protected $tpl = null;
	
	public function __construct(\CTIP_TemplateEngine $tpl) {
		$this->tpl = $tpl;
		add_action( 'show_user_profile', array( $this, 'addEditorMetaFields' ) );
		add_action( 'edit_user_profile', array( $this, 'addEditorMetaFields' ) );
		add_action( 'personal_options_update', array( $this, 'saveEditorMetaFields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'saveEditorMetaFields' ) );
	}
	
	public function addEditorMetaFields(\WP_User $user) {
    	if (current_user_can('edit_users') === false && get_current_user_id() !== $user->ID)
    		return; // TODO add WP capability for Authors to add their own BCH address?
    	//if (in_array('author', $user->roles) === false) // we would have to check every role (including roles of 3rd party plugins)
    		//return;
    	$this->tpl->showEditorMetaFields($user);
    }
    
    public function saveEditorMetaFields(int $userID) {
    	if (current_user_can('edit_users') === false && get_current_user_id() !== $userID)
    		return;
    	$defaults = $this->getDefaultUserFields();
    	foreach ($defaults as $key => $value) {
    		if (is_bool($value) === true)
    			update_user_meta($userID, $key, isset($_POST[$key])); // update checkbox value
    		else if (isset($_POST[$key]))
    			update_user_meta($userID, $key, sanitize_text_field($_POST[$key]));
    		// TODO add more features of our sanitizer class here if we have more advanced user settings
		}
    }
    
    protected function getDefaultUserFields() {
    	$defaults = array(
    			'author_bch_address' => ''
    	);
    	return $defaults;
    }
}
?>