<?php

namespace NPCShop\event;

use NPCShop\Main;
use NPCShop\entity\MarketNPC;

use pocketmine\event\Listener;

use pocketmine\utils\Config;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;

use pocketmine\Player;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;

use pocketmine\item\Item;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\math\Vector3;

class OpenShopEvent implements Listener{
	private $owner;
	public function __construct(Main $owner){
		$this->owner = $owner;
	}
		
	public function TouchNPC(EntityDamageEvent $event){
		$npc = $event->getEntity();
		if($npc instanceof MarketNPC){
			$event->setCancelled();
			$npc->setMotion(new Vector3(0, 0, 0));
			if($event instanceof EntityDamageByEntityEvent){
				$player = $event->getDamager();
				if($player instanceof Player){
				    if(isset($this->owner->db[$npc->getLevel()->getFolderName()]["{$npc->x}:{$npc->y}:{$npc->z}"]) and $this->owner->pd["모드"][$player->getName()] == "노모드"){
				        $this->owner->sendShopUI($player, $this->owner->db[$npc->getLevel()->getFolderName()]["{$npc->x}:{$npc->y}:{$npc->z}"]);
				        $this->owner->pd["좌표"][$player->getName()] = "{$npc->getLevel()->getFolderName()}:{$npc->x}:{$npc->y}:{$npc->z}";
				    }
				}
			}
		}
	}
}