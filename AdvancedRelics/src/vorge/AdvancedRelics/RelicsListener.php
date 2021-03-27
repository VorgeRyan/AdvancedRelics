<?php
declare(strict_types = 1);

namespace vorge\AdvancedRelics;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use function in_array;
use function mt_rand;

class RelicsListener implements Listener{

	/** @var RelicsMain */
	private $plugin;

	/**
	 * RelicsListener constructor.
	 *
	 * @param RelicsMain $plugin
	 */
	public function __construct(RelicsMain $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();

		$relicRandomize = $this->plugin->getConfig()->get("relicRandomize");
		$randomRelic = $this->plugin->getRandomRelic();
		$relicData = $this->plugin->getRelicData($randomRelic);

		if(in_array($event->getBlock()->getId(), $this->plugin->getConfig()->get("relicBlocks"))){
			if(mt_rand(1, $relicRandomize) == 1){
				$nbt = new CompoundTag("", [
					new StringTag("relic", $randomRelic)
				]);

				$item = Item::get(Item::NETHER_STAR, 0, 1, $nbt);
				$item->setCustomName("§l" . $relicData["name"] . " " . $this->plugin->getConfig()->get("relicName"));

				$player->getInventory()->addItem($item);
				$player->addTitle("§7You have found a", $relicData["name"] . " §6Relic§r");

				if($this->plugin->getConfig()->get("broadcastMessage") == true){
					if(in_array($randomRelic, $this->plugin->getConfig()->get("opRelics"))){
						$this->plugin->getServer()->broadcastMessage("§l§c(!) §r§a" . $player->getName() . " §rhas found a §l" . $relicData["name"] . " §6Relic");
					}
				}
			}
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onTap(PlayerInteractEvent $event){
		$player = $event->getPlayer();

		$item = $player->getInventory()->getItemInHand();
		$relics = $this->plugin->getRelics();

		if($item->getId() === Item::NETHER_STAR){
			foreach($relics as $type => $data){
				$nbt = $item->getNamedTag();
				if($nbt->hasTag("relic", StringTag::class)){
					$relicType = $nbt->getString("relic");
					if($relicType === $type){
						$item->setCount($item->getCount() - 1);
						$item->setDamage($item->getDamage());
						$event->getPlayer()->getInventory()->setItemInHand($item);
						$reward = $this->plugin->reward($player, $type);

						if($reward instanceof Item){
							$player->getInventory()->addItem($reward);
							$player->sendMessage("§aYou received §e" . $reward->getName() . " §d(x" . $reward->getCount() . ") §afrom §l" . $data["name"] . " §6Relic");
						}
					}
				}
			}
		}
	}
}
