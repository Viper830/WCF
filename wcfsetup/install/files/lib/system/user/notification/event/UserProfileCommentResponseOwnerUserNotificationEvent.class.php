<?php
namespace wcf\system\user\notification\event;
use wcf\data\comment\Comment;
use wcf\data\user\User;
use wcf\system\request\LinkHandler;
use wcf\system\user\notification\event\AbstractUserNotificationEvent;
use wcf\system\WCF;

/**
 * User notification event for profile's owner for commment responses.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2014 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	system.user.notification.event
 * @category	Community Framework
 */
class UserProfileCommentResponseOwnerUserNotificationEvent extends AbstractUserNotificationEvent {
	/**
	 * @see	\wcf\system\user\notification\event\AbstractUserNotificationEvent::$stackable
	 */
	protected $stackable = true;
	
	/**
	 * @see	\wcf\system\user\notification\event\IUserNotificationEvent::getTitle()
	 */
	public function getTitle() {
		$count = count($this->getAuthors());
		if ($count > 1) {
			return $this->getLanguage()->getDynamicVariable('wcf.user.notification.commentResponseOwner.title.stacked', array(
				'count' => $count,
				'timesTriggered' => $this->timesTriggered
			));
		}
		
		return $this->getLanguage()->get('wcf.user.notification.commentResponseOwner.title');
	}
	
	/**
	 * @see	\wcf\system\user\notification\event\IUserNotificationEvent::getMessage()
	 */
	public function getMessage() {
		// @todo: use cache or a single query to retrieve required data
		$comment = new Comment($this->userNotificationObject->commentID);
		if ($comment->userID) {
			$commentAuthor = new User($comment->userID);
		}
		else {
			$commentAuthor = new User(null, array(
				'username' => $comment->username
			));
		}
		
		$authors = array_values($this->getAuthors());
		$count = count($authors);
		if ($count > 1) {
			return $this->getLanguage()->getDynamicVariable('wcf.user.notification.commentResponseOwner.message.stacked', array(
				'author' => $commentAuthor,
				'authors' => $authors,
				'count' => $count,
				'others' => $count - 1
			));
		}
		
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.commentResponseOwner.message', array(
			'author' => $this->author,
			'commentAuthor' => $commentAuthor
		));
	}
	
	/**
	 * @see	\wcf\system\user\notification\event\IUserNotificationEvent::getEmailMessage()
	 */
	public function getEmailMessage($notificationType = 'instant') {
		$comment = new Comment($this->userNotificationObject->commentID);
		$owner = new User($comment->objectID);
		if ($comment->userID) {
			$commentAuthor = new User($comment->userID);
		}
		else {
			$commentAuthor = new User(null, array(
				'username' => $comment->username
			));
		}
		
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.commentResponseOwner.mail', array(
			'response' => $this->userNotificationObject,
			'author' => $this->author,
			'commentAuthor' => $commentAuthor,
			'owner' => $owner,
			'notificationType' => $notificationType
		));
	}
	
	/**
	 * @see	\wcf\system\user\notification\event\IUserNotificationEvent::getLink()
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('User', array('object' => WCF::getUser()), '#wall');
	}
	
	/**
	 * @see	\wcf\system\user\notification\event\IUserNotificationEvent::getEventHash()
	 */
	public function getEventHash() {
		return sha1($this->eventID . '-' . $this->userNotificationObject->commentID);
	}
}
