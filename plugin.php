<?php
if (!defined("IN_ESO")) exit;

/**
 * SuperSticky plugin: Adds a more powerful and respected type of sticky
 * label to your forum.
 */
if (!defined("IN_ESO")) exit;

class SuperSticky extends Plugin {
	
var $id = "SuperSticky";
var $name = "Super Sticky";
var $version = "1.0";
var $description = "Adds a more powerful and respected type of sticky label called 'super sticky'";
var $author = "esoBB team";

function init()
{
	parent::init();
	
	// Language definitions
	$this->eso->addLanguage(array("labels", "superSticky"), "Super sticky");
	$this->eso->addLanguage("Super-sticky", "Super-sticky");
	$this->eso->addLanguage(array("gambits", "super sticky"), "super sticky");
	$this->eso->addLanguageToJS("Super-sticky");
	
	// Add the super sticky label to the esoTalk->labels array.
	$this->eso->addHook("init", array($this, "addSuperStickyLabel"));
	
	// Add the super sticky style to the head.
	$this->eso->addToHead("<style type='text/css'>.superSticky {background:#f00; color:#fff} #gambits a.superText {color:#f00}</style>");

	// If we're on the search view, register the super sticky gambit.
	global $language;
	if ($this->eso->action == "search")
		$this->eso->controller->registerGambit($language["gambits"]["super sticky"], "s3 superText", array($this, "gambitSuperSticky"), 'return $term == $language["gambits"]["super sticky"];');
	
	// If we're on the conversation view...
	if ($this->eso->action == "conversation") {
		// Add hooks to answer to incoming requests to toggle super sticky.
		$this->eso->controller->addHook("init", array($this, "conversationInit"));
		$this->eso->controller->addHook("toggleSticky", array($this, "toggleSticky"));
	}
}

// The super sticky gambit function.
function gambitSuperSticky(&$search, $v, $negate)
{
	$search->condition("conversations", "sticky" . ($negate ? "!" : "") . "=2");
}

// Add the super sticky label to the esoTalk->labels array.
function addSuperStickyLabel(&$esoTalk)
{
	$esoTalk->labels = array("superSticky" => "IF(sticky=2,1,0)") + $esoTalk->labels;
	$esoTalk->labels["sticky"] = "IF(sticky=1,1,0)";
}

// On the conversation view: answer any request to toggle the super sticky of the conversation,
// and add the super sticky link to the bar.
function conversationInit(&$controller)
{
	if ($controller->canSticky() !== true or !$controller->conversation["id"]) return;
	
	$this->eso->addScript("plugins/SuperSticky/superSticky.js", 1000);
	
	global $language;
	if (in_array("sticky", $controller->conversation["labels"])) $k = "Super-sticky"; 
	elseif (in_array("superSticky", $controller->conversation["labels"])) $k = "Unsticky";
	else $k = "Sticky";
	$this->eso->bar["right"][400] = "<a href='" . makeLink($controller->conversation["id"], $controller->conversation["slug"], "?toggleSticky", $controller->startFrom ? "&start=$this->startFrom" : "", "&token={$_SESSION["token"]}") . "' onclick='Conversation.toggleSticky();return false' id='stickyLink'><span class='button buttonSmall'><input type='submit' id='stickyLink' value='" . $language[$k] . "'></span></a>";
}

// Toggle super sticky.
function toggleSticky(&$controller, &$query)
{
	if (!$controller->canSticky()) return false;

	global $config;
	$query = str_replace("sticky=(!sticky)", "sticky=IF(sticky>=2,0,sticky+1)", $query);
}

// Add the column to the database table.
function upgrade($oldVersion)
{
	global $config;
	if (!$oldVersion) {
		$this->eso->db->query("ALTER TABLE {$config["tablePrefix"]}conversations MODIFY COLUMN sticky tinyint(2) NOT NULL default '0'");
	}
}

}

?>
