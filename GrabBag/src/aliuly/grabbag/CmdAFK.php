<?php
//= cmd:afk,Player_Management
//: Toggles AFK status
//>  usage: **afk** _[message]_
//:
namespace aliuly\grabbag;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

use aliuly\common\mc;
use aliuly\common\BasicCli;
use aliuly\common\PermUtils;
use aliuly\common\MPMU;


class CmdAFK extends BasicCli implements CommandExecutor,Listener {
	public function __construct($owner) {
		parent::__construct($owner);
		foreach (["freeze-thaw", "mute-unmute", "shield"] as $m) {
			if ($this->owner->getModule($m) === null) {
				$this->owner->getLogger()->warning(mc::_("AFK requires %1% to be enabled", $m));
				return;
			}
		}

    PermUtils::add($this->owner, "gb.cmd.afk", "afk command", "op");
    $this->enableCmd("afk",
							  ["description" => mc::_("away from keyboard"),
								"usage" => mc::_("/afk [message]"),
								"permission" => "gb.cmd.afk"]);
	}
  public function onCommand(CommandSender $sender,Command $cmd,$label, array $args) {
    if (strtolower($cmd->getName()) != "afk") return false;
    if (!MPMU::inGame($sender)) return true;
    $api = $this->owner->api;
    if ($api->isShielded($sender) && $this->isFrozen($sender) && $this->getMute($sender)) {
      // Current AFK'ed
      $msg = count($args) == 0 ? mc::_("%1% is back at keyboard", $sender->getName()) : implode(" ", $args);
      $api->freeze($sender, false);
      $api->setMute($sender, false);
      $api->setShield($sender, false);
      $this->owner->getServer()->broadcastMesage($msg);
    } else {
      $msg = count($args) == 0 ? mc::_("%1% is away from keyboard", $sender->getName()) : implode(" ", $args);
      $api->freeze($sender, true);
      $api->setMute($sender, true);
      $api->setShield($sender, true);
    }
    $this->owner->getServer()->broadcastMesage($msg);
    return true;
	}
	/**
	 * Handle player quit events.  Free's data used by the state tracking
	 * code.
	 *
	 * @param PlayerQuitEvent $ev - Quit event
	 * @priority LOWEST
	 */
	public function onPlayerQuit(PlayerQuitEvent $ev) {
		//echo __METHOD__.",".__LINE__."\n";//##DEBUG
		$sender = $ev->getPlayer();
		$api = $this->owner->api;
		if ($api->isShielded($sender) && $this->isFrozen($sender) && $this->getMute($sender)) {
			$api->freeze($sender, false);
			$api->setMute($sender, false);
			$api->setShield($sender, false);
		}
	}

}
