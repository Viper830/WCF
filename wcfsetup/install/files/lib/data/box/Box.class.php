<?php
namespace wcf\data\box;
use wcf\data\DatabaseObject;
use wcf\data\menu\Menu;
use wcf\data\menu\MenuCache;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a box.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	data.box
 * @category	Community Framework
 * @since	2.2
 */
class Box extends DatabaseObject {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'box';
	
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableIndexName = 'boxID';
	
	/**
	 * available box types
	 * @var string[]
	 */
	public static $availableBoxTypes = ['text', 'html', 'system', 'menu'];
	
	/**
	 * available box positions
	 * @var string[]
	 */
	public static $availablePositions = ['hero', 'headerBoxes', 'top', 'sidebarLeft', 'contentTop', 'sidebarRight', 'contentBottom', 'bottom', 'footerBoxes', 'footer'];
	
	/**
	 * menu object
	 * @var Menu
	 */
	protected $menu;
	
	/**
	 * Returns true if the active user can delete this box.
	 * 
	 * @return boolean
	 */
	public function canDelete() {
		if (WCF::getSession()->getPermission('admin.content.cms.canManageBox') && !$this->originIsSystem) {
			return true;
		}
			
		return false;
	}
	
	/**
	 * Returns the box content.
	 *
	 * @return array
	 */
	public function getBoxContent() {
		$content = array();
		$sql = "SELECT	*
			FROM	wcf".WCF_N."_box_content
			WHERE	boxID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($this->boxID));
		while ($row = $statement->fetchArray()) {
			$content[($row['languageID'] ?: 0)] = [
				'title' => $row['title'],
				'content' => $row['content']
			];
		}
	
		return $content;
	}
	
	/**
	 * Gets the title for the rendered version of this box.
	 *
	 * @return      string
	 */
	public function getTitle() {
		if ($this->boxType == 'system') {
			return $this->getController()->getTitle();
		}
		else if ($this->boxType == 'menu') {
			return $this->getMenu()->getTitle();
		}
		else {
			$boxContent = $this->getBoxContent();
			if ($this->isMultilingual) {
				if (isset($boxContent[WCF::getLanguage()->languageID])) return $boxContent[WCF::getLanguage()->languageID]['title'];
			}
			else {
				if (isset($boxContent[0])) return $boxContent[0]['title'];
			}
		}
		
		return '';
	}
	
	/**
	 * Gets the content for the rendered version of this box.
	 * 
	 * @return      string
	 */
	public function getContent() {
		if ($this->boxType == 'system') {
			return $this->getController()->getContent();
		}
		else if ($this->boxType == 'menu') {
			return $this->getMenu()->getContent();
		}
		
		$boxContent = $this->getBoxContent();
		$content = '';
		if ($this->isMultilingual) {
			if (isset($boxContent[WCF::getLanguage()->languageID])) $content = $boxContent[WCF::getLanguage()->languageID]['content'];
		}
		else {
			if (isset($boxContent[0])) $content = $boxContent[0]['content'];
		}
		
		if ($this->boxType == 'text') {
			// @todo parse text
			$content = StringUtil::encodeHTML($content);
		}
		
		return $content;
	}
	
	/**
	 * Returns the rendered version of this box.
	 * 
	 * @return      string
	 */
	public function __toString() {
		if (!$this->hasContent()) return ''; 
		
		WCF::getTPL()->assign([
			'box' => $this
		]);
		return WCF::getTPL()->fetch('__box');
	}
	
	/**
	 * Returns false if this box has no content.
	 * 
	 * @return      boolean
	 */
	public function hasContent() {
		if ($this->boxType == 'system') {
			return $this->getController()->hasContent();
		}
		else if ($this->boxType == 'menu') {
			return $this->getMenu()->hasContent();
		}
		else {
			$boxContent = $this->getBoxContent();
			$content = '';
			if ($this->isMultilingual) {
				if (isset($boxContent[WCF::getLanguage()->languageID])) $content = $boxContent[WCF::getLanguage()->languageID]['content'];
			}
			else {
				if (isset($boxContent[0])) $content = $boxContent[0]['content'];
			}
			
			return !empty($content);
		}
	}
	
	public function getController() {
		// @todo
	}
	
	public function getMenu() {
		if ($this->menu === null) {
			$this->menu = MenuCache::getInstance()->getMenuByID($this->menuID);
		}
		
		return $this->menu;
	}
	
	/**
	 * Returns the image of this box.
	 * 
	 * @return      \wcf\data\media\Media
	 */
	public function getImage() {
		if ($this->boxType == 'system') {
			return $this->getController()->getImage();
		}
		else if ($this->boxType == 'menu') {
			return null;
		}
		else {
			// @todo
		}
		
		return null;
	}
	
	/**
	 * Returns true if this box has an image.
	 *
	 * @return      boolean
	 */
	public function hasImage() {
		if ($this->boxType == 'system') {
			return $this->getController()->hasImage();
		}
		else if ($this->boxType == 'menu') {
			return false;
		}
		else {
			// @todo
		}
		
		return false;
	}
	
	public function getLink() {
		// @todo
		return '';
	}
	
	public function hasLink() {
		// @todo
		return false;
	}
	
	/**
	 * Returns the box with the given name.
	 *
	 * @param	string		$name
	 * @return	Box
	 */
	public static function getBoxByName($name) {
		$sql = "SELECT	*
			FROM	wcf".WCF_N."_box
			WHERE	name = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$name]);
		
		return $statement->fetchObject(Box::class);
	}
}
