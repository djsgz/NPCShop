<?php

namespace NPCShop\event;

use NPCShop\Main;
use NPCShop\entity\MarketNPC;

use onebone\economyapi\EconomyAPI;

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

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\event\server\DataPacketReceiveEvent;

use pocketmine\math\Vector3;

class ShoppingEvent implements Listener{
	private $owner;
	public function __construct(Main $owner){
		$this->owner = $owner;
	}
	
	public function Shopping(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if($packet instanceof ModalFormResponsePacket and $packet->formId == 4344){
			$data = json_decode($packet->formData, true);
			if($data[2] == null or (int)$data[2] < 1){
				$player->sendMessage("{$this->owner->prefix}모든 정보를 정확히 입력해주세요.");
			}else{
				$eco = EconomyAPI::getInstance();
				$ni = explode(":", $this->owner->pd["좌표"][$player->getName()]);
				$fn = $ni[0];
				$x = $ni[1];
				$y = $ni[2];
				$z = $ni[3];
				$si = explode(":", $this->owner->db[$fn]["{$x}:{$y}:{$z}"]);
				$itemN = $si[0];
				$buyM = $si[1];
				$sellM = $si[2];
				$amount = (int)$data[2];
				$npc = $player->getLevel()->getNearestEntity(new Vector3($x, $y, $z), 1, MarketNPC::class, false);
				if($npc !== null){
				    $item = $npc->getInventory()->getItemInHand();
				    $item->count = $amount;
				    if($data[1] == "0"){
					    if($buyM !== "구매불가"){
					        $buyM = (int)$buyM;
					        if($eco->myMoney($player) >= $buyM*$amount){
							    $player->getInventory()->addItem($item);
							    $pm = $eco->myMoney($player);
						        $eco->reduceMoney($player, $buyM*$amount);
						        $player->sendMessage("{$this->owner->prefix}{$itemN}§l§f을(를) {$amount}개 구매했습니다.\n§l§7구매전 : §a{$pm}원 §l§7/ 구매후 : §a{$eco->myMoney($player)}원");
					        }else{
						        $player->sendMessage("{$this->owner->prefix}돈이 부족합니다.");
					        }
					    }else{
						    $player->sendMessage("{$this->owner->prefix}해당 아이템은 구매할 수 없습니다.");
					    }
				    }elseif($data[1] == "1"){
					    if($sellM !== "판매불가"){
					        $sellM = (int)$sellM;
					        if($player->getInventory()->contains($item) >= $amount){
							    $player->getInventory()->removeItem($item);
							    $pm = $eco->myMoney($player);
						        $eco->addMoney($player, $sellM*$amount);
						        $player->sendMessage("{$this->owner->prefix}{$itemN}§l§f을(를) {$amount}개 판매했습니다.\n§l§7판매전 : §a{$pm}원 §l§7/ 판매후 : §a{$eco->myMoney($player)}원");
					        }else{
						        $player->sendMessage("{$this->owner->prefix}아이템이 부족합니다.");
					        }
					    }else{
						    $player->sendMessage("{$this->owner->prefix}해당 아이템은 판매할 수 없습니다.");
					    }
					}
				}
			}
		}
	}
}